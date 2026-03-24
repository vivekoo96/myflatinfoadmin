<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuildingPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'permission_id',
    ];
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function permission()
    {
        return $this->belongsTo('App\Models\Permission')->withTrashed();
    }
    
}
