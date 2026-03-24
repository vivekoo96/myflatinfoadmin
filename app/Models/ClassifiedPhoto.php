<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ClassifiedPhoto extends Model
{
    use HasFactory;

    // ✅ Add this
    protected $fillable = [
        'classified_id',
        'photo',
    ];

    public function classified()
    {
        return $this->belongsTo('App\Models\Classified')->withTrashed();
    }

    public function getPhotoAttribute($value)
    {
        if ($value != '') {
            // ✅ Generate correct public or S3 URL
            if ($this->classified && $this->classified->building_id == 0) {
                return rtrim(config('app.superadmin_url'), '/')
                . '/public/images/' . $value;
            }
            return asset('public/images/classifieds/' . $value);
        }
    }

    public function getPhotoFilenameAttribute()
    {
        return $this->attributes['photo'] ?? null;
    }
}
