<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory,SoftDeletes;
    
    public function replies()
    {
        return $this->hasMany('App\Models\Reply')->withTrashed();
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
        // Role user comment (still from the users table)
    public function role_user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }


 public function issue()
    {
        return $this->belongsTo('App\Models\Issue')->withTrashed();
    }
    
    
    
    
}
