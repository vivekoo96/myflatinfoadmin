<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_question_id',
        'option_text',
        'order',
    ];

    public function question()
    {
        return $this->belongsTo(PollQuestion::class, 'poll_question_id');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class, 'poll_option_id');
    }
}
