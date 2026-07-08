<?php

if (! function_exists('brand_route')) {
    /**
     * Aktif istek /site/{brand}/... prefix'inden geldiyse "brand.X" ismini,
     * gerçek domainden geldiyse "X" ismini kullanarak URL üretir.
     * $params içinde Eloquent model geçilmesini de destekler.
     */
    function brand_route(string $name, mixed $params = []): string
    {
        if (is_object($params) || (! is_array($params))) {
            $params = [$params];
        }

        $routeBrand = request()->route('brand');
        $usesPrefix = $routeBrand !== null;

        if ($usesPrefix) {
            $mergedParams = array_merge(['brand' => $routeBrand], $params);
            return route("brand.{$name}", $mergedParams);
        }

        return route($name, $params);
    }
}

if (! function_exists('current_brand')) {
    function current_brand(): array
    {
        $brands = config('brands.brands', []);
        $request = request();
        $routeBrand = $request->route('brand');

        if ($routeBrand && isset($brands[$routeBrand])) {
            app()->instance('currentBrand', $brands[$routeBrand]);
            return $brands[$routeBrand];
        }

        $host = $request->getHttpHost();
        foreach ($brands as $brand) {
            if (in_array($host, $brand['domains'] ?? [], true)) {
                app()->instance('currentBrand', $brand);
                return $brand;
            }
        }

        if (app()->bound('currentBrand')) {
            return app('currentBrand');
        }

        return config('brands.brands.' . config('brands.default'));
    }
}

if (! function_exists('service_sections')) {
    function service_sections(): array
    {
        return config('brands.service_sections', []);
    }
}

if (! function_exists('service_section')) {
    function service_section(string $slug = null): array
    {
        $sections = service_sections();

        if ($slug && isset($sections[$slug])) {
            return $sections[$slug];
        }

        return null;
    }
}

if (! function_exists('active_service_section')) {
    function active_service_section(string $slug = null, array $brand = null): array
    {
        $brand = current_brand();
        $sections = service_sections();
        $selected = $slug ?: request()->query('bolum') ?: ($brand['default_section'] ?? array_key_first($sections));

        return $sections[$selected] ?? $sections[$brand['default_section'] ?? array_key_first($sections)];
    }
}

if (! function_exists('service_section_for_scope')) {
    function service_section_for_scope(string $scope): array
    {
        foreach (service_sections() as $section) {
            if (in_array($scope, $section['scopes'], true)) {
                return $section;
            }
        }

        return null;
    }
}
if (! function_exists('turkey_provinces')) {
    function turkey_provinces(): array
    {
        return config('turkiye.provinces', []);
    }
}

if (! function_exists('classify_facility_ownership_type')) {
    /**
     * Kurum isminden ozel/kamu/belediye/vakif ayrimini cikarir. E-posta/telefon
     * gibi kaynaklardan bu bilgi gelmiyor (veri cekici bunlari toplamiyor);
     * Turkce resmi kurum adlandirma kaliplari isim uzerinden cok daha
     * guvenilir bir gosterge. Sira onemli: belediye/vakif/kamu once kontrol
     * edilir, hicbiri eslesmezse varsayilan "ozel" dondurulur.
     */
    function classify_facility_ownership_type(string $name): string
    {
        $n = \Illuminate\Support\Str::of($name)->lower()->ascii()->toString();

        if (str_contains($n, 'belediye')) {
            return 'belediye';
        }

        if (str_contains($n, 'vakfi') || str_contains($n, 'vakif')) {
            return 'vakif';
        }

        $kamuKeywords = [
            'kaymakamlig', 'valilig', 'bakanlig', 'devlet hastanesi',
            'shcek', 'sosyal hizmet', 'il ozel idare', 'muduurlug',
            'mudurlug', 'kyk', 'kredi yurtlar kurumu', 'universite',
            'universitesi', 'il milli egitim', 'ilce milli egitim',
            'egitim ve arastirma hastanesi', 's.b.u.', 'sbu ',
        ];
        foreach ($kamuKeywords as $kw) {
            if (str_contains($n, $kw)) {
                return 'kamu';
            }
        }

        return 'ozel';
    }
}

if (! function_exists('districts_for_city')) {
    function districts_for_city(string $cityName): array
    {
        if (! $cityName) {
            return [];
        }

        return turkey_provinces()[$cityName] ?? [];
    }
}
if (! function_exists('site_section_content')) {
    function site_section_content(string $brandSlug, ?string $sectionSlug): array
    {
        if (! $sectionSlug) {
            return [];
        }

        return config("site_content.brands.{$brandSlug}.sections.{$sectionSlug}", []);
    }
}

if (! function_exists('site_content_page')) {
    function site_content_page(string $brandSlug, string $slug): array
    {
        return config("site_content.brands.{$brandSlug}.pages.{$slug}", []);
    }
}


if (! function_exists('canonical_url')) {
    function canonical_url(array $keep = ['bolum', 'city', 'district', 'category', 'service', 'price_tier', 'budget', 'page']): string
    {
        $query = array_intersect_key(request()->query(), array_flip($keep));
        ksort($query);

        return $query ? url()->current().'?'.http_build_query($query) : url()->current();
    }
}

if (! function_exists('seo_og_image')) {
    function seo_og_image(?array $section = null): string
    {
        $section = $section ?: active_service_section();

        return url($section['hero_image'] ?? '/images/hero-yasli-bakim.jpg');
    }
}

