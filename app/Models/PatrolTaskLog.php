<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrolTaskLog extends Model
{
    protected $fillable = [
        'building_id', 'patrol_task_id', 'patrol_location_id',
        'guard_user_id', 'checkin_type', 'photo_url', 'qr_scanned_value', 'checked_at'
    ];

    protected $casts = ['checked_at' => 'datetime'];

    public function task()
    {
        return $this->belongsTo(PatrolLocation::class, 'patrol_task_id');
    }

    public function location()
    {
        return $this->belongsTo(PatrolLocation::class, 'patrol_location_id');
    }

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_user_id');
    }
}
