<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classified extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'desc',
        'category',
        'user_id',
        'building_id',
        'block_id',
        'flat_id',
        'status',
        'notification_type',
        'reason',
        'approved_at',
        'is_approved_on_creation',
    ];
    
       protected $casts = [
        'is_approved_on_creation' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }

    public function block()
    {
        return $this->belongsTo('App\Models\Block')->withTrashed();
    }

    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }

    public function classifiedBuildings()
    {
        return $this->hasMany(ClassifiedBuilding::class);
    }

    public function photos()
    {
        return $this->hasMany('App\Models\ClassifiedPhoto');
    }
}
