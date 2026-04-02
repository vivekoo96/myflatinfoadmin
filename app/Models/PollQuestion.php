<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'question',
        'order',
    ];

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    public function options()
    {
        return $this->hasMany(PollOption::class, 'poll_question_id')->orderBy('order');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class, 'poll_question_id');
    }
}
