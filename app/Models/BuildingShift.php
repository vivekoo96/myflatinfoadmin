<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuildingShift extends Model
{
    use SoftDeletes;

    protected $table = 'building_shifts';

    protected $fillable = [
        'building_id',
        'name',
        'start_time',
        'end_time',
        'status',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function patrols()
    {
        return $this->hasMany(GuardPatrol::class, 'building_shift_id');
    }
}
