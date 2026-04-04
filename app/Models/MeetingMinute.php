<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingMinute extends Model
{
    use HasFactory;

    // No SoftDeletes — once saved, cannot be deleted
    protected $fillable = [
        'building_id',
        'title',
        'description',
        'created_by',
        'created_by_role',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
