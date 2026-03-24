<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat');
    }
    
    public function bookings()
    {
        return $this->hasMany('App\Models\Booking');
    }
}
