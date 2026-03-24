<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdBuilding extends Model
{
    protected $table = 'ad_buildings';
    protected $fillable = [
        'ad_id',
        'building_id',
    ];
    public $timestamps = false;

    // Relationships
    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
}
