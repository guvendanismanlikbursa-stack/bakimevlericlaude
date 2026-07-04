<?php

namespace App\Mail;

use App\Models\FamilyUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FamilyEmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FamilyUser $family,
        public string $verificationUrl,
        public string $brandName,
    ) {}

    public function build()
    {
        return $this->subject('E-posta Adresinizi Doğrulayın - '.$this->brandName)
            ->view('emails.family-email-verification');
    }
}
