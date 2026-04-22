<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyCheckin extends Model
{
    protected $fillable = [
        'building_id',
        'guard_user_id',
        'gate_id',
        'building_shift_id',
        'status',
        'scheduled_at',
        'checked_in_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_user_id');
    }

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function buildingShift()
    {
        return $this->belongsTo(BuildingShift::class);
    }
}
