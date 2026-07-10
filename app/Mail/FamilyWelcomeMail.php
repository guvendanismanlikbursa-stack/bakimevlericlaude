<?php

namespace App\Mail;

use App\Models\FamilyUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FamilyWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public FamilyUser $family,
        public string $brandName,
        public string $panelUrl,
    ) {}

    public function build()
    {
        return $this->subject($this->brandName.'ye Hoş Geldiniz')
            ->view('emails.family-welcome');
    }
}
