<?php

namespace App\Observers;

use App\Models\AdminEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 26 Kasım 2026: Kurum ve uzman kayıtlarında önemli alanlar değişince
 * eski → yeni değeri mevcut işlem günlüğüne (admin_events) yazar.
 *
 * Yalnız bir insan (admin ya da kurum paneli kullanıcısı) oturumu varken
 * kaydeder; konsol komutları, tarayıcılar, toplu aktarımlar ve ziyaretçi
 * sayaçları günlüğü doldurmasın diye atlanır.
 */
class AlanDegisiklikGozlemcisi
{
    /** İzlenen alanlar ve İşlem Günlüğü'nde görünecek Türkçe adları. */
    public const ALANLAR = [
        'name' => 'Ad', 'slug' => 'Adres (slug)', 'title' => 'Unvan', 'branch' => 'Branş',
        'clinic_name' => 'Klinik adı', 'city_id' => 'İl', 'district_id' => 'İlçe', 'district' => 'İlçe (metin)',
        'neighborhood' => 'Mahalle', 'address' => 'Adres', 'lat' => 'Enlem', 'lng' => 'Boylam',
        'facility_category_id' => 'Kategori', 'ownership_type' => 'Sahiplik türü',
        'phone' => 'Telefon', 'phone_type' => 'Telefon türü', 'email' => 'E-posta',
        'description' => 'Açıklama', 'capacity' => 'Kapasite', 'price_min' => 'En düşük fiyat',
        'price_max' => 'En yüksek fiyat', 'services' => 'Hizmetler', 'cover_image' => 'Kapak görseli',
        'is_published' => 'Yayında', 'is_featured' => 'Öne çıkan', 'is_claimed' => 'Sahiplenildi',
        'ministry_verification' => 'Bakanlık doğrulaması', 'free_quote_credits' => 'Ücretsiz teklif hakkı',
        'balance' => 'Bakiye', 'quote_price_override' => 'Özel teklif ücreti',
        'is_broker_managed' => 'Aracı yönetiminde', 'allows_visit_service' => 'Ziyaret hizmeti',
        'is_subscribed' => 'Üye', 'subscription_plan' => 'Üyelik planı',
        'subscription_paid_until' => 'Üyelik bitişi', 'subscription_amount' => 'Üyelik tutarı',
        'subscription_package_id' => 'Üyelik paketi', 'website_url' => 'İnternet sitesi',
        'google_maps_url' => 'Harita bağlantısı', 'opening_hours' => 'Çalışma saatleri',
        'supporter_until' => 'Destekçi bitişi',
    ];

    private const UZUN_METIN = 300;

    public function updated(Model $model): void
    {
        $degisiklikler = [];
        foreach (array_keys($model->getChanges()) as $alan) {
            if (! array_key_exists($alan, self::ALANLAR)) {
                continue;
            }
            $eski = $this->sade($model->getRawOriginal($alan));
            $yeni = $this->sade($model->getAttributes()[$alan] ?? null);
            if ($eski === $yeni) {
                continue;
            }
            $degisiklikler[$alan] = [$eski, $yeni];
        }

        if ($degisiklikler) {
            $this->yaz($model, 'alan_degisti', ['degisiklikler' => $degisiklikler]);
        }
    }

    public function deleted(Model $model): void
    {
        $this->yaz($model, 'silindi', ['ad' => $model->getAttribute('name')]);
    }

    public function restored(Model $model): void
    {
        $this->yaz($model, 'geri_alindi', ['ad' => $model->getAttribute('name')]);
    }

    private function yaz(Model $model, string $olay, array $detay): void
    {
        try {
            if ((app()->runningInConsole() && ! app()->runningUnitTests()) || ! app()->bound('session')) {
                return;
            }
            $adminId = session('admin_id');
            $panelKullanicisi = session('facility_user_id');
            if (! $adminId && ! $panelKullanicisi) {
                return;
            }

            $detay['kim'] = $adminId ? 'admin' : 'kurum_paneli';
            if ($panelKullanicisi) {
                $detay['facility_user_id'] = $panelKullanicisi;
                $detay['facility_user_name'] = session('facility_user_name');
            }

            AdminEvent::create([
                'action_site' => request()->route('brand') ?? 'shared',
                'admin_id' => $adminId,
                'event_type' => strtolower(class_basename($model)).'_'.$olay,
                'entity_type' => class_basename($model),
                'entity_id' => $model->getKey(),
                'detail_json' => $detay,
            ]);
        } catch (\Throwable $e) {
            // Günlük yazılamadı diye asıl kayıt işlemi asla bozulmasın.
            report($e);
        }
    }

    private function sade(mixed $deger): mixed
    {
        if (is_bool($deger)) {
            return (int) $deger;
        }
        if (is_string($deger) && mb_strlen($deger) > self::UZUN_METIN) {
            return mb_substr($deger, 0, self::UZUN_METIN).'…';
        }

        return $deger;
    }
}
