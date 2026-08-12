<?php

namespace App\Mail;

use App\Models\FacilityClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityClaimRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FacilityClaim $claim,
    ) {}

    public function build()
    {
        return $this->subject('Sahiplenme Başvurunuz Hakkında - '.$this->claim->facility->name)
            ->view('emails.facility-claim-rejected');
    }
}
