<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendForgetPasswordEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $info;

    public function __construct($user, $info)
    {
        $this->user = $user;
        $this->info = $info;
    }

    public function handle()
    {
        try {
            Log::info('Trying to send email to ' . $this->user->email);

            Mail::send('email.forget_password2', $this->info, function ($message) {
                $message->to($this->user->email, $this->user->name)
                        ->subject('Forget Password');
            });

            Log::info('Mail sent successfully to ' . $this->user->email);
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
        }
    }
}
