<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * 29 Ekim 2026: kullanıcının talebi - demo site linki. Admin, yayında olmayan
 * siteyi doktora / kuruma WhatsApp'tan gönderebilsin diye tahmin edilemeyen,
 * süreli bir önizleme anahtarı. Linki açan kişi giriş yapmadan demoyu görür;
 * site "yayında" sayılmaz, Google listelemez. Süre dolunca ya da admin
 * "Linki yenile" deyince eski link çalışmaz.
 */
trait HasDemoLink
{
    /** Tasarım anahtarı => panelde ve mesajda görünen ad. */
    public const DEMO_TASARIMLAR = [
        'standart' => 'Standart tasarım',
        'klasik' => 'Profesyonel tasarım (Klasik)',
        'hareketli' => 'Profesyonel tasarım (Hareketli)',
    ];

    public const DEMO_GUN = 30;

    /** Geçerli anahtarı döndürür; yoksa, süresi dolmuşsa ya da $yenile ise yenisini üretir. */
    public function demoToken(bool $yenile = false): string
    {
        if ($yenile || ! $this->demo_token || ! $this->demo_until || $this->demo_until->isPast()) {
            $this->forceFill(['demo_token' => Str::random(32), 'demo_until' => now()->addDays(self::DEMO_GUN)])->saveQuietly();
        }

        return (string) $this->demo_token;
    }

    public function demoValid(mixed $token): bool
    {
        return is_string($token) && $token !== '' && $this->demo_token && $this->demo_until
            && $this->demo_until->isFuture() && hash_equals((string) $this->demo_token, $token);
    }

    /** Demo linki her zaman platform adresinde açılır (alan adı site yayına alınınca devreye girer). */
    public function demoUrl(string $tasarim = ''): string
    {
        $yol = $this->demoPath().$this->slug;
        $taban = app()->environment('local', 'testing') ? url($yol) : 'https://bakimevleri.com'.$yol;

        return $taban.'?demo='.$this->demoToken().(isset(self::DEMO_TASARIMLAR[$tasarim]) ? '&tasarim='.$tasarim : '');
    }

    /** Ziyaretçi bu sitenin geçerli demo linkiyle mi geldi? Anahtar oturuma yazılır, iç sayfalarda da geçerli kalır. */
    public function demoAccess(\Illuminate\Http\Request $request): bool
    {
        $anahtar = 'demo_site_'.class_basename(static::class).'_'.$this->id;
        $token = $request->query('demo');
        if ($this->demoValid($token)) {
            $request->session()->put($anahtar, $token);

            return true;
        }

        return $this->demoValid($request->session()->get($anahtar));
    }

    abstract protected function demoPath(): string;
}
