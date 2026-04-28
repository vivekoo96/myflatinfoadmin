<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffFlatAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_flat_attendance';

    protected $fillable = [
        'attendance_log_id',
        'staff_id',
        'flat_id',
        'marked_at',
    ];

    public function attendanceLog()
    {
        return $this->belongsTo(StaffAttendance::class, 'attendance_log_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }
}
