<?php

namespace App\Mail;

use App\Models\OfferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OfferRequestClosedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OfferRequest $offerRequest,
    ) {}

    public function build()
    {
        return $this->subject('Fiyat Talebiniz Kapatıldı')
            ->view('emails.offer-request-closed');
    }
}
