<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
        self::syncCacheForgetToOtherDomains($key);
    }

    /**
     * 1 Eylul 2026: kullanicinin bildirdigi gercek hata - 3 marka AYNI
     * veritabanini paylasiyor ama HER BIRININ KENDI AYRI (dosya tabanli)
     * cache'i var (CACHE_STORE=file) - bkz. CrossDomainImageSync ayni
     * kokten sorun icin ayni tarihli aciklama. Yukaridaki Cache::forget()
     * SADECE bu isteği isleyen sunucunun yerel cache'ini temizler; admin
     * bir ayari (ör. banka IBAN'i, teklif ucreti, WhatsApp numarasi) bir
     * domain'den degistirdiginde, diger 2 domain o degeri SURESIZ eski
     * haliyle gostermeye devam ederdi. FacilityImageSyncController ile
     * AYNI paylasilan-sifre (Bearer token) deseniyle, diger 2 domain'e
     * senkron olarak "bu ayarin cache'ini unut" istegi atilir.
     */
    private static function syncCacheForgetToOtherDomains(string $key): void
    {
        if (app()->environment(['local', 'testing']) || app()->runningInConsole()) {
            return;
        }

        $secret = (string) config('platform.ops_secret');
        if ($secret === '') {
            return;
        }

        $currentHost = request()->getHost();
        $domains = [];
        foreach (config('brands.brands') as $brand) {
            $domain = $brand['domains'][0] ?? null;
            if ($domain && str_ends_with($domain, '.com')) {
                $domains[$domain] = true;
            }
        }
        unset($domains[$currentHost]);

        foreach (array_keys($domains) as $domain) {
            try {
                \Illuminate\Support\Facades\Http::withToken($secret)
                    ->timeout(10)
                    ->post("https://{$domain}/_internal/ayar-onbellek-temizle", ['key' => $key])
                    ->throw();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Ayar cache temizleme {$domain} adresine senkronize edilemedi: ".$e->getMessage(), ['key' => $key]);
                notify_admin_of_exception($e);
            }
        }
    }
}
