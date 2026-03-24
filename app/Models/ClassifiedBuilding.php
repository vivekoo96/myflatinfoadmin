<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassifiedBuilding extends Model
{
    use HasFactory;

    protected $fillable = [
        'classified_id',
        'building_id',
    ];

    public function classified()
    {
        return $this->belongsTo(Classified::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
}
