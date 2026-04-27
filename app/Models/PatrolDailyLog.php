<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrolDailyLog extends Model
{
    protected $fillable = [
        'building_id',
        'patrol_schedule_id',
        'patrol_location_id',
        'guard_user_id',
        'patrol_date',
        'checkin_type',
        'photo_url',
        'qr_scanned_value',
        'checked_at',
    ];

    protected $casts = [
        'checked_at'   => 'datetime',
        'patrol_date'  => 'date',
    ];

    public function schedule()
    {
        return $this->belongsTo(PatrolLocation::class, 'patrol_schedule_id');
    }

    public function location()
    {
        return $this->belongsTo(PatrolLocation::class, 'patrol_location_id');
    }

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_user_id')->withTrashed();
    }
}
