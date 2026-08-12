<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// 30 Temmuz 2026: admin panelindeki Iletisim Mesajlari ekranindan dogrudan
// cevap yazilabilmesi icin - bkz. Admin\ContactMessageController::reply().
class ContactMessageReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $brandName,
    ) {}

    public function build()
    {
        return $this->subject('Mesajınıza Yanıt — '.$this->brandName)
            ->view('emails.contact-message-reply');
    }
}
