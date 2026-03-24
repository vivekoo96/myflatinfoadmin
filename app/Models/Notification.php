<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    
   protected $fillable = [
        'user_id', 'from_id', 'flat_id', 'building_id', 'status', 'department_id',
        'title', 'body', 'type', 'dataPayload', 'read_at', 'admin_read', 'deleted_at', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'dataPayload' => 'array', // auto json_encode on save, json_decode on fetch
    ];
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function from_user()
    {
        return $this->belongsTo('App\Models\User','from_id')->withTrashed();
    }
}
