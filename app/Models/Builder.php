<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Builder extends Model
{
    use HasFactory, SoftDeletes;

    public function buildings()
    {
        return $this->hasMany('App\Models\Building');
    }
}
