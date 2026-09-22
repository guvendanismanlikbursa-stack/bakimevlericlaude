<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// 22 Eylul 2026: kullanicinin talebi - abonelik ozelligi, NotifyExpiringSubscriptions
// komutu tarafindan kullanilir. KASITLI OLARAK ShouldQueue implemente ETMEZ
// (bkz. PlatformErrorAlertMail ayni gerekce). Property adi bilerek '$digestBody'
// ('$message' DEGIL) - Laravel Mailable::buildViewData() 'message' adini
// otomatik enjekte ediyor, ayni isimli bir property SESSIZCE eziliyor
// (bkz. PlatformErrorAlertMail'deki ayni tarihli yorum, daha once yasanmis gercek hata).
class SubscriptionExpiryDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $digestBody) {}

    public function build()
    {
        return $this->subject('[Abonelik] Süresi yaklaşan/dolan kurumlar')
            ->view('emails.subscription-expiry-digest');
    }
}
