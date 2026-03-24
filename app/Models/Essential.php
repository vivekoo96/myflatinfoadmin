<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Essential extends Model
{
    use HasFactory,SoftDeletes;
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
        public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    public function payments()
    {
        return $this->hasMany('App\Models\EssentialPayment')->withTrashed();
    }

}
