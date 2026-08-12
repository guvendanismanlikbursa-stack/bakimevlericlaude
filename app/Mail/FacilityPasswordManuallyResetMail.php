<?php

namespace App\Mail;

use App\Models\FacilityUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// 30 Temmuz 2026: admin panelinden, onay anindan BAGIMSIZ olarak (mail
// gecikmis/hic gitmemis vb. durumlarda) bir kurum yetkilisinin sifresini
// yeniden ureten "Sifre Sifirla" aksiyonunun maili - bkz.
// Admin\UserController::resetFacilityUserPassword().
class FacilityPasswordManuallyResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FacilityUser $facilityUser,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function build()
    {
        return $this->subject('Kurum Paneli Giriş Bilgileriniz Güncellendi')
            ->view('emails.facility-password-manually-reset');
    }
}
