<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoveInOutRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id', 'flat_id', 'user_id', 'type', 'person_type', 
        'first_name', 'last_name', 'email', 'phone', 'id_proof', 
        'date_of_entry_exit', 'from_date', 'to_date', 'passcode', 
        'status', 'comment', 'approved_by', 'visited_at', 'rejected_comment'
    ];

    protected $appends = ['block', 'created'];

    public function getBlockAttribute()
    {
        return $this->flat && $this->flat->block ? $this->flat->block : null;
    }

    public function getCreatedAttribute()
    {
        if ($this->flat && $this->flat->owner) {
            return [
                'id' => $this->flat->owner->id,
                'name' => trim($this->flat->owner->first_name . ' ' . $this->flat->owner->last_name)
            ];
        }
        return null;
    }

    public function building()
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public static function generatePasscode()
    {
        do {
            $passcode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('passcode', $passcode)->where('status', '!=', 'Completed')->exists());

        return $passcode;
    }
}
