<?php

namespace App\Mail;

use App\Models\FacilityRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityRegistrationRevisionRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FacilityRegistration $registration,
        public string $adminNote,
        public string $editUrl,
    ) {}

    public function build()
    {
        return $this->subject('Kurum Kaydı Başvurunuzda Düzeltme Gerekiyor - '.$this->registration->name)
            ->view('emails.facility-registration-revision-requested');
    }
}
