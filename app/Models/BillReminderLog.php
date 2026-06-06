<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per (flat, bill type) "pay your pending bill" reminder that was actually
 * sent. Used to (a) throttle re-sends within a cooldown window and (b) show the
 * reminder history on account/reminder-history.
 */
class BillReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'flat_id',
        'bill_type',
        'sent_by',
        'recipients_count',
    ];

    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }

    public function flat()
    {
        return $this->belongsTo('App\Models\Flat')->withTrashed();
    }

    public function sender()
    {
        return $this->belongsTo('App\Models\User', 'sent_by')->withTrashed();
    }
}
