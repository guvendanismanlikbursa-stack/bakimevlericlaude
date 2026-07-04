<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'slug', 'city_id', 'district_id', 'facility_category_id', 'district',
        'address', 'lat', 'lng', 'phone', 'email', 'source', 'source_payload', 'description', 'capacity', 'price_min',
        'price_max', 'services', 'cover_image', 'is_published',
        'is_featured', 'rating', 'is_claimed', 'claimed_at',
        'free_quote_credits', 'balance', 'views_count', 'favorites_count',
    ];

    protected function casts(): array
    {
        return [
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
            'balance' => 'float',
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
     * Kurum yeni bir teklif (quote) gonderebilir mi? Once ucretsiz hak,
     * bitince bakiyeden teklif basina ucret dusulur.
     */
    public function canSendQuote(): bool
    {
        if ($this->free_quote_credits > 0) {
            return true;
        }

        return $this->balance >= (float) Setting::get('quote_price', config('platform.default_quote_price'));
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

        $price = (float) Setting::get('quote_price', config('platform.default_quote_price'));
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

    /**
     * Ucretlendirme segmenti: Ekonomik / Standart / Premium / Ultra Premium.
     * price_min uzerinden, admin panelden ayarlanabilir esiklere gore hesaplanir
     * (bkz. Setting::get('price_tier_...'), varsayilanlar config/platform.php).
     * Fiyat bilgisi olmayan (ozellikle on kayitli, veri cekiciyle gelen) kurumlarda
     * yanlis bir segment gostermemek icin null doner.
     */
    public function priceTier(): ?array
    {
        if (! $this->price_min) {
            return null;
        }

        $defaults = config('platform.default_price_tiers');
        $standartMin = (float) Setting::get('price_tier_standart_min', $defaults['standart_min']);
        $premiumMin = (float) Setting::get('price_tier_premium_min', $defaults['premium_min']);
        $ultraMin = (float) Setting::get('price_tier_ultra_min', $defaults['ultra_min']);

        $price = (float) $this->price_min;

        return match (true) {
            $price >= $ultraMin => ['key' => 'ultra_premium', 'label' => 'Ultra Premium', 'emoji' => '🟡', 'classes' => 'bg-amber-100 text-amber-800'],
            $price >= $premiumMin => ['key' => 'premium', 'label' => 'Premium', 'emoji' => '🟣', 'classes' => 'bg-purple-100 text-purple-800'],
            $price >= $standartMin => ['key' => 'standart', 'label' => 'Standart', 'emoji' => '🔵', 'classes' => 'bg-blue-100 text-blue-800'],
            default => ['key' => 'ekonomik', 'label' => 'Ekonomik', 'emoji' => '🟢', 'classes' => 'bg-green-100 text-green-800'],
        };
    }

}

