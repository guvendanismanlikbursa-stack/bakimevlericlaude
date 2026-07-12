<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// KASITLI OLARAK ShouldQueue implemente ETMEZ - digerlerinin aksine (bkz.
// notify_user()) bu kod giris sirasinda kullanicinin BEKLEDIGI bir sey;
// arka plan kuyruguna girip cron'un bir sonraki dakikasini beklemesi kotu
// bir deneyim olur. Admin girisleri seyrek oldugu icin bu senkron
// gonderim, kuyruk sistemine geri donmedigimiz anlamina gelmiyor.
class AdminLoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function build()
    {
        return $this->subject('Admin giriş kodunuz: '.$this->code)
            ->view('emails.admin-login-code');
    }
}