if (! function_exists('facility_card_image')) {
    function facility_card_image($facility, array $section = null): string
    {
        $image = null;
        if ($facility) {
            $image = $facility->relationLoaded('images')
                ? $facility->images->first()
                : $facility->images()->first();
        }

        if ($image?->path) {
            return asset('storage/'.$image->path);
        }

        $section = service_section_for_scope($facility->category->brand_scope);
        $slug = $section['slug'] ?? 'yasli-bakim';
        $path = [
            'yasli-bakim' => 'demo-cards/yasli-bakim.png',
            'cocuk' => 'demo-cards/cocuk.png',
            'rehabilitasyon' => 'demo-cards/rehabilitasyon.png',
        ][$slug] ?? 'demo-cards/yasli-bakim.png';

        if (file_exists(storage_path('app/public/'.$path))) {
            return asset('storage/'.$path);
        }

        return $section['hero_image'] ?? '';
    }
}

if (! function_exists('log_admin_event')) {
    /**
     * canliyaal projesinden tasindi: admin panelindeki onemli aksiyonlari
     * denetim gunlugune (admin_events) yazar. $entity bir Eloquent model,
     * bir dizi veya null olabilir.
     */
    function log_admin_event(string $eventType, mixed $entity = null, array $detail = [], ?string $actionSite = null): void
    {
        $entityType = null;
        $entityId = null;

        if (is_object($entity) && method_exists($entity, 'getKey')) {
            $entityType = class_basename($entity);
            $entityId = $entity->getKey();
        }

        \App\Models\AdminEvent::create([
            'action_site' => $actionSite ?? (request()->route('brand') ?? 'shared'),
            'admin_id' => session('admin_id'),
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'detail_json' => $detail ?: null,
        ]);
    }
}

if (! function_exists('notify_user')) {
    /**
     * canliyaal projesinden tasindi: aile/kurum panellerine gosterilecek
     * uygulama-ici bildirim olusturur. $notifiable bir FacilityUser veya
     * FamilyUser modeli olmalidir.
     */
    function notify_user($notifiable, string $type, string $title, ?string $body = null, array $data = []): void
    {
        if (! $notifiable) {
            return;
        }

        \App\Models\PlatformNotification::create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
        ]);
    }
}

if (! function_exists('facility_brand_framing')) {
    /**
     * 3 site ayni kurum envanterini paylastigi icin (bkz. config/brand_voice.php
     * basindaki not), ayni kurum sayfasinin 3 domainde birebir ayni icerik
     * olarak gorunmemesi icin markaya ozgu bir cerceve cumlesi ve meta ek
     * metni uretir. $facility, $brand (current_brand() dizisi) alir.
     */
    function facility_brand_framing($facility, array $brand): array
    {
        $voice = config("brand_voice.{$brand['slug']}", config('brand_voice.bakimevibul'));

        $location = trim(($facility->district ? $facility->district.', ' : '').($facility->city->name ?? ''));
        $category = $facility->category->name ?? 'kurum';

        $replace = [':category' => $category, ':location' => $location ?: 'bölgenizde'];

        return [
            'intro' => strtr($voice['facility_intro'], $replace),
            'meta_suffix' => strtr($voice['meta_suffix'], $replace),
        ];
    }
}

if (! function_exists('classify_phone_type')) {
    /**
     * Turk telefon numaralarinda cep hatlari "5" ile baslar (0532, 90532,
     * +90 532... hepsi normalize edildiginde "5..." olur); sabit hatlar il
     * kodlarindan biriyle baslar (0212, 0224, 0312, 0242 vb. - "2","3","4"
     * ile baslar). WhatsApp daveti sadece cep hatlarina anlamli oldugu icin
     * bu ayrim davet kuyruklarini otomatik bolmek icin kullanilir.
     */
    function classify_phone_type(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return 'none';
        }

        // Basta ulke/sifir on eklerini at: 90XXXXXXXXXX -> XXXXXXXXXX, 0XXXXXXXXXX -> XXXXXXXXXX
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 10) {
            return 'none';
        }

        return $digits[0] === '5' ? 'mobile' : 'landline';
    }
}

if (! function_exists('facility_invitation_statuses')) {
    function facility_invitation_statuses(): array
    {
        return [
            'not_started' => 'Henüz işlem yapılmadı',
            'opened' => 'WhatsApp açıldı',
            'sent' => 'Davet gönderildi',
            'claimed' => 'Kurum sahiplenme başlattı',
            'approved' => 'Kurum sahiplenildi',
            'do_not_contact' => 'Kurum istemiyor',
            'unreachable' => 'Ulaşılamadı',
            'wrong_number' => 'Numara yanlış',
            'landline_only' => 'Sadece sabit hat var',
            'contact_missing' => 'Telefon yok',
            'excluded' => 'Kamu/belediye veya davet dışı',
        ];
    }
}

if (! function_exists('facility_invitation_message')) {
    function facility_invitation_message(\App\Models\Facility $facility): string
    {
        return "Merhaba, {$facility->name} için bakimevibul.com / bakimeviara.com / bakimevleri.com üzerinde ücretsiz kurum profiliniz oluşturuldu.\n\n"
            .'Bilgilerinizi kontrol etmek, fotoğraf eklemek ve kurumunuzu sahiplenmek için bakimevleri.com sitesini açarak ön kayıtlı kurumlardan kolayca sahiplenme başvurusu yapabilirsiniz.'
            ."\n\nBu mesajı almak istemiyorsanız lütfen \"istemiyorum\" yazmanız yeterlidir.";
    }
}

if (! function_exists('facility_whatsapp_url')) {
    function facility_whatsapp_url(\App\Models\Facility $facility): ?string
    {
        if (classify_phone_type($facility->phone) !== 'mobile') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $facility->phone);
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            // zaten ulke koduyla birlikte
        } elseif (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        } else {
            $digits = '90'.$digits;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode(facility_invitation_message($facility));
    }
}
