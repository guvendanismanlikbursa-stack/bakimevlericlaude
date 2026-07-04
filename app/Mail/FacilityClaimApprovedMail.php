<?php

namespace App\Mail;

use App\Models\Facility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityClaimApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facility $facility,
        public string $email,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function build()
    {
        return $this->subject('Kurum Sahiplenme Başvurunuz Onaylandı - '.$this->facility->name)
            ->view('emails.facility-claim-approved');
    }
}
