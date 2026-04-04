<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuideVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'category',
        'youtube_link',
        'status',
        'created_by',
    ];

    /**
     * Extract YouTube video ID from any standard YouTube URL.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        $url = $this->youtube_link;
        preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m);
        return $m[1] ?? null;
    }

    public function getEmbedUrlAttribute(): string
    {
        $id = $this->youtube_id;
        return $id ? 'https://www.youtube.com/embed/' . $id : '';
    }

    public function getThumbnailAttribute(): string
    {
        $id = $this->youtube_id;
        return $id ? 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg' : '';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
