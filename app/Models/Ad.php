<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Ad extends Model
{
    use HasFactory;

    public function getImageAttribute($value)
    {
        if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return 'https://superadmin.myflatinfo.com/public/images/'.$value;
        }
        // return asset('public/images/'.$value);

    }
    
    public function getImageFilenameAttribute()
    {
        return $this->attributes['image'] ?? null;
    }
    public function buildings()
{
    return $this->belongsToMany(Building::class, 'ad_buildings', 'ad_id', 'building_id');
}
public function building()
{
    return $this->belongsTo(Building::class, 'building_id');
}

    
}
