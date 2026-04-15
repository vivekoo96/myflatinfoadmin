<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityNote extends Model
{
    use HasFactory;

    protected $fillable = ['building_id', 'guard_user_id', 'gate_id', 'title', 'body', 'noted_at'];

    protected $casts = [
        'noted_at'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function guard()
    {
        return $this->belongsTo(User::class, 'guard_user_id')->withTrashed();
    }

    public function gate()
    {
        return $this->belongsTo(Gate::class)->withTrashed();
    }
}
