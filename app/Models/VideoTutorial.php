<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoTutorial extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'text',
        'video_url',
        'video_type',
        'interfaces',
    ];

    protected $casts = [
        'interfaces' => 'array',
    ];

    public function module()
    {
        return $this->belongsTo(VideoModule::class, 'module_id');
    }
}
