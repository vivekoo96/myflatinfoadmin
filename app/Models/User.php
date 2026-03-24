<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use \Str;

use \DB;

class User extends Authenticatable
   
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'password', 'gender', 
        'city_id', 'address', 'company_name', 'status', 'image', 'role',
        'created_by', 'created_type', 'fcm_token', 'device_type'
    ];

    protected $hidden = [
        'password', 'remember_token','api_token','otp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function sent_notifications()
    {
        return $this->hasMany('App\Models\Notification','from_id');
    }
    
    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
public function getPhotoAttribute($value)
    {
        if($value != ''){
            // Check if image exists in public/images/ directory (old location)
            $file_path = public_path('images/' . $value);
            if (is_file($file_path)) {
                return asset('public/images/'.$value);
            }
            
            // Check if image exists in storage/app/public/user_images/ directory (new location via Storage facade)
            $storage_path = public_path('storage/user_images/' . $value);
            if (is_file($storage_path)) {
                return asset('storage/user_images/'.$value);
            }
            
            // Fallback: try to construct URL from value (handles both paths)
            if (strpos($value, 'user_images/') !== false) {
                // Value already contains user_images/ path
                return asset('storage/' . $value);
            } else {
                // Try Storage disk - check if file exists in user_images directory
                if (Storage::disk('public')->exists('user_images/' . $value)) {
                    return asset('storage/user_images/' . $value);
                }
                // Fallback to old public/images path
                return asset('public/images/'.$value);
            }
        }else{
        //   dd($this->gender);
            if($this->gender == 'Female'){
                return asset('public/images/profiles/female.jpeg');
            }
            else{
                return asset('public/images/profiles/male.png');
            }
        }
        
        
    }
    
    public function buildings()
    {
        // Handle both old relationship (owned buildings) and new array format (assigned buildings)
        $owned = $this->hasMany('App\Models\Building')->get();
        
        // Get buildings from building_id array
        $buildingIds = $this->building_id ?: [];
        $assigned = \App\Models\Building::whereIn('id', $buildingIds)->get();
        
        // Merge and remove duplicates
        return $owned->merge($assigned)->unique('id')->values();
    }
    
    public function allBuildings()
    {
        // Buildings created/owned by user
        $owned = $this->hasMany('App\Models\Building')->get();
    
        // Buildings from building_users relationship
        $assigned = $this->departments()
            ->with('building') // assuming BuildingUser has `building()` relationship
            ->get()
            ->pluck('building')
            ->filter(); // remove nulls if any
    
        // Merge both collections and remove duplicates (by ID)
        return $owned->merge($assigned)->unique('id')->values();
    }

    public function vehicles()
    {
        return $this->hasMany('App\Models\Vehicle');
    }
    
    public function departments()
    {
        return $this->hasMany('App\Models\BuildingUser')->with('role');
    }
    
    public function hasSelectedRole($slug)
    {
        if(!$this->selectedRole){
            return false;
        }
        return $this->selectedRole->slug === $slug;
    }
    
    public function hasRole($slug)
    {
        // Filter departments by current building context (from session or Auth user)
        $currentBuildingId = session('current_building_id') ?? $this->building_id;
        
        return $this->departments->contains(function ($department) use ($slug, $currentBuildingId) {
            return $department->building_id == $currentBuildingId && 
                   $department->role && 
                   $department->role->slug === $slug;
        });
    }
    
    public function hasAnyRole()
    {
        // Filter departments by current building context (from session or Auth user)
        $currentBuildingId = session('current_building_id') ?? $this->building_id;
        
        return $this->departments->contains(function ($department) use ($currentBuildingId) {
            return $department->building_id == $currentBuildingId && 
                   $department->role !== null;
        });
    }
    
    public function hasAnyCustomRole()
    {
        // Filter departments by current building context (from session or Auth user)
        $currentBuildingId = session('current_building_id') ?? $this->building_id;
        
        return $this->departments->contains(function ($department) use ($currentBuildingId) {
            return $department->building_id == $currentBuildingId && 
                   $department->role && 
                   $department->role->type == 'custom';
        });
    }
    
    public function hasAnyIssueRole()
    {
        // Filter departments by current building context (from session or Auth user)
        $currentBuildingId = session('current_building_id') ?? $this->building_id;
        
        return $this->departments->contains(function ($department) use ($currentBuildingId) {
            return $department->building_id == $currentBuildingId && 
                   $department->role && 
                   $department->role->type == 'issue';
        });
    }
    
    public function hasPermission($slug)
    {
       // If user has a selected role, check only that role's permissions
        if ($this->selectedRole) {
            return $this->selectedRole->hasPermission($slug);
        }
        
        // Fallback: check if user has the permission in any role (for backward compatibility)
        // Filter by current building context (from session or Auth user)
        $currentBuildingId = session('current_building_id') ?? $this->building_id;
        
        // Get all roles for this user in the current building
        $roles = $this->departments()
            ->where('building_id', $currentBuildingId)
            ->with('role.permissions')
            ->get()
            ->pluck('role')
            ->filter();
        
        // Check if any of the user's roles has this permission
        foreach ($roles as $role) {
            if ($role && $role->hasPermission($slug)) {
                return true;
            }
        }
        
        return false;
    }

    public function parcels()
    {
        return $this->hasMany('App\Models\Parcel')->withTrashed();
    }

    public function visitors()
    {
        return $this->hasMany('App\Models\Visitor')->withTrashed();
    }
    
    public function department()
    {
        return $this->belongsTo('App\Models\BuildingUser')->withTrashed();
    }
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building');
    }
    
    public function selectedRole()
    {
        return $this->belongsTo('App\Models\Role', 'selected_role_id');
    }
    
    public function flat()
    {
        return $this->belongsTo('App\Models\Flat');
    }
    
               public function belongFlatOwner()
            {
                return $this->hasOne(Flat::class, 'owner_id', 'id');
            }
            
                public function belongFlatTanent()
            {
                return $this->hasOne(Flat::class, 'tanent_id', 'id');
            }
    
    
    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }
    
    public function gate()
    {
        return $this->belongsTo('App\Models\Gate');
    }
    
    public function getPhotoFilenameAttribute()
    {
        return $this->attributes['photo'];
    }
    
    private function generateUniqueString($length = 8)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    public function getEmailAttribute($value)
    {
        return strtolower($value);
    }

     public function bookings()
    {
        return $this->hasMany('App\Models\Booking');
    }

    public function getAssignedBlocksModels()
    {
        $blockIds = $this->getAssignedBlocksArray();
        
        if (empty($blockIds)) {
            return collect();
        }
        
        return \App\Models\Block::whereIn('id', $blockIds)
                               ->where('status', 'Active')
                               ->get();
    }

    public function getAssignedBlocksArray()
    {
        if (!$this->attributes['assigned_blocks']) {
            return [];
        }
        
        $value = $this->attributes['assigned_blocks'];
        
        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }
        
        // If it's a JSON string, decode it
        return json_decode($value, true) ?: [];
    }

    public function getAssignedBlocksAttribute($value)
    {
        if (!$value) {
            return [];
        }
        
        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }
        
        // If it's a JSON string, decode it
        return json_decode($value, true) ?: [];
    }

    public function setAssignedBlocksAttribute($value)
    {
        $this->attributes['assigned_blocks'] = is_array($value) ? json_encode($value) : $value;
    }


}
