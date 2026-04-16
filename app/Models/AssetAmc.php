<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AssetAmc extends Model
{
    use HasFactory;

    protected $table = 'asset_amcs';

    protected $fillable = [
        'building_id',
        'asset_id',
        'provider_name',
        'start_date',
        'end_date',
        'service_frequency',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: AMC belongs to Asset
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get AMC status: active or expired
     */
    public function getStatusAttribute(): string
    {
        return $this->end_date->gte(now()) ? 'active' : 'expired';
    }

    /**
     * Check if AMC is due for renewal (within 30 days)
     */
    public function isDueForRenewal(): bool
    {
        return $this->end_date->gte(now()) && $this->end_date->lte(now()->addDays(30));
    }

    /**
     * Calculate days remaining for AMC
     */
    public function daysRemaining(): int
    {
        return $this->end_date->diffInDays(now());
    }
}
