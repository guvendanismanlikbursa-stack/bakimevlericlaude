<?php

namespace App\Mail;

use App\Models\Facility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facility $facility,
        public string $email,
        public string $brandName,
        public string $loginUrl,
    ) {}

    public function build()
    {
        return $this->subject('Sistemi En İyi Şekilde Kullanmak İçin Rehber - '.$this->brandName)
            ->view('emails.facility-welcome');
    }
}
