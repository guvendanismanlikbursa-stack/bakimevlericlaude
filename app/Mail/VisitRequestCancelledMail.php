<?php

namespace App\Mail;

use App\Models\VisitRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VisitRequestCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public VisitRequest $visitRequest,
    ) {}

    public function build()
    {
        return $this->subject('Ziyaret Talebiniz Hakkında')
            ->view('emails.visit-request-cancelled');
    }
}
