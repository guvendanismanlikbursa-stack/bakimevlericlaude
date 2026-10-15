<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'slug', 'old_slug', 'city_id', 'district_id', 'facility_category_id', 'ownership_type', 'district', 'neighborhood',
        'address', 'lat', 'lng', 'phone', 'phone_type', 'email', 'source', 'source_payload', 'description', 'capacity', 'price_min',
        'price_max', 'services', 'cover_image', 'is_published',
        'is_featured', 'rating', 'is_claimed', 'claimed_at', 'ministry_verification',
        'free_quote_credits', 'balance', 'quote_price_override', 'views_count', 'boosted_views_count', 'boosted_views_count_weekly',
        'views_count_at_week_start', 'views_count_at_month_start', 'views_count_at_quarter_start', 'favorites_count',
        'invitation_status', 'invitation_status_at', 'menu_image_path', 'menu_image_updated_at',
        'is_broker_managed', 'vacant_beds_male', 'vacant_beds_female', 'vacant_beds_updated_at',
        'allows_visit_service', 'video_path', 'video_updated_at',
        'vacancy_male', 'vacancy_female', 'vacancy_general', 'vacancy_updated_at',
        'site_visited_at', 'google_maps_url', 'google_reviews_fetched_at',
        'is_subscribed', 'subscription_plan', 'subscription_started_at', 'subscription_paid_until', 'subscription_amount',
        'marketing_status', 'marketing_status_at', 'marketing_brochure_requested', 'marketing_callback_at',
    ];

    protected function casts(): array
    {
        return [
            'menu_image_updated_at' => 'datetime',
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
            'is_broker_managed' => 'boolean',
            'allows_visit_service' => 'boolean',
            'video_updated_at' => 'datetime',
            'vacant_beds_male' => 'integer',
            'vacant_beds_female' => 'integer',
            'vacant_beds_updated_at' => 'datetime',
            'vacancy_male' => 'boolean',
            'vacancy_female' => 'boolean',
            'vacancy_general' => 'boolean',
            'vacancy_updated_at' => 'datetime',
            'site_visited_at' => 'datetime',
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
            'is_subscribed' => 'boolean',
            'subscription_started_at' => 'datetime',
            'subscription_paid_until' => 'date',
            'subscription_amount' => 'float',
            'marketing_status_at' => 'datetime',
            'marketing_brochure_requested' => 'boolean',
            'marketing_callback_at' => 'datetime',
        ];
    }

    // 6 Ekim 2026: kullanicinin bulgusu - telefon numarasi herhangi bir
    // yoldan (admin duzenleme formu, Veri Cekici importu, ops/phone-fix,
    // vb.) degistiginde invitation_status ("Telefon yok"/"Sabit Hatli"/
    // "Gonderilecek" etiketleri) ESKI DEGERDE KALIYORDU - bu senkronizasyon
    // SADECE DataQualityService::phoneFix()/phoneTypeFix() GIBI belirli
    // tek-seferlik araclarin icinde elle yapiliyordu, baska HICBIR
    // yoldan telefon degisince calismiyordu. Ayni hata tekrar tekrar
    // (farkli kurumlarda) rapor edildi. Artik TEK, MERKEZI bir yerde -
    // model olay dinleyicisinde - telefon her ne sekilde degisirse degissin
    // otomatik calisir, ayrica hicbir yerde elle cagirmaya gerek kalmaz.
    // Davet sureci ilerlemis (opened/sent/claimed/onaylanmis/istemiyor/
    // ulasilamadi/yanlis-numara/haric-tutulmus) kurumlara DOKUNMAZ.
    protected static function booted(): void
    {
        static::saving(function (Facility $facility) {
            if (! $facility->isDirty('phone')) {
                return;
            }

            $autoStatuses = ['not_started', 'landline_only', 'contact_missing'];
            if (! in_array($facility->invitation_status, $autoStatuses, true)) {
                return;
            }

            $computed = classify_phone_type($facility->phone);
            $desired = match ($computed) {
                'mobile' => 'not_started',
                'landline' => 'landline_only',
                default => 'contact_missing',
            };

            if ($facility->invitation_status !== $desired) {
                $facility->invitation_status = $desired;
                $facility->invitation_status_at = now();
            }
        });
    }

    // 22 Eylul 2026: kullanicinin talebi - "abonelik" ozelligi. Kasitli
    // olarak ayri bir "aktif" alani TUTULMUYOR - abonelik durumu HER ZAMAN
    // subscription_paid_until tarihine canli bakilarak hesaplanir, boylece
    // odeme suresi dolunca telefon/WhatsApp butonlari otomatik (bir sonraki
    // sayfa yuklemesinde, ek bir gece gorevi beklemeden) gizlenir - ayri bir
    // "aktif" bayragi olsaydi bunu ayrica senkronlamak gerekirdi.
    public function isActiveSubscriber(): bool
    {
        return $this->is_subscribed
            && $this->subscription_paid_until
            && ! $this->subscription_paid_until->copy()->endOfDay()->isPast();
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

    /**
     * 29 Agustos 2026: kullanicinin talebi - koguslari cinsiyete gore
     * ayrilan yasli bakim kategorilerinde (huzurevi/yasli bakim evi)
     * bay/bayan icin ayri "yer var mi" gosterilir, diger kategorilerde
     * (cocuk, rehabilitasyon vb.) tek bir genel alan yeterli.
     */
    public function usesGenderSplitVacancy(): bool
    {
        return $this->category?->brand_scope === 'yasli-bakim';
    }

    public function offerRequests()
    {
        return $this->hasMany(OfferRequest::class);
    }

    public function facilityUsers()
    {
        return $this->hasMany(FacilityUser::class);
    }

    public function marketingNotes()
    {
        return $this->hasMany(FacilityMarketingNote::class)->latest();
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function images()
    {
        return $this->hasMany(FacilityImage::class)->orderBy('sort_order');
    }

    public function roomTypes()
    {
        return $this->hasMany(FacilityRoomType::class)->orderBy('sort_order');
    }

    public function ageGroups()
    {
        return $this->hasMany(FacilityAgeGroup::class)->orderBy('sort_order');
    }

    public function programTypes()
    {
        return $this->hasMany(FacilityProgramType::class)->orderBy('sort_order');
    }

    public function visitServiceRequests()
    {
        return $this->hasMany(VisitServiceRequest::class);
    }

    public function brokerReferrals()
    {
        return $this->hasMany(BrokerReferral::class);
    }

    /**
     * 19 Agustos 2026: kullanicinin talebi - admin/kurum yetkilisi 10
     * gorselden hangisinin ANA (kapak) gorsel olacagini secebilsin. Kasitli
     * olarak sort_order'i DEGISTIRMEZ (galeri sirasi bozulmasin) - ayri bir
     * is_primary bayragi kullanilir. Hicbir gorsel acikca "ana" olarak
     * isaretlenmemisse (ör. bu ozellikten once yuklenmis eski kurumlar),
     * geriye donuk uyumluluk icin sort_order'i en dusuk olan (ilk yuklenen)
     * gorsel varsayilan olarak kullanilir - onceki davranisla BIREBIR ayni.
     */
    public function primaryImage(): ?FacilityImage
    {
        if ($this->relationLoaded('images')) {
            return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        }

        return $this->images()->where('is_primary', true)->first() ?? $this->images()->first();
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

    // 12 Ekim 2026: bu kuruma hangi aile ihtiyac anketleri icin WhatsApp
    // gonderildiginin kaydi - bkz. FamilyNeedSurveyController::whatsapp().
    public function needSurveySends()
    {
        return $this->hasMany(FamilyNeedSurveySend::class)->latest();
    }

    // 13 Ekim 2026: Bakim Danismani uzerinden gelen (persist edilmis) lead'lere
    // bu kurumun WhatsApp'tan ulastigi kayitlar - bkz. CareAdvisorLeadSend.
    public function careAdvisorLeadSends()
    {
        return $this->hasMany(CareAdvisorLeadSend::class)->latest();
    }
    public function reviews()
    {
        return $this->hasMany(FacilityReview::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(FacilityReview::class)->where('status', 'approved')->latest();
    }

    // 20 Eylul 2026: kullanicinin talebi - veri cekici ile toplanan Google
    // yorumlari, platformun kendi aile yorumlarindan (yukarida) AYRI.
    public function googleReviews()
    {
        return $this->hasMany(FacilityGoogleReview::class)->orderBy('position');
    }

    /**
     * 20 Eylul 2026: kullanicinin talebi - kurum kartinda "💬 X yorum"
     * rozeti icin. SADECE anlasmali/sahiplenilmis kartlarda (facility-card
     * partial'inin buyuk kart dalinda) cagirilir - on kayitli kurumlarin
     * kucuk mini-kartinda hic yok, N+1 riski o yuzden sinirli.
     */
    /**
     * 30 Eylul 2026: kullanicinin talebi - kurum sayfalarinda ANA/BUYUK
     * rakam artik "bu AY" sayacidir (ayin basinda sifirlanir, bkz. Console\
     * Commands\ResetMonthlyViewCounters) - haftalik sifirlama hemen sonrasi
     * kucuk gorunen sayi sorununu cozer. Gercek aylik goruntulenme
     * (views_count - views_count_at_month_start) + bu ay otomatik eklenen
     * (boosted_views_count). Ic analitik BUNU DEGIL, ham views_count'u
     * (lifetime) kullanmaya devam eder.
     */
    public function monthlyDisplayViewsCount(): int
    {
        $monthlyReal = max(0, (int) $this->views_count - (int) $this->views_count_at_month_start);

        return $monthlyReal + (int) $this->boosted_views_count;
    }

    /**
     * 30 Eylul 2026: "bu HAFTA" sayaci - kucuk, ikincil bir trend rozeti
     * olarak monthlyDisplayViewsCount()'un yaninda gosterilir (bkz. Console\
     * Commands\ResetWeeklyViewCounters, facilities/show.blade.php).
     */
    public function displayViewsCount(): int
    {
        $weeklyReal = max(0, (int) $this->views_count - (int) $this->views_count_at_week_start);

        return $weeklyReal + (int) $this->boosted_views_count_weekly;
    }

    public function totalReviewCount(): int
    {
        // 22 Eylul 2026: kullanicinin bildirdigi gercek hata (N+1 testi) -
        // bu metot listeleme sayfalarinda HER kart icin 2 ayri COUNT
        // sorgusu tetikliyordu (10 kartlik bir sayfada +20 sorgu).
        // withCount(['approvedReviews', 'googleReviews']) ile onceden
        // yuklenmisse (bkz. Public\FacilityController/HomeController
        // ->with(...) cagrilari) o degerler burada sorgusuz kullanilir.
        if (array_key_exists('approved_reviews_count', $this->attributes) || array_key_exists('google_reviews_count', $this->attributes)) {
            return (int) ($this->attributes['approved_reviews_count'] ?? 0) + (int) ($this->attributes['google_reviews_count'] ?? 0);
        }

        $approved = $this->relationLoaded('approvedReviews') ? $this->approvedReviews->count() : $this->approvedReviews()->count();
        $google = $this->relationLoaded('googleReviews') ? $this->googleReviews->count() : $this->googleReviews()->count();

        return $approved + $google;
    }

    /**
     * 28 Eylul 2026: kullanicinin talebi - tanıtım kartlarında "📷 X" rozeti
     * icin GERCEK (demo HARİÇ) gorsel sayisi. images relation ZATEN listeleme
     * sorgularinda ->with('images') ile yukleniyor (bkz. facility-card.blade.php
     * ust kismindaki $ownImagePath), bu yuzden N+1 riski yok - relation
     * yuklenmemisse (tek kurum sayfalari gibi) sorguya duser.
     */
    public function realImageCount(): int
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return $images->filter(fn ($img) => ! str_starts_with($img->path, 'facilities/demo/'))->count();
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

    // 30 Eylul 2026: kullanicinin talebi - TUM kurum listeleme yuzeylerinde
    // (anasayfa, arama sonuclari, kategori/rehber sayfalari) AYNI siralama
    // onceligi: Anlasmali > Öne Çıkan (admin'in bilerek one cikardigi VEYA
    // odemesi gecerli abone) > Önerilen (sahiplenilmis, digerleri degil) >
    // Ön Kayıtlı. Onceden her controller kendi orderByDesc('is_broker_managed')
    // ->orderByDesc('is_featured') zincirini TEKRAR TEKRAR yaziyordu (5 ayri
    // dosyada, bazilari 'is_featured'i abone durumunu HIC saymiyordu) - bu
    // tek, paylasilan scope ile artik HEPSI ayni mantigi kullanir.
    public function scopeOrderByListingPriority($query)
    {
        // DIKKAT: CURDATE() MySQL'e ozel - yerel test paketi sqlite
        // kullaniyor, orada "near CURDATE: syntax error" ile patlardi.
        // Bugunun tarihini PHP'den parametre olarak gecmek her iki
        // veritabaninda da calisir.
        // 15 Ekim 2026: kullanicinin talebi - "siteyi daha profesyonel
        // gosterme" fikirlerinden biri: gercek (demo olmayan) fotograf
        // yukleyen kurumlar aramalarda hafifce one cikar. BILEREK sadece
        // AYNI katman ICINDE bir ikinci kriter (is_broker_managed/is_featured/
        // is_claimed katmanlarindan SONRA, rating'den ONCE) - odeme yapan/
        // sahiplenen bir kurumun onune ASLA gecmez, sadece ayni katmandaki
        // kurumlar arasinda gercek fotografi olan hafifce one cikar.
        return $query
            ->orderByDesc('is_broker_managed')
            ->orderByRaw('(is_featured OR (is_subscribed AND subscription_paid_until >= ?)) DESC', [now()->toDateString()])
            ->orderByDesc('is_claimed')
            ->orderByRaw("EXISTS (SELECT 1 FROM facility_images fi WHERE fi.facility_id = facilities.id AND fi.path NOT LIKE 'facilities/demo/%') DESC")
            ->orderByDesc('rating');
    }

    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    /**
     * 1 Eylul 2026: kullanicinin bildirdigi gercek hata - anlasmali
     * (is_broker_managed) ama henuz sahiplenilmemis bir kurumda "Ücret
     * Bilgisi İste"/"Ziyaret Talebi"/"Kontenjan Sor"/"Soru Sor" formlari
     * HIC gorunmuyordu (her yerde sadece is_claimed kontrol ediliyordu) -
     * aile o kurum icin platform uzerinden YAPILANDIRILMIS hicbir talep
     * gonderemiyordu, sadece dogrudan arama/WhatsApp'a mahkumdu. Oysa bu
     * talepler anlasmali kurumlarda zaten dogru sekilde admine yonleniyor
     * (bkz. notify_facility_or_broker_admins()) - eksik olan SADECE bu
     * formlarin GORUNMESI ve sunucu tarafinda KABUL EDILMESIYDI. Bu scope
     * OfferRequestController/VisitRequestController/FacilityQuestionController'daki
     * tum ->where('is_claimed', true) kontrollerinin yerini alir -
     * scopeClaimed() ise (listeleme/filtreleme icin, gercekten kendi
     * hesabiyla yoneten kurumlar anlamina geliyor) DEGISTIRILMEDEN kaldi.
     */
    public function scopeAcceptsFamilyRequests($query)
    {
        return $query->where(function ($q) {
            $q->where('is_claimed', true)->orWhere('is_broker_managed', true);
        });
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
     * 10 Eylul 2026: kullanicinin talebi - bakimevleri.com, anlasmali
     * (is_broker_managed) KRES ve ANAOKULLARINA kayit yaptiran cocuklar
     * icin 1 yillik ferdi kaza sigortasini UCRETSIZ yaptiriyor. Bu bir
     * pazarlama/guven unsuru olarak kurum kartinda ve detay sayfasinda
     * ozel bir etiket + aciklama ile gosterilir. SADECE anlasmali
     * kurumlar icin gecerli; kategori 'kres-ve-anaokulu' İSE VEYA kurum
     * adinda "anaokulu" geciyorsa uygulanir.
     *
     * 16 Eylul 2026: kullanicinin bildirdigi gercek hata - veri cekiciyle
     * toplu eklenen yuzlerce kurum (ismi "... Anaokulu" olsa bile) yanlis
     * kategoride ('Çocuk Bakım Merkezi', #3) kaydedilmisti, kategori
     * 'kres-ve-anaokulu' (#4) DEGILDI - tek tek kategori duzeltmeden
     * anlasmali isaretlenen boyle bir kurumda rozet hic cikmiyordu.
     * Kullanicinin acik talebi: "kategori VEYA isim ikisinden biri
     * yeterli" - kategori dogru girilmemis olsa bile isminde "anaokulu"
     * gecen anlasmali kurumlar artik otomatik uygun sayilir.
     */
    public function hasFreeChildAccidentInsurance(): bool
    {
        if (! $this->is_broker_managed) {
            return false;
        }

        if ($this->category?->slug === 'kres-ve-anaokulu') {
            return true;
        }

        return str_contains(mb_strtolower($this->name), 'anaokulu');
    }

    /**
     * 15 Eylul 2026: kullanicinin talebi - gorsel SEO. Kurum gorsellerindeki
     * alt metni sadece kurum adiyla sinirliydi ("ÖZEL DİLEK ANAOKULU" gibi),
     * il/ilce/kategori bilgisi eklenmiyordu - bu da Google Görseller'de
     * "Bursa anaokulu" gibi aramalarda gorsellerin yakalanma sansini
     * azaltiyordu. Bu metot city/category iliskileri ZATEN yuklu oldugu
     * (with(['city','category'])) her yerde kullanilir; yeni bir sorgu
     * tetiklemez, sadece mevcut veriyi birlestirir. Herhangi bir alan
     * eksikse (ör. city null) o kismi sessizce atlar, hicbir zaman "null"
     * metni gorunmez.
     */
    public function imageAltText(): string
    {
        $parts = array_filter([
            $this->name,
            $this->category?->name,
            $this->city?->name,
        ]);

        return implode(' - ', $parts);
    }

    /**
     * "Kurum Performans Sayfasi" icin guven/istatistik ozeti.
     * Yalnizca gercekten var olan verilerle hesaplanir; uydurma alan yok.
     */
    public function performanceSummary(): array
    {
        // 30 Eylul 2026: kullanicinin talebi - bu, "HER YERDE AYNI TEK sayi"
        // diyen 28 Agustos yorumundaki TEK merkezi kaynak - kurum sahibine/
        // ziyaretcilere gosterilen ANA goruntulenme artik monthlyDisplayViewsCount()
        // ("bu ay"). Ayrica kucuk bir "bu hafta" trend rozeti icin
        // displayViewsCount() de ayri bir anahtarda dondurulur. Ic analitik
        // hicbirini kullanmaz, sadece bu metot (dis gorunum) uzerinden gecer.
        return [
            'views_count' => $this->monthlyDisplayViewsCount(),
            'weekly_views_count' => $this->displayViewsCount(),
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

    /**
     * 18 Eylul 2026: kullanicinin talebi - rakip site (okul.com.tr)
     * kiyasinda goze carpan bir sosyal kanit unsuru: "bu ilcede en cok
     * yorum alan #X kurum". SADECE kurum DETAY sayfasinda cagirilir (bkz.
     * facilities/show.blade.php) - liste/kart dongulerinde ASLA
     * kullanilmamali, her kurum icin ayri bir sorgu calistirir (N+1 riski).
     * En az 3 onayli yorumu olmayan ya da ilcesinde ilk 5'e giremeyen
     * kurumlar icin null doner (rozet hic gosterilmez).
     */
    public function reviewRankLabel(): ?string
    {
        if (! $this->district || ! $this->facility_category_id) {
            return null;
        }

        $myCount = $this->relationLoaded('approvedReviews') ? $this->approvedReviews->count() : $this->approvedReviews()->count();
        if ($myCount < 3) {
            return null;
        }

        $betterCount = self::query()
            ->where('facility_category_id', $this->facility_category_id)
            ->where('district', $this->district)
            ->whereNull('deleted_at')
            ->where('id', '!=', $this->id)
            ->withCount('approvedReviews')
            ->having('approved_reviews_count', '>', $myCount)
            ->count();

        $rank = $betterCount + 1;
        if ($rank > 5) {
            return null;
        }

        return "Bu ilçede en çok yorum alan #{$rank} kurum";
    }

}

