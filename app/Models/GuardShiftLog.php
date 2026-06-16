<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GuardShiftLog extends Model
{
    protected $table = 'guard_shift_logs';

    protected $fillable = [
        'building_id',
        'guard_user_id',
        'gate_id',
        'building_shift_id',
        'assignment_id',
        'shift_date',
        'status',
        'checked_in_at',
        'checked_out_at',
        'late_minutes',
        'early_arrival_minutes',
        'handover_confirmed_by',
        'handover_at',
        'notes',
    ];

    protected $casts = [
        'shift_date'    => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at'=> 'datetime',
        'handover_at'   => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function guardUser()
    {
        return $this->belongsTo(User::class, 'guard_user_id')->withTrashed();
    }

    public function gate()
    {
        return $this->belongsTo(Gate::class, 'gate_id');
    }

    public function buildingShift()
    {
        return $this->belongsTo(BuildingShift::class, 'building_shift_id')->withTrashed();
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function assignment()
    {
        return $this->belongsTo(GuardPatrolAssignment::class, 'assignment_id')->withTrashed();
    }

    public function handoverConfirmedBy()
    {
        return $this->belongsTo(User::class, 'handover_confirmed_by')->withTrashed();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Compute late_minutes from shift start vs actual check-in.
     * Returns 0 if guard was on time or early.
     */
    public function computeLateMinutes(): int
    {
        if (!$this->checked_in_at || !$this->buildingShift) {
            return 0;
        }

        $shiftStart = Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->buildingShift->start_time);
        $checkedIn  = Carbon::parse($this->checked_in_at);

        return max(0, (int) $shiftStart->diffInMinutes($checkedIn, false));
    }

    /**
     * Compute early_arrival_minutes from shift start vs incoming arrival.
     * Returns 0 if they arrived on time or late.
     */
    public function computeEarlyArrival(): int
    {
        if (!$this->checked_in_at || !$this->buildingShift) {
            return 0;
        }

        $shiftStart = Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->buildingShift->start_time);
        $checkedIn  = Carbon::parse($this->checked_in_at);

        return max(0, (int) $checkedIn->diffInMinutes($shiftStart, false));
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->checked_in_at || !$this->checked_out_at) {
            return null;
        }
        return (int) Carbon::parse($this->checked_in_at)->diffInMinutes(Carbon::parse($this->checked_out_at));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'           => 'success',
            'completed'        => 'info',
            'late'             => 'warning',
            'absent'           => 'danger',
            'handover_pending' => 'secondary',
            default            => 'light',
        };
    }
}
