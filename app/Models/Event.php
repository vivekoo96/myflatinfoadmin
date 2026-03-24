<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;


class Event extends Model
{
    use HasFactory,SoftDeletes;
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
    
    
    public function payments()
    {
        return $this->hasMany('App\Models\Payment')->withTrashed();
    }
    
    /**
     * Scope to filter only Active events (TC07: hides Inactive/Pending events for users)
     * For user-facing queries, chain with: ->where('from_time', '<=', now())
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
    
    public function getImageAttribute($value)
    {
        if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return asset('public/images/'.$value);
        }
        return '';
    }

    public function getImageFilenameAttribute()
    {
        return $this->attributes['image'] ?? null;
    }


}
