<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
class Setting extends Model
{
    use HasFactory;
    
    
    public function getLogoAttribute($value)
    {
        // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
        //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
        // });
        return 'https://dev.superadmin.myflatinfo.com/public/images/'.$value;
    }
    
    public function getLogoFilenameAttribute()
    {
        return $this->attributes['logo'] ?? null;
    }
    
    public function getFaviconAttribute($value)
    {
        // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
        //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
        // });
        return 'https://dev.superadmin.myflatinfo.com/public/images/'.$value;
    }
    
    public function getFaviconFilenameAttribute()
    {
        return $this->attributes['favicon'] ?? null;
    }
    
    public function getAddUrlAttribute($value)
    {
        return asset('public/'.$value);
    }

}
