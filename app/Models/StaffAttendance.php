<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendance_logs';

    protected $fillable = [
        'staff_id',
        'building_id',
        'gate_id',
        'date',
        'entry_time',
        'exit_time',
        'status',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function flatAttendances()
    {
        return $this->hasMany(StaffFlatAttendance::class, 'attendance_log_id');
    }
}
