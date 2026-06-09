<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs';

    protected $fillable = [
        'building_id',
        'staff_id',
        'name',
        'photo',
        'phone',
        'address',
        'type',
        'category',
        'is_open_to_all',
        'document_verification',
        'noc_police',
        'document_status',
        'status',
        'creator_id',
        'creator_type',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function tags()
    {
        return $this->hasMany(StaffTag::class, 'staff_id');
    }

    /** Current flat assignment (most recent tag). orderByDesc keeps the latest
     *  even when eager-loaded (version-safe; avoids latestOfMany() which needs L8.42+). */
    public function activeTag()
    {
        return $this->hasOne(StaffTag::class, 'staff_id')->orderByDesc('id');
    }

    /** Full public URL for the photo (stored as a relative path). */
    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset($this->photo) : null;
    }

    /** Full public URL for the uploaded verification document. */
    public function getDocumentUrlAttribute()
    {
        return $this->document_verification ? asset($this->document_verification) : null;
    }

    /** Full public URL for the optional police NOC. */
    public function getNocUrlAttribute()
    {
        return $this->noc_police ? asset($this->noc_police) : null;
    }

    public function attendanceLogs()
    {
        return $this->hasMany(StaffAttendance::class, 'staff_id');
    }
}
