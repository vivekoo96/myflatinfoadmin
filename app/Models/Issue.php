<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
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
    public function role_user_id()
{
    return $this->belongsTo('App\Models\User')->withTrashed();
}
public function role_user()
{
    return $this->belongsTo('App\Models\User', 'role_user_id')->withTrashed();
}

    public function block()
    {
        return $this->belongsTo('App\Models\Block')->withTrashed();
    }
    

    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }
    
    public function department()
    {
        return $this->belongsTo('App\Models\Role','role_id')->withTrashed();
    }
    
    public function photos()
    {
        return $this->hasMany('App\Models\IssuePhoto')->withTrashed();
    }
    
    public function comments()
    {
        return $this->hasMany('App\Models\Comment')->withTrashed();
    }

}
