<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuardPatrolAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'guard_patrol_assignments';

    protected $fillable = [
        'building_id',
        'guard_user_id',
        'patrol_location_id',
        'building_shift_id',
        'notes',
        'status',
        'assigned_by',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_user_id')->withTrashed();
    }

    public function patrolLocation()
    {
        return $this->belongsTo(PatrolLocation::class, 'patrol_location_id')->withTrashed();
    }

    public function buildingShift()
    {
        return $this->belongsTo(BuildingShift::class, 'building_shift_id')->withTrashed();
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
