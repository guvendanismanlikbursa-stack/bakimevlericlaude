<?php

namespace App\Mail;

use App\Models\FacilityRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityRegistrationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FacilityRegistration $registration,
    ) {}

    public function build()
    {
        return $this->subject('Kurum Kayıt Başvurunuz Hakkında - '.$this->registration->name)
            ->view('emails.facility-registration-rejected');
    }
}
