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
     * Total outstanding maintenance payable for a flat = ceil( sum of
     * (dues + late fine + GST) across ALL unpaid maintenance bills of the flat ).
     *
     * This is the single source of truth for the "Total" shown on
     * account/pending-bills, account/upi-pending and the pending-bills API,
     * so they always agree.
     */
    public static function flatOutstandingTotal($flatId)
    {
        $payments = self::where('flat_id', $flatId)
            ->where('status', 'Unpaid')
            ->with('maintenance')
            ->get();

        $total_amount = 0;
        $total_gst    = 0;

        foreach ($payments as $payment) {
            if (!$payment->maintenance) {
                continue;
            }

            $late_fine   = 0;
            $maintenance = $payment->maintenance;

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

            $amount        = $payment->dues_amount + $late_fine;
            $total_amount += $amount;
            $total_gst    += ($amount * ($maintenance->gst ?? 0)) / 100;
        }

        return ceil($total_amount + $total_gst);
    }

}
