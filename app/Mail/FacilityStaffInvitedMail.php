<?php

namespace App\Mail;

use App\Models\Facility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// 12 Agustos 2026: kullanicinin talebi - "ekibime hesap acamiyorum". Kurum
// sahibi (owner) yeni bir personel hesabi ekledikce bu mail gecici sifreyi
// iletir - bkz. Facility\TeamController::store().
class FacilityStaffInvitedMail extends Mailable implements ShouldQueue
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
        return $this->subject('Kurum Panelinize Ekip Üyesi Olarak Eklendiniz - '.$this->facility->name)
            ->view('emails.facility-staff-invited');
    }
}
