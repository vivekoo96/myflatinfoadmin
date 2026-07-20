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
            $filename = basename($value);
            
            // Generate correct public or S3 URL
            if ($this->classified && $this->classified->building_id == 0) {
                $superadminUrl = config('app.superadmin_url');
                if (empty($superadminUrl)) {
                    $currentUrl = url('/');
                    if (strpos($currentUrl, 'dev.buildingadmin') !== false) {
                        $superadminUrl = str_replace('dev.buildingadmin', 'dev.superadmin', $currentUrl);
                    } elseif (strpos($currentUrl, 'buildingadmin') !== false) {
                        $superadminUrl = str_replace('buildingadmin', 'superadmin', $currentUrl);
                    } else {
                        $superadminUrl = 'https://superadmin.myflatinfo.com';
                    }
                }
                return rtrim($superadminUrl, '/') . '/public/images/classifieds/' . $filename;
            }
            return asset('public/images/classifieds/' . $filename);
        }
    }

    public function getPhotoFilenameAttribute()
    {
        return $this->attributes['photo'] ?? null;
    }
}
