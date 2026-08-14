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
        // 16 Temmuz 2026: mail icerigi kurumun turune (yasli-bakim/cocuk/
        // rehabilitasyon) gore ipucu gostersin diye - config/brands.php'deki
        // service_sections zaten bu 3 bolum icin profile_fields/features
        // tutuyor (bkz. facility profil formu, ayni veriyi kullanir), burada
        // sadece kurumun kategorisinden bolumu bulup view'a geciriyoruz.
        $section = $this->facility->category
            ? service_section_for_scope($this->facility->category->brand_scope)
            : null;

        return $this->from(config('mail.from.address'), $this->brandName)
            ->subject('Sistemi En İyi Şekilde Kullanmak İçin Rehber - '.$this->brandName)
            ->view('emails.facility-welcome', ['section' => $section]);
    }
}
