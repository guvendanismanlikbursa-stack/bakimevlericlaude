<?php

namespace App\Support;

use App\Models\City;
use App\Models\Expert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 25 Ekim 2026: kullanıcının talebi - "Doktorlar bölümü için maksimum görünürlük".
 * İl / ilçe / branş açılış sayfaları ("Nilüfer Çocuk Doktorları") ve doktor profil
 * sayfaları için ortak bilgiler. Asıl adres her zaman bakimevleri.com'dur: aynı doktor
 * sayfaları bakimevibul / bakimeviara'da da açılır ama Google'a tek adres bildirilir.
 */
class DoktorSeo
{
    /** Branş adresi => [başlık (DB branch), etiket (DB branch_label), çoğul ad]. */
    public const BRANSLAR = [
        'pediatri' => ['title' => 'Pediatri', 'label' => 'Çocuk Doktoru', 'cogul' => 'Çocuk Doktorları'],
        'dis-hekimligi' => ['title' => 'Diş Hekimliği', 'label' => 'Diş Doktoru', 'cogul' => 'Diş Doktorları'],
        'geriatri' => ['title' => 'Geriatri', 'label' => 'Yaşlılık Uzmanı', 'cogul' => 'Geriatri Uzmanları'],
        'fiziksel-tip-ve-rehabilitasyon' => ['title' => 'Fiziksel Tıp ve Rehabilitasyon', 'label' => 'Fizik Tedavi Doktoru', 'cogul' => 'Fizik Tedavi Doktorları'],
        'cocuk-norolojisi' => ['title' => 'Çocuk Nörolojisi', 'label' => 'Çocuk Beyin ve Sinir Hastalıkları', 'cogul' => 'Çocuk Nörolojisi Uzmanları'],
        'cocuk-psikiyatrisi' => ['title' => 'Çocuk Psikiyatrisi', 'label' => 'Çocuk Gelişim Uzmanı', 'cogul' => 'Çocuk Psikiyatristleri'],
        'noroloji' => ['title' => 'Nöroloji', 'label' => 'Beyin ve Sinir Hastalıkları', 'cogul' => 'Nöroloji Uzmanları'],
        'ortopedi' => ['title' => 'Ortopedi', 'label' => 'Kemik ve Eklem Hastalıkları', 'cogul' => 'Ortopedi Uzmanları'],
    ];

    public const ANA_ALAN = 'https://bakimevleri.com';

    public static function asilAdres(string $yol): string
    {
        return self::ANA_ALAN.'/'.ltrim($yol, '/');
    }

    /** Doktorun branşının adres karşılığı (yoksa null). */
    public static function bransSlug(?string $branch, ?string $label): ?string
    {
        foreach (self::BRANSLAR as $slug => $b) {
            if ($branch === $b['title'] || $label === $b['label']) {
                return $slug;
            }
        }

        return null;
    }

    public static function ilceSlug(?string $ilce): ?string
    {
        return Str::slug((string) $ilce) ?: null;
    }

    /** Sorguya branş filtresi uygular (liste sayfasındaki filtreyle aynı kural). */
    public static function bransFiltresi($sorgu, string $slug)
    {
        $b = self::BRANSLAR[$slug];

        return $sorgu->where(fn ($q) => $q->where('branch_label', $b['label'])->orWhere('branch', $b['title']));
    }

    /**
     * Yayındaki doktorların il / ilçe / branş dağılımı (site haritası, bağlantılar, sayılar).
     * 6 saat önbellekte; satır: [il_id, il_slug, il_ad, ilce, ilce_slug, brans_slug, adet].
     */
    public static function dagilim(): Collection
    {
        return collect(Cache::remember('doktor-seo:dagilim:v1', now()->addHours(6), function () {
            $iller = City::all(['id', 'slug', 'name'])->keyBy('id');

            return Expert::query()->where('is_published', true)->whereNotNull('city_id')
                ->selectRaw('city_id, district, branch, branch_label, COUNT(*) as adet')
                ->groupBy('city_id', 'district', 'branch', 'branch_label')->get()
                ->map(function ($r) use ($iller) {
                    $il = $iller->get($r->city_id);

                    return $il ? [
                        'il_id' => $il->id, 'il_slug' => $il->slug, 'il_ad' => $il->name,
                        'ilce' => $r->district ? trim($r->district) : null, 'ilce_slug' => $r->district ? self::ilceSlug($r->district) : null,
                        'brans_slug' => self::bransSlug($r->branch, $r->branch_label), 'adet' => (int) $r->adet,
                    ] : null;
                })->filter()->values()->all();
        }));
    }
}
