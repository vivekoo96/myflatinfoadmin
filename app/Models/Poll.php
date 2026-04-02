<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Poll extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id',
        'title',
        'description',
        'type',
        'structure',
        'voting_type',
        'status',
        'expiry_date',
        'created_by',
        'created_by_role',
        'result_released_at',
    ];

    protected $casts = [
        'expiry_date'        => 'datetime',
        'result_released_at' => 'datetime',
    ];

    /**
     * Computed display status — returns 'expiring_soon' when poll is active
     * and within 24 hours of expiry.
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'active' && $this->expiry_date) {
            if (Carbon::now()->diffInHours($this->expiry_date, false) <= 24 &&
                Carbon::now()->lt($this->expiry_date)) {
                return 'expiring_soon';
            }
        }
        return $this->status;
    }

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function questions()
    {
        return $this->hasMany(PollQuestion::class)->orderBy('order');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Total unique voters (flat-based = distinct flat_id, user-based = distinct user_id).
     */
    public function getTotalVotersAttribute(): int
    {
        if ($this->voting_type === 'flat_based') {
            return $this->votes()->whereNotNull('flat_id')->distinct()->count('flat_id');
        }
        return $this->votes()->distinct()->count('user_id');
    }
}
