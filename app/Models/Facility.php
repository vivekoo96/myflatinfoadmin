<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Facility extends Model
{
    use HasFactory,SoftDeletes;
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function timings()
    {
        return $this->hasMany('App\Models\Timing')->withTrashed();
    }
    
    public function bookings()
    {
        return $this->hasMany('App\Models\Booking')->withTrashed();
    }
    
    public function getIconAttribute($value)
    {
        // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
        //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
        // });
        return asset('public/images/'.$value);
    }
    
    public function getIconFilenameAttribute()
    {
        return $this->attributes['icon'] ?? null;
    }
    
    
}
