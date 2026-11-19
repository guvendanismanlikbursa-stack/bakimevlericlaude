<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 19 Kasım 2026: canlı sohbette engellenen ziyaretçiler (tarayıcı kimliği ve/veya telefon numarası).
class ChatBlock extends Model
{
    protected $fillable = ['guest_token', 'phone', 'chat_thread_id', 'admin_id'];

    public static function engelliMi(?string $token, ?string $telefon): bool
    {
        if (! $token && ! $telefon) {
            return false;
        }

        return static::query()->where(function ($q) use ($token, $telefon) {
            if ($token) {
                $q->orWhere('guest_token', $token);
            }
            if ($telefon) {
                $q->orWhere('phone', $telefon);
            }
        })->exists();
    }

    /** Türkiye cep numarasını 05XXXXXXXXX biçimine getirir; geçersizse null. */
    public static function telefonDuzelt(?string $girdi): ?string
    {
        $r = preg_replace('/\D+/', '', (string) $girdi);
        if (str_starts_with($r, '90') && strlen($r) === 12) {
            $r = substr($r, 2);
        }
        if (str_starts_with($r, '0') && strlen($r) === 11) {
            $r = substr($r, 1);
        }

        return preg_match('/^5\d{9}$/', $r) ? '0'.$r : null;
    }
}
