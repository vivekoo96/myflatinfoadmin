<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlatParking extends Model
{
    use HasFactory;
    protected $table = 'flat_parkings';
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
    public function parking()
    {
        return $this->belongsTo('App\Models\Parking')->withTrashed();
    }
    
    
}
