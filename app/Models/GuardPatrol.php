<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardPatrol extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'guard_user_id', 'patrol_location_id',
        'checkin_type', 'shift', 'photo_url', 'qr_scanned_value', 'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_user_id')->withTrashed();
    }

    public function patrolLocation()
    {
        return $this->belongsTo(PatrolLocation::class)->withTrashed();
    }
}
