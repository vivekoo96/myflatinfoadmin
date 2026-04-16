<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id',
        'name',
        'category',
        'total_quantity',
        'available_qty',
        'used_qty',
        'damaged_qty',
        'low_stock_threshold',
        'location',
        'purchase_date',
        'vendor_name',
        'vendor_contact',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Asset belongs to Building
     */
    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    /**
     * Relationship: Asset has many AMC records
     */
    public function amcs()
    {
        return $this->hasMany(AssetAmc::class);
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->available_qty <= $this->low_stock_threshold;
    }
}
