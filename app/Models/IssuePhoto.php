<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class IssuePhoto extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = ['issue_id', 'photo'];
    
    public function issue()
    {
        return $this->belongsTo('App\Models\Issue')->withTrashed();
    }
    
    public function getPhotoAttribute($value)
    {
        // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
        //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
        // });
        return asset('public/images/'.$value);
    }
    
    public function getPhotoFilenameAttribute()
    {
        return $this->attributes['photo'] ?? null;
    }
    
}
