<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flat extends Model
{
    use HasFactory,SoftDeletes;
    protected $appends = ['current_user'];

    // ...existing code...

    // Get all bookings for this flat (owner and tenant)
    public function bookings()
    {
        $ownerBookings = $this->owner && method_exists($this->owner, 'bookings') ? $this->owner->bookings()->get() : collect();
        $tenantBookings = $this->tanent && method_exists($this->tanent, 'bookings') ? $this->tanent->bookings()->get() : collect();
        // Merge and sort by date desc
        return $ownerBookings->merge($tenantBookings)->sortByDesc('date');
    }
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function block()
    {
        return $this->belongsTo('App\Models\Block')->withTrashed();
    }
    
    public function getCurrentUserAttribute()
    {
        if ($this->living_status === 'Owner') {
            return $this->owner;
        } elseif ($this->living_status === 'Tenant') {
            return $this->tanent;
        }
    
        return null;
    }
        
    public function owner()
    {
        return $this->belongsTo('App\Models\User','owner_id')->withTrashed();
    }
    
    public function tanent()
    {
        return $this->belongsTo('App\Models\User','tanent_id')->withTrashed();
    }
    


    public function family_members()
    {
        return $this->hasMany('App\Models\FamilyMember');
    }
    
    public function parcels()
    {
        return $this->hasMany('App\Models\Parcel')->withTrashed();
    }
    
    public function vehicles()
    {
        return $this->hasMany('App\Models\Vehicle');
    }
    
    public function visitors()
    {
        return $this->hasMany('App\Models\Visitor')->withTrashed();
    }
    
    public function visitor_inouts()
    {
        return $this->hasMany('App\Models\VisitorInout')->withTrashed();
    }
    
    public function maintenance_payments()
    {
        return $this->hasMany('App\Models\MaintenancePayment')->withTrashed();
    }
    
    public function essential_payments()
    {
        return $this->hasMany('App\Models\EssentialPayment')->withTrashed();
    }

    public function parkings()
    {
        return $this->belongsToMany(Parking::class, 'flat_parkings', 'flat_id', 'parking_id')->withTrashed()->withPivot('id');
    }
    public function classifieds()
{
    return $this->hasMany(Classified::class);
}

    
}
