<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTag extends Model
{
    use HasFactory;
    
    protected $table = 'staff_flat_tags';

    protected $fillable = [
        'staff_id',
        'flat_id',
        'building_id',
        'time_slot',
        'status',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }
}
