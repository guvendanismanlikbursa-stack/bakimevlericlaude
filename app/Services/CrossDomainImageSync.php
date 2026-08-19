<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 19 Agustos 2026: kullanicinin acik talebi - "herhangi bir siteden görsel
 * yüklendiğinde aynı anda tüm sitelerede yüklenmeli silindiğinde tüm
 * sitelerden silinmeli", "yükleme ve silme işlemi bütün kullanıcılar için
 * aynı şekilde işlemeli" (admin VE kurum yetkilisi, hangi domain'den
 * olursa olsun). Bkz. FacilityImageSyncController - bu servis o ucu
 * SENKRON (ayni HTTP istegi icinde, kuyruga atmadan) cagirir. BILEREK
 * kuyruklanmadi: 'jobs' tablosu 3 domain arasinda PAYLASILIYOR, bir domain
 * uzerinde tetiklenen kuyruk isini BASKA bir domain'in queue:work'u
 * yakalayip calistirabilir - o domain'in DISK'inde kaynak dosya hic
 * olmayacagi icin (senkronizasyonun TAM COZMEYE calistigi sorunun AYNISI)
 * is basarisiz olurdu. Senkron cagri bu riski tamamen ortadan kaldirir.
 */
class CrossDomainImageSync
{
    public function syncStore(string $path): void
    {
        $bytes = Storage::disk('public')->get($path);
        if ($bytes === null) {
            return;
        }

        foreach ($this->otherDomains() as $domain) {
            try {
                \Illuminate\Support\Facades\Http::withToken($this->secret())
                    ->timeout(15)
                    ->attach('file', $bytes, basename($path))
                    ->post("https://{$domain}/_internal/kurum-gorseli-sync", ['path' => $path])
                    ->throw();
            } catch (\Throwable $e) {
                Log::warning("Kurum gorseli {$domain} adresine senkronize edilemedi: ".$e->getMessage(), ['path' => $path]);
                notify_admin_of_exception($e);
            }
        }
    }

    public function syncDelete(string $path): void
    {
        foreach ($this->otherDomains() as $domain) {
            try {
                \Illuminate\Support\Facades\Http::withToken($this->secret())
                    ->timeout(15)
                    ->post("https://{$domain}/_internal/kurum-gorseli-sil", ['path' => $path])
                    ->throw();
            } catch (\Throwable $e) {
                Log::warning("Kurum gorseli silme {$domain} adresine senkronize edilemedi: ".$e->getMessage(), ['path' => $path]);
                notify_admin_of_exception($e);
            }
        }
    }

    /**
     * Su an isteği islemekte olan domain HARIC, diger 2 marka domain'i.
     * local/testing ortaminda hicbir domain'e cikilmaz (bos donuyor).
     */
    private function otherDomains(): array
    {
        if (app()->environment('local', 'testing')) {
            return [];
        }

        $currentHost = request()->getHost();
        $all = [];
        foreach (config('brands.brands') as $brand) {
            $domain = $brand['domains'][0] ?? null;
            if ($domain && str_ends_with($domain, '.com')) {
                $all[$domain] = true;
            }
        }

        return array_keys(array_diff_key($all, [$currentHost => true]));
    }

    private function secret(): string
    {
        return (string) config('platform.ops_secret');
    }
}
