<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// 13 Temmuz 2026: yedekler bugune kadar sadece sunucunun kendi diskinde
// tutuluyordu (offsite degildi) - disk/hesap kaybi durumunda yedek de
// veriyle birlikte giderdi. Bu mail, gunluk yedegi mevcut mail altyapisi
// uzerinden ayrica bir posta kutusuna da (sunucu disina) tasir.
class BackupCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $filename,
        public string $sizeLabel,
        public ?string $attachmentPath = null,
        public ?string $filesZipPath = null,
    ) {}

    public function build()
    {
        $mail = $this->subject('Bakım Platformu - Günlük Veritabanı Yedeği: '.$this->filename)
            ->view('emails.backup-created');

        if ($this->attachmentPath) {
            $mail->attach($this->attachmentPath, ['as' => $this->filename, 'mime' => 'application/gzip']);
        }

        if ($this->filesZipPath) {
            $mail->attach($this->filesZipPath, ['as' => basename($this->filesZipPath), 'mime' => 'application/zip']);
        }

        return $mail;
    }
}
