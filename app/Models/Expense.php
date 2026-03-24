<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facility;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use \Auth;
class Expense extends Model
{
    use HasFactory;
    
    public function building()
    {
        return $this->belongsTo('App\Models\Building')->withTrashed();
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }
    
    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }
    
    
    public function getImageAttribute($value)
    {
        if($value != ''){
            // return Cache::remember("signed_url_{$value}", now()->addMinutes(10), function () use ($value) {
            //     return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(10)); // Expires in 10 min
            // });
            return asset('public/images/'.$value);
        }
    }
    
    public function getImageFilenameAttribute()
    {
        return $this->attributes['image'] ?? null;
    }

    public function getModelNameAttribute()
    {
        if (!empty($this->name)) {
            return $this->name;
        }

        // Prefix the model name with the App\Models\ namespace
        $class = 'App\\Models\\' . $this->model;

        if (class_exists($class)) {
            if($this->model == 'Booking'){
                $instance = Facility::find($this->model_id);
            }else{
                $instance = $class::find($this->model_id);
            }
            return $instance ? ($instance->name ?? $instance->title ?? $this->model_id) : $this->model_id;
        }

        return 'N/A';
    }
    public function event()
{
    return $this->belongsTo(\App\Models\Event::class, 'model_id', 'id')
                ->where('model', 'Event');
}

}
