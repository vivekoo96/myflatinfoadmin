<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    
    public function order()
    {
        return $this->belongsTo('App\Models\Order','order_id','order_id');
    }

public function payerRole()
{
    return $this->belongsTo('App\Models\User'::class, 'payerrole_id');
}

    public function maintenance_payments()
    {
        return $this->hasMany('App\Models\MaintenancePayment');
    }
    
    public function bookings()
    {
        return $this->hasMany('App\Models\Booking');
    }
    
}
