<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleInout extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'flat_id',
        'vehicle_id',
        'building_id',
        'type'
    ];
    
    public function vehicle()
    {
        return $this->belongsTo('App\Models\Vehicle')->withTrashed();
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
}
