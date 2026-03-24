<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuildingUser extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'building_id',
        'user_id', 
        'role_id'
    ];
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function role()
    {
        return $this->belongsTo('App\Models\Role')->withTrashed();
    }
    
    public function flat()
{
    return $this->belongsTo('App\Models\Flat')->withTrashed();
}

    
      public function scopeActive($query)
    {
        return $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['active']);
    }


    
}
