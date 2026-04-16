<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityActivityResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_activity_id',
        'user_id',
        'flat_id',
        'response',
    ];

    public function activity()
    {
        return $this->belongsTo(CommunityActivity::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
