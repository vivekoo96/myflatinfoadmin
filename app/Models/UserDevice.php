<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDevice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_type',
        'device_unique_id',
        'device_model',
        'device_os',
        'app_version',
        'lat',
        'lng',
        'ip_address',
        'user_agent',
        'last_login_at',
        'is_active',
        'current_flat_id'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function currentFlat()
    {
        return $this->belongsTo(Flat::class, 'current_flat_id');
    }
    public static function generateDeviceUniqueId($request)
    {
        $deviceInfo = [
            'device_model' => $request->device_model ?? 'unknown',
            'device_os' => $request->device_os ?? 'unknown',
            'user_agent' => $request->header('User-Agent') ?? 'unknown',
            'ip_address' => $request->ip(),
            'timestamp' => now()->timestamp
        ];
        
        // Create a unique hash based on device information
        $deviceString = implode('|', $deviceInfo);
        return 'device_' . hash('sha256', $deviceString);
    }
}
