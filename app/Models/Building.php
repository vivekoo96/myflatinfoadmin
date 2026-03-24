<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory, SoftDeletes;

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function treasurer()
    {
        if($this->treasurer_id == 'NULL'){
            $this->treasurer_id = $this->user_id;
        }
        return $this->belongsTo('App\Models\User','treasurer_id')->withTrashed();
    }

    public function users()
    {
        return $this->hasMany('App\Models\BuildingUser')->withTrashed();
    }
    
    public function owner_and_tenants()
    {
        return $this->hasMany('App\Models\Flat')
            ->with(['owner', 'tanent']);
    }

    public function builder()
    {
        return $this->belongsTo('App\Models\Builder')->withTrashed();
    }
    
    public function blocks()
    {
        return $this->hasMany('App\Models\Block')->withTrashed();
    }
    
    public function flats()
    {
        return $this->hasMany('App\Models\Flat')->withTrashed();
    }
    
    public function gates()
    {
        return $this->hasMany('App\Models\Gate')->withTrashed();
    }
    
    public function city()
    {
        return $this->belongsTo('App\Models\City')->withTrashed();
    }
    
    public function roles()
    {
        $buildingId = $this->id;
    
        return $this->hasMany('App\Models\Role')
            ->withTrashed()
            ->where(function ($query) use ($buildingId) {
                $query->where('building_id', $buildingId)
                      ->where('type', 'issue');
            });
    }
    
    public function common_roles()
    {
        return Role::withTrashed()
            ->where('building_id', 0)
            ->get();
    }
    
    public function custom_roles()
    {
        $buildingId = $this->id;
    
        return $this->hasMany('App\Models\Role')
            ->withTrashed()
            ->where(function ($query) use ($buildingId) {
                $query->where('building_id', $buildingId)
                      ->where('type', 'custom');
            });
    }
    
    public function noticeboards()
    {
        return $this->hasMany('App\Models\Noticeboard')->withTrashed();
    }
    
    public function events()
    {
        return $this->hasMany('App\Models\Event')->withTrashed();
    }
    
    public function classifieds()
    {
        return $this->hasMany('App\Models\Classified')->withTrashed();
    }
    
    public function issues()
    {
        return $this->hasMany('App\Models\Issue')->withTrashed();
    }
    
    public function facilities()
    {
        return $this->hasMany('App\Models\Facility')->withTrashed();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'building_permissions', 'building_id', 'permission_id');
    }
    
    public function hasPermission($permissionName)
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    
    public function visitors()
    {
        return $this->hasMany('App\Models\Visitor')->withTrashed();
    }
    
    public function vehicles()
    {
        return $this->hasMany('App\Models\Vehicle')->withTrashed();
    }
    
    public function guards()
    {
        return $this->hasMany('App\Models\Guard')->withTrashed();
    }
    
    public function maintenances()
    {
        return $this->hasMany('App\Models\Maintenance')->withTrashed();
    }
    
    public function essentials()
    {
        return $this->hasMany('App\Models\Essential')->withTrashed();
    }
    
    public function visitor_inouts()
    {
        return $this->hasMany('App\Models\VisitorInout')->withTrashed();
    }
    
    public function vehicle_inouts()
    {
        return $this->hasMany('App\Models\VehicleInout')->withTrashed();
    }
    
    public function getImageAttribute($value)
    {
        if($value != ''){
            return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
                return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            });
        }

    }
    
    public function getImageFilenameAttribute()
    {
        return $this->attributes['image'] ?? null;
    }
    
    public function departments()
    {
        return $this->hasMany('App\Models\Role')->withTrashed();
    }

    public function parkings()
    {
        return $this->hasMany('App\Models\Parking')->withTrashed();
    }
    
    public function expenses()
    {
        return $this->hasMany('App\Models\Expense');
    }

    public function bookings()
    {
        return $this->hasMany('App\Models\Booking');
    }
    
}
