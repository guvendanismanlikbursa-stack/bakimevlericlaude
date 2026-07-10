<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public ?string $bodyText,
        public ?string $actionUrl,
    ) {}

    public function build()
    {
        return $this->subject($this->title)
            ->view('emails.notification');
    }
}
