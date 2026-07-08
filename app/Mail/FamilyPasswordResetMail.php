<?php

namespace App\Mail;

use App\Models\FamilyUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FamilyPasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FamilyUser $family,
        public string $resetUrl,
        public string $brandName,
    ) {}

    public function build()
    {
        return $this->subject('Şifre Sıfırlama - '.$this->brandName)
            ->view('emails.family-password-reset');
    }
}
