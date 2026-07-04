<?php

namespace App\Mail;

use App\Models\FacilityUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityEmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FacilityUser $user,
        public string $verificationUrl,
        public string $brandName,
    ) {}

    public function build()
    {
        return $this->subject('E-posta Doğrulaması - '.$this->brandName)
            ->view('emails.facility-email-verification');
    }
}
