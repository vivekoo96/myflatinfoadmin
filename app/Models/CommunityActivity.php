<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id',
        'user_id',
        'flat_id',
        'title',
        'description',
        'activity_datetime',
        'response_type',
        'post_type',
        'max_slots',
    ];

    protected $casts = [
        'activity_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Slot-filling responses per type
    const SLOT_FILLERS = [
        'simple'   => ['interested'],
        'detailed' => ['attending', 'maybe'],
    ];

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function responses()
    {
        return $this->hasMany(CommunityActivityResponse::class);
    }

    /**
     * Count filled slots
     */
    public function filledSlotsCount(): int
    {
        $fillers = self::SLOT_FILLERS[$this->response_type] ?? [];
        return $this->responses()->whereIn('response', $fillers)->count();
    }

    /**
     * Check if all slots are filled
     */
    public function isSlotFull(): bool
    {
        if ($this->post_type === 'unlimited') {
            return false;
        }
        return $this->filledSlotsCount() >= $this->max_slots;
    }
}
