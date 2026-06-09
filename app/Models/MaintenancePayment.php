<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePayment extends Model
{
    use HasFactory,SoftDeletes;
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function maintenance()
    {
        return $this->belongsTo('App\Models\Maintenance')->withTrashed();
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }

    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }

    public function getPaymentScreenshotAttribute($value)
    {
        if ($value) {
            return asset('public/maintenance_screenshots/' . $value);
        }
        return null;
    }

    public function getPaymentScreenshotFilenameAttribute()
    {
        return $this->attributes['payment_screenshot'] ?? null;
    }

    /**
     * Full breakdown of a flat's outstanding maintenance across ALL unpaid bills.
     * Single source of truth used by flatOutstandingTotal() and by the
     * pending-bills web view / API so the displayed columns and the Total agree.
     *
     * Returns:
     *   current_dues - dues of the latest (highest-id) unpaid bill
     *   arrears      - sum of dues of the OLDER unpaid bills
     *   dues_total   - current_dues + arrears (all unpaid dues)
     *   late_fine    - sum of late fine across all unpaid bills (now() reference)
     *   gst          - sum of GST across all unpaid bills
     *   total        - ceil(dues_total + late_fine + gst)
     *
     * @param  int $flatId
     * @return array{current_dues:float,arrears:float,dues_total:float,late_fine:float,gst:float,total:float}
     */
    public static function flatOutstandingBreakdown($flatId)
    {
        $payments = self::where('flat_id', $flatId)
            ->where('status', 'Unpaid')
            ->with('maintenance')
            ->orderBy('id', 'desc')
            ->get();

        $current_dues = 0;
        $arrears      = 0;
        $dues_total   = 0;
        $late_total   = 0;
        $gst_total    = 0;

        $latestId = optional($payments->first())->id;

        foreach ($payments as $payment) {
            if (!$payment->maintenance) {
                continue;
            }

            $maintenance = $payment->maintenance;
            $late_fine   = 0;

            if ($maintenance->due_date) {
                $dueDate = \Carbon\Carbon::parse($maintenance->due_date);
                if ($dueDate->lt(now()->startOfDay())) {
                    $late_days = $dueDate->diffInDays(now());
                    switch ($maintenance->late_fine_type) {
                        case 'Daily':      $late_fine = $late_days * $maintenance->late_fine_value; break;
                        case 'Fixed':      $late_fine = $maintenance->late_fine_value;              break;
                        case 'Percentage': $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100; break;
                    }
                }
            }

            $amount      = $payment->dues_amount + $late_fine;
            $dues_total += $payment->dues_amount;
            $late_total += $late_fine;
            $gst_total  += ($amount * ($maintenance->gst ?? 0)) / 100;

            if ($payment->id === $latestId) {
                $current_dues = $payment->dues_amount;
            } else {
                $arrears += $payment->dues_amount;
            }
        }

        return [
            'current_dues' => $current_dues,
            'arrears'      => $arrears,
            'dues_total'   => $dues_total,
            'late_fine'    => $late_total,
            'gst'          => $gst_total,
            'total'        => ceil($dues_total + $late_total + $gst_total),
        ];
    }

    /**
     * Total outstanding maintenance payable for a flat = ceil( sum of
     * (dues + late fine + GST) across ALL unpaid maintenance bills of the flat ).
     *
     * This is the single source of truth for the "Total" shown on
     * account/pending-bills, account/upi-pending and the pending-bills API,
     * so they always agree.
     */
    public static function flatOutstandingTotal($flatId)
    {
        return self::flatOutstandingBreakdown($flatId)['total'];
    }

    /**
     * Settle the flat's FULL outstanding maintenance in one UPI payment:
     * marks every Unpaid maintenance bill of the flat as Paid and records a
     * single Credit transaction whose amount equals flatOutstandingTotal()
     * (i.e. the exact ₹ total shown on the pending pages).
     *
     * @param  int                    $flatId
     * @param  MaintenancePayment|null $approvedPayment  the submitted UPI bill (marked Approved)
     * @param  string|null            $remarks
     * @return array{transaction: \App\Models\Transaction, grand_total: float}|null  null if nothing outstanding
     */
    public static function settleFlatOutstanding($flatId, $approvedPayment = null, $remarks = null)
    {
        $unpaid = self::where('flat_id', $flatId)
            ->where('status', 'Unpaid')
            ->with(['maintenance', 'flat'])
            ->get();

        if ($unpaid->isEmpty()) {
            return null;
        }

        $total_amount = 0;
        $total_gst    = 0;

        // Pass 1: compute totals and stash each bill's late fine.
        foreach ($unpaid as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine   = 0;

            if ($maintenance && $maintenance->due_date) {
                $dueDate = \Carbon\Carbon::parse($maintenance->due_date);
                if ($dueDate->lt(now()->startOfDay())) {
                    $late_days = $dueDate->diffInDays(now());
                    switch ($maintenance->late_fine_type) {
                        case 'Daily':      $late_fine = $late_days * $maintenance->late_fine_value; break;
                        case 'Fixed':      $late_fine = $maintenance->late_fine_value;              break;
                        case 'Percentage': $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100; break;
                    }
                }
            }

            $payment->late_fine = $late_fine;
            $amount        = $payment->dues_amount + $late_fine;
            $total_amount += $amount;
            $total_gst    += ($amount * (($maintenance->gst ?? 0))) / 100;
        }

        $grand_total = ceil($total_amount + $total_gst);
        $first       = $unpaid->first();

        return \DB::transaction(function () use ($unpaid, $first, $approvedPayment, $remarks, $grand_total, $total_gst) {
            // One transaction for the whole UPI payment.
            $transaction = new \App\Models\Transaction();
            $transaction->building_id  = $first->building_id;
            $transaction->user_id      = $first->user_id;
            $transaction->flat_id      = $first->flat_id;
            $transaction->block_id     = optional($first->flat)->block_id;
            $transaction->model        = 'Maintenance';
            $transaction->model_id     = $approvedPayment->maintenance_id ?? $first->maintenance_id;
            $transaction->type         = 'Credit';
            $transaction->payment_type = 'InBank';
            $transaction->amount       = $grand_total;
            $transaction->gst_amount   = $total_gst;
            $transaction->reciept_no   = 'UPI' . ($approvedPayment->id ?? $first->id) . rand(1000, 9999);
            $transaction->desc         = 'Maintenance Payment (full outstanding) paid by flat number ' . (optional($first->flat)->name ?? 'Unknown') . ' via UPI';
            $transaction->status       = 'Success';
            $transaction->date         = now()->toDateString();
            $transaction->save();

            // Mark every unpaid bill Paid and link it to the transaction.
            foreach ($unpaid as $payment) {
                $payment->transaction_id = $transaction->id;
                $payment->status         = 'Paid';
                // Store the ACTUAL amount paid for this bill = dues + late fine + GST
                // (not just base dues), so history / receipts show the real figure.
                // Sum across the flat's bills equals the transaction amount above.
                $lateFine = $payment->late_fine ?? 0;
                $gstRate  = optional($payment->maintenance)->gst ?? 0;
                $billGst  = (($payment->dues_amount + $lateFine) * $gstRate) / 100;
                $payment->paid_amount    = ceil($payment->dues_amount + $lateFine + $billGst);
                $payment->dues_amount    = 0;
                $payment->type           = 'UPI';
                $payment->payment_type   = 'InBank';

                if ($approvedPayment && $payment->id == $approvedPayment->id) {
                    $payment->upi_payment_status = 'Approved';
                    $payment->upi_remarks        = $remarks;
                }

                $payment->save();
            }

            return ['transaction' => $transaction, 'grand_total' => $grand_total];
        });
    }

}
