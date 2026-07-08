<?php

namespace App\Mail;

use App\Models\FacilityUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityPasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FacilityUser $user,
        public string $resetUrl,
        public string $brandName,
    ) {}

    public function build()
    {
        return $this->subject('Şifre Sıfırlama - '.$this->brandName)
            ->view('emails.facility-password-reset');
    }
}
