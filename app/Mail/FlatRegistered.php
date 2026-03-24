<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FlatRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $flat;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($flat, $user)
    {
        $this->flat = $flat;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Flat Registration Confirmation - ' . $this->flat->name)
                    ->view('emails.flat_registered')
                    ->with([
                        'flatName' => $this->flat->name,
                        'blockName' => $this->flat->block->name ?? 'N/A',
                        'area' => $this->flat->area,
                        'corpusFund' => $this->flat->corpus_fund,
                        'livingStatus' => $this->flat->living_status,
                        'status' => $this->flat->status,
                        'buildingName' => $this->flat->building->name ?? 'N/A',
                        'userName' => $this->user->name,
                    ]);
    }
}
