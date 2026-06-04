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
     * Grand total payable for this bill = ceil(dues + late fine + GST).
     * $asOf controls the date the late fine is calculated against:
     *   - pending-bills passes null  -> now()
     *   - upi-pending  passes upi_submitted_at (frozen at submission)
     */
    public function calculateGrandTotal($asOf = null)
    {
        $maintenance = $this->maintenance;
        if (!$maintenance) {
            return ceil($this->dues_amount);
        }

        $asOf = $asOf ? \Carbon\Carbon::parse($asOf) : now();
        $late_fine = 0;

        if ($maintenance->due_date) {
            $dueDate = \Carbon\Carbon::parse($maintenance->due_date);
            if ($dueDate->lt($asOf->copy()->startOfDay())) {
                $late_days = $dueDate->diffInDays($asOf);
                switch ($maintenance->late_fine_type) {
                    case 'Daily':      $late_fine = $late_days * $maintenance->late_fine_value; break;
                    case 'Fixed':      $late_fine = $maintenance->late_fine_value;              break;
                    case 'Percentage': $late_fine = ($this->dues_amount * $maintenance->late_fine_value) / 100; break;
                }
            }
        }

        $total_before_gst = $this->dues_amount + $late_fine;
        $gst_amount       = ($total_before_gst * ($maintenance->gst ?? 0)) / 100;

        return ceil($total_before_gst + $gst_amount);
    }

}
