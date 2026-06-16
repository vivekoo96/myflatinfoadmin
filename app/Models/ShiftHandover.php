<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftHandover extends Model
{
    protected $table = 'shift_handovers';

    protected $fillable = [
        'building_id',
        'gate_id',
        'building_shift_id',
        'shift_date',
        'outgoing_guard_id',
        'incoming_guard_id',
        'outgoing_log_id',
        'incoming_log_id',
        'incoming_arrived_at',
        'outgoing_confirmed_at',
        'incoming_confirmed_at',
        'status',
        'late_minutes',
        'notes',
    ];

    protected $casts = [
        'shift_date'            => 'date',
        'incoming_arrived_at'   => 'datetime',
        'outgoing_confirmed_at' => 'datetime',
        'incoming_confirmed_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function outgoingGuard()
    {
        return $this->belongsTo(User::class, 'outgoing_guard_id')->withTrashed();
    }

    public function incomingGuard()
    {
        return $this->belongsTo(User::class, 'incoming_guard_id')->withTrashed();
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

    public function outgoingLog()
    {
        return $this->belongsTo(GuardShiftLog::class, 'outgoing_log_id');
    }

    public function incomingLog()
    {
        return $this->belongsTo(GuardShiftLog::class, 'incoming_log_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed'        => 'success',
            'pending_outgoing' => 'warning',
            'pending_incoming' => 'secondary',
            default            => 'light',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_incoming' => 'Waiting for Incoming Guard',
            'pending_outgoing' => 'Waiting for Outgoing Guard to Confirm',
            'completed'        => 'Completed',
            default            => ucfirst($this->status),
        };
    }
}
