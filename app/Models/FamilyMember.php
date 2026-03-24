<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class FamilyMember extends Model
{
    use HasFactory;
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
    public function getPhotoAttribute($value)
    {   if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return asset('public/images/'.$value);
        }
    }
    
    public function getPhotoFilenameAttribute()
    {
        return $this->attributes['photo'] ?? null;
    }
    
}
