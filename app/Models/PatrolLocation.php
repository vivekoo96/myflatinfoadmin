<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatrolLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id', 'name', 'description', 'qr_string', 'status',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function patrols()
    {
        return $this->hasMany(GuardPatrol::class);
    }
}
