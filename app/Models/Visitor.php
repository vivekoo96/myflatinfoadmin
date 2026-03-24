<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Visitor extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'building_id', 'block_id', 'flat_id', 'user_id',
        'head_name', 'head_phone', 'head_photo',
        'type', 'status', 'total_members', 'stay_from', 'stay_to',
        'visiting_purpose', 'vehicle_number', 'vehicle_type',
        'code', 'invitation_email', 'invitation_sent_at'
    ];
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->with(['owner','tanent'])->withTrashed();
    }
    
    public function block()
    {
        return $this->belongsTo('App\Models\Block')->withTrashed();
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function vehicles()
    {
        return $this->hasMany('App\Models\Vehicle');
    }
    
    public function inouts()
    {
        return $this->hasMany('App\Models\VisitorInout')->withTrashed();
    }
    
    public function getHeadPhotoAttribute($value)
    {
        if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return asset('public/images/'.$value);
        }
        return null;
    }
    
    public function getHeadPhotoFilenameAttribute()
    {
        return $this->attributes['head_photo'] ?? null;
    }

    public function gate_passes()
    {
        return $this->hasMany('App\Models\GatePass')->withTrashed();
    }
    
}
