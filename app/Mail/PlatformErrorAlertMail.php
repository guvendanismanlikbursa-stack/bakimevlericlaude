<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// KASITLI OLARAK ShouldQueue implemente ETMEZ - bkz. AdminLoginCodeMail
// ayni gerekce: platformda hicbir mail kuyruklanmiyor (kullanicinin acik
// talebi). record_platform_error() helper'i (app/helpers.php) tarafindan,
// hem genel exception yakalayicidan (bootstrap/app.php) hem de ozel
// kontrol komutlarindan (ör. gallery:check-health) cagirilir - tek, ortak
// "platformda bir hata olustu" mail formati.
//
// 11 Agustos 2026: property adi bilerek '$errorMessage' ('$message' DEGIL) -
// Laravel'in Mailable::buildViewData() metodu view'a OTOMATIK olarak kendi
// 'message' adinda bir Illuminate\Mail\Message nesnesi enjekte ediyor, bu
// bizim public $message property'mizi (view data'sina ayni isimle
// eklenirken) SESSIZCE EZIYORDU - mail hic gonderilmiyordu, sadece
// "htmlspecialchars(): Argument must be string, Message given" hatasiyla
// loglara dusuyordu (admin_alert_email adresine giden ESKI mail mekanizmasi
// bu yeni kod calismadigi icin hic etkilenmedi, bu yuzden fark edilmesi
// kolay degildi).
class PlatformErrorAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $title, public string $errorMessage, public string $source) {}

    public function build()
    {
        return $this->subject('[Hata] '.$this->title)
            ->view('emails.platform-error-alert');
    }
}
