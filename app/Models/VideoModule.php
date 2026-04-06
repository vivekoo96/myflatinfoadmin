<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoModule extends Model
{
    protected $fillable = ['title'];

    public function videos()
    {
        return $this->hasMany(VideoTutorial::class, 'module_id');
    }
}
