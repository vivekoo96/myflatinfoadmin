<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Poll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CloseExpiredPolls extends Command
{
    protected $signature   = 'polls:close-expired';
    protected $description = 'Auto-close polls whose expiry_date has passed and are still active';

    public function handle()
    {
        $now = Carbon::now();

        $expired = Poll::whereIn('status', ['active'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $now)
            ->get();

        foreach ($expired as $poll) {
            $poll->status = 'closed';
            $poll->save();

            Log::info('Poll auto-closed by scheduler', [
                'poll_id'     => $poll->id,
                'title'       => $poll->title,
                'expiry_date' => $poll->expiry_date->toDateTimeString(),
            ]);
        }

        $this->info("Closed {$expired->count()} expired poll(s).");
    }
}
