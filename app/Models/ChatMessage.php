<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_thread_id', 'sender_type', 'sender_admin_id', 'body',
        'attachment_path', 'attachment_type', 'attachment_mime', 'attachment_size', 'delivered_at', 'read_at',
    ];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'read_at' => 'datetime'];
    }

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function senderAdmin()
    {
        return $this->belongsTo(Admin::class, 'sender_admin_id');
    }

    /**
     * 17 Kasım 2026: WhatsApp tarzı tikler. Karşı tarafın ekranı mesajları çektiğinde "iletildi",
     * sohbet penceresi açık ve görünürken çektiğinde ayrıca "okundu" işaretlenir.
     * $gonderen: işaretlenecek mesajları GÖNDEREN taraf ('guest' ya da 'admin'); $threadId null ise tüm sohbetler.
     */
    public static function alindiIsaretle(?int $threadId, string $gonderen, bool $okundu): void
    {
        $sorgu = fn () => static::query()->where('sender_type', $gonderen)
            ->when($threadId, fn ($q) => $q->where('chat_thread_id', $threadId));

        $sorgu()->whereNull('delivered_at')->update(['delivered_at' => now()]);
        if ($okundu) {
            $sorgu()->whereNull('read_at')->update(['read_at' => now()]);
        }
    }

    /**
     * Bir sohbette $gonderen tarafın mesajlarından karşı tarafa en son iletilen ve en son okunan mesajın id'si.
     * Mesajlar sırayla çekildiği için "bu id'ye kadar olanların hepsi iletildi/okundu" anlamına gelir.
     *
     * @return array{iletilen: int, okunan: int}
     */
    public static function alindiDurumu(int $threadId, string $gonderen): array
    {
        $sorgu = fn () => static::query()->where('chat_thread_id', $threadId)->where('sender_type', $gonderen);

        return [
            'iletilen' => (int) $sorgu()->whereNotNull('delivered_at')->max('id'),
            'okunan' => (int) $sorgu()->whereNotNull('read_at')->max('id'),
        ];
    }
}
