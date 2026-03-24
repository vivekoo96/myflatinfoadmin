<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Noticeboard extends Model
{
    use HasFactory,SoftDeletes;
    
        protected $fillable = [
            'building_id',
            'title',
            'desc',
            'from_time',
            'to_time',
            'from_notified_at',
            'status',
            'block_ids',
            'is_all_blocks',
        ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'from_time' => 'datetime',
        'to_time' => 'datetime',
        'from_notified_at' => 'datetime',
        'is_all_blocks' => 'boolean',
    ];
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function block()
    {
        return $this->belongsTo('App\Models\Block')->withTrashed();
    }
    
    public function blocks()
    {
        return $this->belongsToMany('App\Models\Block', 'noticeboard_blocks', 'noticeboard_id', 'block_id')->withTrashed();
    }
    
}
