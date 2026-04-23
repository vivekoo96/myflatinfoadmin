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
        'status', 'comment', 'approved_by', 'visited_at'
    ];

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
