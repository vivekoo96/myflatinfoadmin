<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorInout extends Model
{
    use HasFactory,SoftDeletes;
    
    public function visitor()
    {
        return $this->belongsTo('App\Models\Visitor')->withTrashed();
    }

    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }

    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
}
