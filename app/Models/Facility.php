<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'slug', 'old_slug', 'city_id', 'district_id', 'facility_category_id', 'ownership_type', 'district',
        'address', 'lat', 'lng', 'phone', 'phone_type', 'email', 'source', 'source_payload', 'description', 'capacity', 'price_min',
        'price_max', 'services', 'cover_image', 'is_published',
        'is_featured', 'rating', 'is_claimed', 'claimed_at', 'ministry_verification',
        'free_quote_credits', 'balance', 'quote_price_override', 'views_count', 'favorites_count',
        'invitation_status', 'invitation_status_at',
    ];

    protected function casts(): array
    {
        return [
            // 28 Temmuz 2026: OfferRequest::city_id/facility_category_id
            // ZATEN integer'a cast'li (bkz. o model), Facility tarafinda
            // bu alanlar cast'siz olunca "yayin talebi" (broadcast) uygunluk
            // kontrolundeki === kiyaslamalari (Facility\QuoteController)
            // ayni turden bir string/int uyumsuzlugu riski tasiyordu - bkz.
            // facility_id icin FacilityUser'da bulunan ve duzeltilen ayni sinif hata.
            'city_id' => 'integer',
            'district_id' => 'integer',
            'facility_category_id' => 'integer',
            'services' => 'array',
            'source_payload' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'is_claimed' => 'boolean',
            'claimed_at' => 'datetime',
            'price_min' => 'float',
            'price_max' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'rating' => 'float',
            'avg_response_minutes' => 'integer',
            'response_sample_count' => 'integer',
            'balance' => 'float',
            'quote_price_override' => 'float',
            'invitation_status_at' => 'datetime',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    public function offerRequests()
    {
        return $this->hasMany(OfferRequest::class);
    }

    public function facilityUsers()
    {
        return $this->hasMany(FacilityUser::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function images()
    {
        return $this->hasMany(FacilityImage::class)->orderBy('sort_order');
    }

    public function engagementEvents()
    {
        return $this->hasMany(FacilityEngagementEvent::class);
    }


    public function serviceOptions()
    {
        return $this->belongsToMany(FacilityServiceOption::class, 'facility_service_option_facility')->withTimestamps();
    }

    public function elderlyDetail()
    {
        return $this->hasOne(ElderlyFacilityDetail::class);
    }

    public function childDetail()
    {
        return $this->hasOne(ChildFacilityDetail::class);
    }

    public function rehabDetail()
    {
        return $this->hasOne(RehabFacilityDetail::class);
    }

    public function claims()
    {
        return $this->hasMany(FacilityClaim::class);
    }

    public function walletTopups()
    {
        return $this->hasMany(WalletTopup::class);
    }

    public function balanceLogs()
    {
        return $this->hasMany(BalanceLog::class)->latest();
    }
    public function reviews()
    {
        return $this->hasMany(FacilityReview::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(FacilityReview::class)->where('status', 'approved')->latest();
    }

    public function visitRequests()
    {
        return $this->hasMany(VisitRequest::class)->latest();
    }

    public function questions()
    {
        return $this->hasMany(FacilityQuestion::class)->latest();
    }

    public function answeredQuestions()
    {
        return $this->hasMany(FacilityQuestion::class)->whereNotNull('answer')->latest('answered_at');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    // 17 Agustos 2026: kullanicinin bildirdigi hata - platform:check-user-flows
    // gunluk otomatik kontrolunun kalici QATEST Daily kurum sabitleri (bkz.
    // CheckUserFlows::ensureClaimedFacility/ensureUnclaimedFacility, hepsi
    // source='qa_test') GERCEK ailelerin karsisina keşif/listeleme
    // yuzeylerinde (anasayfa, kurumlar listesi, rehber, "yeni eklenenler" vb.)
    // cikiyordu. published() scope'unu degistirmedik cunku CheckUserFlows'un
    // kendisi bu kurumlara DOGRUDAN slug ile (/kurumlar/{slug}) erisiyor -
    // published()'i kisitlasaydik gunluk kontrolun kendisi kirilirdi. Bunun
    // yerine SADECE gercekten "kesif/listeleme" yuzeylerinde kullanilacak
    // ayri bir scope: dogrudan tek-kurum sayfalarinda (show/sahiplen/teklif/
    // ziyaret/soru) hala published() kullanilir, degismedi.
    public function scopeDiscoverable($query)
    {
        // DIKKAT: source cogu gercek kurumda NULL - "!= 'qa_test'" SQL'de
        // NULL satirlari da (dogru degil, BILINMIYOR sonucu ureterek)
        // SESSIZCE eler, bu ilk denemede TUM gercek kurumlari kesif
        // sayfalarindan kaybetmisti. NULL'u acikca serbest birakmak sart.
        return $query->published()->where(function ($q) {
            $q->whereNull('source')->orWhere('source', '!=', 'qa_test');
        });
    }

    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    /**
     * Bir markanin kategori kapsamina giren kurumlari getirir.
     */
    public function scopeForBrand($query, array $categoryScope)
    {
        return $query->whereHas('category', function ($q) use ($categoryScope) {
            $q->whereIn('brand_scope', $categoryScope);
        });
    }

    public function isInBrandScope(array $categoryScope): bool
    {
        $scope = $this->relationLoaded('category')
            ? $this->category?->brand_scope
            : $this->category()->value('brand_scope');

        return in_array($scope, $categoryScope, true);
    }

    /**
     * Bu kurum icin gecerli teklif ucreti: admin bu kurum icin ozel bir ucret
     * tanimladiysa (quote_price_override) o kullanilir, yoksa genel ayar.
     */
    public function effectiveQuotePrice(): float
    {
        return (float) ($this->quote_price_override ?? Setting::get('quote_price', config('platform.default_quote_price')));
    }

    /**
     * Kurum yeni bir teklif (quote) gonderebilir mi? Once ucretsiz hak,
     * bitince bakiyeden teklif basina ucret dusulur.
     */
    public function canSendQuote(): bool
    {
        if ($this->free_quote_credits > 0) {
            return true;
        }

        return $this->balance >= $this->effectiveQuotePrice();
    }

    /**
     * Bir teklif gonderildiginde hak/bakiye dusumunu yapar ve denetim kaydi olusturur.
     */
    public function chargeForQuote(): void
    {
        if ($this->free_quote_credits > 0) {
            $this->decrement('free_quote_credits');

            BalanceLog::create([
                'facility_id' => $this->id,
                'type' => 'quote_charge_credit',
                'amount' => 0,
                'credits_amount' => -1,
                'balance_after' => $this->balance,
                'credits_after' => $this->fresh()->free_quote_credits,
                'note' => 'Ücretsiz hak kullanıldı (teklif gönderimi).',
            ]);

            return;
        }

        $price = $this->effectiveQuotePrice();
        $this->decrement('balance', $price);

        BalanceLog::create([
            'facility_id' => $this->id,
            'type' => 'quote_charge_balance',
            'amount' => -$price,
            'credits_amount' => 0,
            'balance_after' => $this->fresh()->balance,
            'credits_after' => $this->free_quote_credits,
            'note' => 'Bakiyeden teklif ücreti düşüldü.',
        ]);
    }


    public function profileQuality(): array
    {
        $checks = [
            'name' => ['label' => 'Kurum adi', 'done' => filled($this->name), 'weight' => 8],
            'category' => ['label' => 'Kategori', 'done' => filled($this->facility_category_id), 'weight' => 8],
            'city' => ['label' => 'Il bilgisi', 'done' => filled($this->city_id), 'weight' => 8],
            'district' => ['label' => 'Ilce bilgisi', 'done' => filled($this->district), 'weight' => 7],
            'address' => ['label' => 'Acik adres', 'done' => filled($this->address), 'weight' => 8],
            'phone' => ['label' => 'Telefon', 'done' => filled($this->phone), 'weight' => 8],
            'description' => ['label' => 'Detayli aciklama', 'done' => mb_strlen((string) $this->description) >= 120, 'weight' => 12],
            'capacity' => ['label' => 'Kapasite', 'done' => filled($this->capacity) && (int) $this->capacity > 0, 'weight' => 7],
            'price' => ['label' => 'Fiyat araligi', 'done' => filled($this->price_min) || filled($this->price_max), 'weight' => 8],
            'services' => ['label' => 'Hizmet/ozellik secimi', 'done' => count($this->services ?? []) >= 3, 'weight' => 10],
            'images' => ['label' => 'Galeri gorselleri', 'done' => $this->qualityImagesCount() >= 3, 'weight' => 10],
            'claimed' => ['label' => 'Yetkili dogrulamasi', 'done' => (bool) $this->is_claimed, 'weight' => 6],
        ];

        $score = collect($checks)->sum(fn ($check) => $check['done'] ? $check['weight'] : 0);
        $missing = collect($checks)
            ->filter(fn ($check) => ! $check['done'])
            ->map(fn ($check) => $check['label'])
            ->values()
            ->all();

        return [
            'score' => min(100, $score),
            'missing' => $missing,
            'completed' => collect($checks)->where('done', true)->count(),
            'total' => count($checks),
        ];
    }

    public function profileQualityScore(): int
    {
        return $this->profileQuality()['score'];
    }

    private function qualityImagesCount(): int
    {
        if ($this->relationLoaded('images')) {
            return $this->images->count();
        }

        return $this->images()->count();
    }

    /**
     * 14 Agustos 2026: kullanicinin talebi - "hizli yanit veren kurum"
     * rozeti. En az 3 ornek + ortalama 2 saatin (120 dk) altinda yanit
     * SART - tek bir sansli hizli yanitla rozet kazanilmamali (bkz.
     * CalculateFacilityResponseTime komutu, response_sample_count < 3
     * icin avg_response_minutes'i zaten null birakiyor).
     */
    public function hasFastResponseBadge(): bool
    {
        return $this->avg_response_minutes !== null
            && $this->response_sample_count >= 3
            && $this->avg_response_minutes <= 120;
    }

    /**
     * "Kurum Performans Sayfasi" icin guven/istatistik ozeti.
     * Yalnizca gercekten var olan verilerle hesaplanir; uydurma alan yok.
     */
    public function performanceSummary(): array
    {
        return [
            'views_count' => $this->views_count,
            'favorites_count' => $this->favorites_count,
            'offers_count' => $this->relationLoaded('offerRequests') ? $this->offerRequests->count() : $this->offerRequests()->count(),
            'quotes_count' => $this->relationLoaded('quotes') ? $this->quotes->count() : $this->quotes()->count(),
            'reviews_count' => $this->relationLoaded('approvedReviews') ? $this->approvedReviews->count() : $this->approvedReviews()->count(),
            'last_updated_at' => $this->updated_at,
            'is_claimed' => $this->is_claimed,
            'claimed_at' => $this->claimed_at,
        ];
    }

    // 17 Agustos 2026: kullanicinin talebi - sahiplenilmemis kurum sayfasinda
    // "size gercekten talep geliyor" kanitini son 30 gune gore gosterme.
    // Gunluk pencere (24 saat) dusuk trafikli kurumlarda cogunlukla 0
    // gosterip mesaji zayiflatirdi; 30 gun daha istikrarli.
    public function engagementStats30d(): array
    {
        $counts = $this->engagementEvents()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'views' => (int) ($counts['view'] ?? 0),
            'phone_clicks' => (int) ($counts['phone_click'] ?? 0),
            'whatsapp_clicks' => (int) ($counts['whatsapp_click'] ?? 0),
        ];
    }

    /**
     * 17 Agustos 2026: kullanicinin talebi - "yol tarifi"/harita SADECE
     * kurumun GERCEK adresinden geocode edilmis konumu varsa gosterilmeli;
     * aksi halde aileyi il merkezine (yanlis yere) yonlendirir. Ayri bir
     * "hassasiyet" sutunu yok - geoFillCityCentroid() SADECE lat/lng bos
     * kurumlari il merkeziyle doldurdugu icin, kaydedilen deger o ilin
     * merkez koordinatiyla (turkiye_centroids.php) TAM ESLESIYORSA bu,
     * gercek bir adresin tesadufen tam o noktaya denk gelmesinden
     * (istatistiksel olarak imkansiza yakin) COK daha olası şekilde bir
     * il-merkezi yedegidir.
     */
    public function hasPreciseLocation(): bool
    {
        if (! $this->lat || ! $this->lng) {
            return false;
        }

        $centroid = config('turkiye_centroids.'.$this->city?->name);
        if (! $centroid) {
            return true;
        }

        return abs((float) $this->lat - $centroid[0]) > 0.0001 || abs((float) $this->lng - $centroid[1]) > 0.0001;
    }

    /**
     * 16 Temmuz 2026: 4 segmentin (Ekonomik/Standart/Premium/Ultra Premium)
     * sinir/etiket/renk tanimlarini, VERILEN esiklere (artik kurum kategorisi
     * bazinda - bkz. FacilityCategory::priceTierThresholds()) gore uretir.
     * Her tier'in [min, max) araligi vardir (max=null => sinirsiz ust sinir).
     */
    private static function tierBands(array $thresholds): array
    {
        return [
            ['key' => 'ekonomik', 'label' => 'Ekonomik', 'emoji' => '🟢', 'classes' => 'bg-green-100 text-green-800', 'min' => 0, 'max' => $thresholds['standart_min']],
            ['key' => 'standart', 'label' => 'Standart', 'emoji' => '🔵', 'classes' => 'bg-blue-100 text-blue-800', 'min' => $thresholds['standart_min'], 'max' => $thresholds['premium_min']],
            ['key' => 'premium', 'label' => 'Premium', 'emoji' => '🟣', 'classes' => 'bg-purple-100 text-purple-800', 'min' => $thresholds['premium_min'], 'max' => $thresholds['ultra_min']],
            ['key' => 'ultra_premium', 'label' => 'Ultra Premium', 'emoji' => '🟡', 'classes' => 'bg-amber-100 text-amber-800', 'min' => $thresholds['ultra_min'], 'max' => null],
        ];
    }

    /**
     * Ucretlendirme segmenti: Ekonomik / Standart / Premium / Ultra Premium.
     * price_min uzerinden, kurumun KATEGORISINE gore ayarlanmis esiklere gore
     * hesaplanir. Fiyat bilgisi olmayan (ozellikle on kayitli, veri cekiciyle
     * gelen) kurumlarda yanlis bir segment gostermemek icin null doner.
     */
    public function priceTier(): ?array
    {
        if (! $this->price_min) {
            return null;
        }

        $thresholds = $this->category?->priceTierThresholds() ?? config('platform.default_price_tiers');
        $price = (float) $this->price_min;

        foreach (array_reverse(self::tierBands($thresholds)) as $tier) {
            if ($price >= $tier['min']) {
                return $tier;
            }
        }

        return self::tierBands($thresholds)[0];
    }

    /**
     * 16 Temmuz 2026: bir kurumun fiyat araligi (price_min - price_max)
     * BIRDEN FAZLA segmenti kaplayabilir (orn. 12.000-35.000₺ hem Ekonomik'in
     * ust ucunu hem Standart'in tamamini hem Premium'un alt ucunu kapsayabilir).
     * Kurum karti/inceleme sayfasinda TEK degil, kesisen TUM segment
     * rozetlerinin gosterilmesi icin kullanilir. price_max yoksa tek nokta
     * (price_min) muamelesi gorur - priceTier() ile ayni sonucu verir.
     */
    public function priceTiers(): array
    {
        if (! $this->price_min) {
            return [];
        }

        $thresholds = $this->category?->priceTierThresholds() ?? config('platform.default_price_tiers');
        $min = (float) $this->price_min;
        $max = $this->price_max !== null ? (float) $this->price_max : $min;

        return collect(self::tierBands($thresholds))
            ->filter(fn ($tier) => $min < ($tier['max'] ?? INF) && $max >= $tier['min'])
            ->values()
            ->all();
    }

    /**
     * Aile ve Sosyal Hizmetler Bakanligi'nin resmi ozel huzurevi sicili ile
     * yapilan otomatik isim/telefon eslestirmesinin sonucu (bkz. Bakanlik
     * Kiyaslamasi raporu, 07.07.2026). Sadece bakanlik kaydi bulunan illerin
     * Huzurevi kategorisi icin dolduruldu; diger kategoriler/iller icin
     * ministry_verification hep null kalir (karsilastirilacak resmi veri yok).
     */
    public function ministryVerificationBadge(): ?array
    {
        return match ($this->ministry_verification) {
            'verified' => ['label' => 'Bakanlık: Doğrulandı (Özel)', 'classes' => 'bg-green-100 text-green-800'],
            'review' => ['label' => 'Bakanlık: İncelenmeli', 'classes' => 'bg-amber-100 text-amber-800'],
            'unverified' => ['label' => 'Bakanlık: Doğrulanamadı', 'classes' => 'bg-red-100 text-red-800'],
            'kamu_vakif' => ['label' => 'Bakanlık: Kamu/Belediye/Vakıf', 'classes' => 'bg-slate-200 text-slate-800'],
            default => null,
        };
    }

}

