<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class GatePass extends Model
{
    use HasFactory,SoftDeletes;
    
    public function building()
    {
        return $this->belongsTo('App\Models\Visitor')->withTrashed();
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function visitor()
    {
        return $this->belongsTo('App\Models\Visitor')->withTrashed();
    }
    
    public function getImageAttribute($value)
    {
        if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return asset('public/images/'.$value);
        }
        return null;
    }
    
    public function getImageFilenameAttribute()
    {
        return $this->attributes['image'] ?? null;
    }
    
    public function getExtraImageAttribute($value)
    {
        if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return asset('public/images/'.$value);
        }
        return null;
    }
    
    public function getExtraImageFilenameAttribute()
    {
        return $this->attributes['extra_image'] ?? null;
    }
    
}
