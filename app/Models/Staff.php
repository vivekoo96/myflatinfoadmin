<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs';

    protected $fillable = [
        'building_id',
        'staff_id',
        'name',
        'photo',
        'phone',
        'address',
        'type',
        'category',
        'is_open_to_all',
        'document_verification',
        'noc_police',
        'status',
        'creator_id',
        'creator_type',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function tags()
    {
        return $this->hasMany(StaffTag::class, 'staff_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(StaffAttendance::class, 'staff_id');
    }
}
