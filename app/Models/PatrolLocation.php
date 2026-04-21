<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatrolLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id', 'gate_id', 'building_shift_id', 'name', 'description', 'qr_string', 'status', 'patrol_time',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function buildingShift()
    {
        return $this->belongsTo(BuildingShift::class);
    }

    public function patrols()
    {
        return $this->hasMany(GuardPatrol::class);
    }
}
