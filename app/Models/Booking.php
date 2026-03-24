<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $appends = ['booking_status','refundable_amount','payable_amount','amount'];

    public function facility()
    {
        return $this->belongsTo('App\Models\Facility')->withTrashed();
    }
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
      public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }
    
    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }
    
    public function timing()
    {
        return $this->belongsTo('App\Models\Timing')->withTrashed();
    }
    
    public function getAmountAttribute()
    {
        $timing = $this->timing;
        $amount = $timing->price * $this->members;
        return ceil($amount);
    }
    
    public function getPayableAmountAttribute()
    {
        $timing = $this->timing;
        $amount = $timing->price * $this->members;
        $gst = $amount * $this->facility->gst / 100;
        $item_amount = $amount + $gst;
        $item_amount = ceil($item_amount);
        return $item_amount;
    }
    
    public function getRefundableAmountAttribute()
    {
        $timing = $this->timing;
        $amount = $timing->price * $this->members;
        $refund_amount = 0;
        if($timing->cancellation_type == 'Fixed'){
            $refund_amount = ($timing->price - $timing->cancellation_value) * $this->members;
        }
        if($timing->cancellation_type == 'Percentage'){
            $refund_amount = $timing->price * $timing->cancellation_value / 100;
            $refund_amount = ($timing->price - $refund_amount) * $this->members;
        }
        return $refund_amount = ceil($refund_amount);

    }
    
    public function getBookingStatusAttribute()
    {
        if ($this->status !== 'Success') {
            return $this->status;
        }
    
        $now = now();
    
        $startDateTime = \Carbon\Carbon::parse($this->date . ' ' . $this->timing->from);
        $endDateTime   = \Carbon\Carbon::parse($this->date . ' ' . $this->timing->to);
    
        if ($now->lt($startDateTime)) {
            return 'Upcoming';
        } elseif ($now->between($startDateTime, $endDateTime)) {
            return 'Ongoing';
        } else {
            return 'Completed';
        }
    }

    
}
