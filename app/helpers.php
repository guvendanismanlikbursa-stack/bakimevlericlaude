<?php

if (! function_exists('email_taken_by_other_account_type')) {
    /**
     * 16 Temmuz 2026: aile ve kurum hesaplari ayri tablolarda oldugu icin
     * eskiden e-posta benzersizligi sadece KENDI tablosu icinde kontrol
     * ediliyordu - ayni e-posta ile hem aile hem kurum hesabi (veya
     * sahiplenme) acilabiliyordu. Hesap olusturulan/sahiplenme onaylanan
     * HER noktada bu kontrol kullanilmali. $excludeFamilyId/$excludeFacilityId
     * ayni hesabin kendi kaydini guncellerken yanlislikla kendine
     * carpmamasi icin (su an kullanilmiyor ama ileride gerekebilir).
     */
    function email_taken_by_other_account_type(string $email): ?string
    {
        if (\App\Models\FamilyUser::where('email', $email)->exists()) {
            return 'Bu e-posta zaten bir aile hesabına ait. Aynı e-posta ile kurum hesabı/sahiplenme yapılamaz.';
        }

        if (\App\Models\FacilityUser::where('email', $email)->exists()) {
            return 'Bu e-posta zaten bir kurum hesabına ait. Aynı e-posta ile aile hesabı oluşturulamaz.';
        }

        return null;
    }
}

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
    function service_section(?string $slug = null): array
    {
        $sections = service_sections();

        if ($slug && isset($sections[$slug])) {
            return $sections[$slug];
        }

        return null;
    }
}

if (! function_exists('active_service_section')) {
    function active_service_section(?string $slug = null, ?array $brand = null): array
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

        // "Ozel" ibaresi acikca ozel isletme oldugunu belirtir; asagidaki dernek
        // ve kamu anahtar kelimeleri bazen ozel isletme isimlerinde de gecebildigi
        // icin (orn. "Ozel Dernegim Ozel Egitim Merkezi", "Ozel ... Ogrenci Yurdu"),
        // "ozel" varsa dernek/kamu kontrolu atlanir. Belediye/vakif icin bu koruma
        // YOK, cunku o kelimeler mulkiyeti (sahiplik) belirtir, "ozel" markalamasi
        // olsa bile ust kurulus belediye/vakif ise gercekten oyledir.
        if (str_contains($n, 'ozel')) {
            return 'ozel';
        }

        if (str_contains($n, 'dernegi') || str_contains($n, 'dernek ') || str_contains($n, 'dernek-')) {
            return 'vakif';
        }

        $kamuKeywords = [
            'kaymakamlig', 'valilig', 'bakanlig', 'devlet hastanesi',
            'shcek', 'sosyal hizmet', 'il ozel idare', 'muduurlug',
            'mudurlug', 'kyk', 'kredi yurtlar kurumu', 'universite',
            'universitesi', 'il milli egitim', 'ilce milli egitim',
            'egitim ve arastirma hastanesi', 's.b.u.', 'sbu ',
            'ogretmenevi', 'halk egitim', 'toplum sagligi', 'aile sagligi',
            'sydv', 'emniyet', 'jandarma', 'ogrenci yurdu', 'yetistirme yurdu',
            'defterdarlig', 'garnizon', 'saglik ocagi', 'sgk', 'adliye',
            'cezaevi', 'ceza infaz', 'karakol', 'diyanet', 'buyuksehir',
            'baskanlig', 'kizilay', 'ilkokul', 'ortaokul', ' lisesi', ' lise ',
            'meb ', 'muftulug', 'kaymakamlik', 'mustafa kemal', 'esmek',
            'geri gonderme merkezi', 'yibo', 'ybo ',
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

        return url($section['hero_image'] ?? '/images/hero-yasli-bakim.webp');
    }
}

if (! function_exists('facility_card_image')) {
    function facility_card_image($facility, ?array $section = null): string
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
     * canliyaal projesinden tasindi: aile/kurum/admin panellerine gosterilecek
     * uygulama-ici bildirim olusturur. $notifiable bir FacilityUser, FamilyUser
     * veya Admin modeli olmalidir. Ayrica $notifiable->email doluysa (mail
     * ayarlari coktugunda kayit/onay gibi kritik akislarin cokmemesi icin
     * kurulan try/catch+Log::warning deseniyle) ayni bildirimin bir e-posta
     * kopyasi da gonderilir.
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

        $actionUrl = notification_action_url($notifiable, $type, $data);

        // 31 Temmuz 2026: 'claim_approved'/'registration_approved' icin
        // FacilityClaimController/FacilityRegistrationController zaten kendi
        // ozel, GERCEK giris bilgilerini (e-posta+gecici sifre) iceren daha
        // detayli bir mail gonderiyor (bkz. facility-claim-approved.blade.php,
        // facility-registration-approved.blade.php). Bu genel bildirim maili
        // de AYRICA gidince kullanici "Kurum kaydınız onaylandı" diye IKINCI
        // bir mail aliyor - butonu sadece BOS bir giris sayfasina goturuyor
        // (icinde sifre yok), kullanici hangi maildeki sifreyi kullanacagini
        // bilemeyip kafasi karisiyordu. Bu iki tur icin sadece e-posta kanali
        // bastirilir - uygulama-ici bildirim ve push bildirimi (asagida)
        // gibi hala calisir, sadece kafa karistiran ikinci/eksik mail gitmez.
        $suppressEmailForTypes = ['claim_approved', 'registration_approved'];

        if (! in_array($type, $suppressEmailForTypes, true) && ! empty($notifiable->email) && notification_channel_enabled($notifiable, $type, 'email')) {
            try {
                \Illuminate\Support\Facades\Mail::to($notifiable->email)->sendNow(
                    new \App\Mail\NotificationMail($title, $body, $actionUrl)
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bildirim maili gonderilemedi: ' . $e->getMessage(), [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->getKey(),
                    'type' => $type,
                ]);
            }
        }

        if (notification_channel_enabled($notifiable, $type, 'push')) {
            try {
                app(\App\Services\WebPushService::class)->sendToNotifiable($notifiable, $title, $body, $actionUrl);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Push bildirimi gonderilemedi: ' . $e->getMessage(), [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->getKey(),
                    'type' => $type,
                ]);
            }
        }
    }
}

if (! function_exists('notification_channel_enabled')) {
    /**
     * 12 Agustos 2026: kullanicinin talebi - "hangi olaylar icin e-posta/push
     * gelsin secemiyorum, hepsi ya acik ya kapali". family_users/facility_users.
     * notification_preferences (JSON) bos/eksikse VARSAYILAN HER ZAMAN ACIK
     * (opt-out) - boylece tercihini hic degistirmemis mevcut kullanicilar
     * icin davranis sessizce degismez, sadece acikca kapatilirsa susar.
     */
    function notification_channel_enabled($notifiable, string $type, string $channel): bool
    {
        $prefs = $notifiable->notification_preferences ?? [];

        return (bool) ($prefs[$type][$channel] ?? true);
    }
}

if (! function_exists('notification_preference_groups')) {
    /**
     * Bildirim tercihi ekraninda (aile/kurum profil sayfalari) TEK TEK
     * dahili "type" anahtarlarini degil, kullanicinin anlayacagi birkac
     * anlamli grubu gosterir - her grup kaydederken ayni ayari altindaki
     * TUM dahili turlere uygular (bkz. Family/Facility ProfileController).
     *
     * @return array<string, array{label: string, types: array<int, string>}>
     */
    function notification_preference_groups(string $for): array
    {
        if ($for === 'family') {
            return [
                'quotes' => ['label' => 'Yeni teklif geldiğinde', 'types' => ['quote_received']],
                'messages' => ['label' => 'Kurum mesaj gönderdiğinde', 'types' => ['new_message']],
                'updates' => ['label' => 'Talebim/sorum güncellendiğinde', 'types' => ['quote_accepted', 'quote_declined', 'question_answered']],
                'review_invite' => ['label' => 'Yorum yazma daveti', 'types' => ['review_invite']],
            ];
        }

        return [
            'leads' => ['label' => 'Yeni talep veya soru geldiğinde', 'types' => ['offer_request', 'new_question']],
            'messages' => ['label' => 'Aile mesaj gönderdiğinde', 'types' => ['new_message']],
            'wallet' => ['label' => 'Bakiye işlemlerimde', 'types' => ['topup_approved', 'topup_rejected']],
            'reminders' => ['label' => 'Hatırlatmalarda', 'types' => ['question_reminder']],
        ];
    }
}

if (! function_exists('notification_action_url')) {
    /**
     * Bildirim e-postasindaki "ilgili sayfaya git" butonunun hedefini
     * $type'a gore uretir. QUEUE_CONNECTION=sync oldugu icin bu her zaman
     * orijinal HTTP request'in icinde, dogru marka baglaminda calisir
     * (bkz. brand_route() helper) - ileride gercek arka plan kuyruguna
     * gecilirse bu varsayim gecersiz olur, o zaman URL notify_user()
     * cagrisi sirasinda (kuyruga girmeden once) sabit string olarak
     * hesaplanip tasinmali.
     */
    function notification_action_url($notifiable, string $type, array $data): ?string
    {
        try {
            $isFamilyUser = $notifiable instanceof \App\Models\FamilyUser;

            return match ($type) {
                // 16 Temmuz 2026: 'new_question' burada 'offer_request' ile ayni
                // koldaydi ama hicbir zaman offer_request_id tasimiyor (bkz.
                // FacilityQuestionController) - link hep null'a duserdu, sorulan
                // soruya gitmek yerine hicbir yere gitmiyordu. Ayrica facility
                // henuz teklif vermemisken 'offer_request' (yeni firsat)
                // bildirimini tiklayip dogrudan mesaj thread'ine gitmesi - rakip
                // kurum mesaj sizintisi fix'inden sonra - artik 403 veriyordu;
                // teklif verebilecegi panele yonlendirilmesi dogrusu.
                'new_question' => brand_route('facility.questions.index'),
                'offer_request' => brand_route('facility.dashboard'),
                // 'quote_received': yayin talebinde teklif kabul edilene kadar
                // mesajlasma kapali oldugundan (ayni fix), aile burada da
                // dogrudan thread'e degil, teklifi gorup kabul edebilecegi
                // panele yonlendirilir.
                'quote_received' => brand_route('family.dashboard'),
                // 21 Temmuz 2026: teklif kabul edilince mesajlasma o kurum icin
                // acildigindan (accepted_quote_id sahipligi), kazanan kurum
                // dogrudan thread'e yonlendirilebilir; kaybeden kurumun ise
                // artik erisimi olmadigindan panele yonlendirilir.
                'quote_accepted' => isset($data['offer_request_id'])
                    ? brand_route('facility.thread', $data['offer_request_id']) : brand_route('facility.dashboard'),
                'quote_declined' => brand_route('facility.dashboard'),
                'question_answered' => isset($data['facility_slug'])
                    ? brand_route('facilities.show', $data['facility_slug']) : null,
                'question_reminder' => brand_route('facility.questions.index'),
                'review_invite' => isset($data['facility_slug']) ? brand_route('facilities.show', $data['facility_slug']) : null,
                'new_message' => isset($data['offer_request_id'])
                    ? brand_route($isFamilyUser ? 'family.thread' : 'facility.thread', $data['offer_request_id']) : null,
                'claim_approved', 'registration_approved' => brand_route('facility.login'),
                'topup_approved', 'topup_rejected' => brand_route('facility.wallet.index'),
                'claim_submitted' => route('admin.claims.index'),
                'registration_submitted' => route('admin.registrations.index'),
                'contact_message_submitted' => route('admin.contact-messages.index'),
                'topup_requested' => route('admin.topups.index'),
                'chat_message' => isset($data['chat_thread_id']) ? route('admin.chat.show', $data['chat_thread_id']) : route('admin.chat.index'),
                default => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
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

if (! function_exists('guide_page_content')) {
    /**
     * Rehber (/rehber) ve fiyat rehberi (/fiyat-rehberi) sayfalari icin markaya
     * ozgu, coğrafya+kategoriye gore FARKLILASAN giris metni uretir.
     *
     * Neden gerekli: bu sayfa ailesi 81 il x ~30 ortalama ilce x kategori
     * kombinasyonuna kadar olceklenebiliyor (bkz. SitemapController) ama
     * eskiden intro metni sadece marka+bolum bazinda (config/site_content.php)
     * sabitti - yani binlerce farkli il/ilce/kategori sayfasi BIREBIR AYNI
     * paragrafi gosteriyordu (Google duplicate/thin-content riski). Bu
     * fonksiyon iki katmanli bir cozum uygular:
     *   1) Marka basina 4 alternatif cumle sablonu (config/brand_voice.php
     *      'guide_intro_templates') arasindan, sayfanin kendi kimligine
     *      (il+ilce+kategori+marka) gore DETERMINISTIK (crc32 hash) secim -
     *      ayni URL her zaman ayni metni uretir (indexleme tutarliligi),
     *      farkli URL'ler farkli sablona dusme egilimindedir.
     *   2) Sablona GERCEK, o sayfaya ozgu bir sayi (kurum sayisi) enjekte
     *      edilir - facility_brand_framing() ile ayni "ayni ham veri +
     *      markaya ozgu cerceve" deseninin coğrafya sayfalarina tasinmis hali.
     */
    function guide_page_content(array $brand, string $cityName, ?string $districtName, string $categoryLabel, int $facilityCount): array
    {
        $voice = config("brand_voice.{$brand['slug']}", config('brand_voice.bakimevibul'));
        $templates = $voice['guide_intro_templates'] ?? [];

        if (empty($templates)) {
            return ['intro' => ''];
        }

        $location = trim(($districtName ? $districtName.', ' : '').$cityName);
        $index = crc32($cityName.'|'.$districtName.'|'.$categoryLabel.'|'.$brand['slug']) % count($templates);

        $replace = [
            ':location' => $location ?: 'bölgenizde',
            ':category' => $categoryLabel,
            ':count' => max($facilityCount, 0),
        ];

        return ['intro' => strtr($templates[$index], $replace)];
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
    // 30 Temmuz 2026: admin panelinden (Platform Ayarlari) elle
    // degistirilebilir - bkz. SettingController, admin.settings.edit.
    // {kurum_adi} yer tutucusu gonderim aninda gercek kurum adiyla
    // degistirilir. Ayar hic girilmemisse eski sabit metin varsayilan olarak kullanilir.
    function facility_invitation_message(\App\Models\Facility $facility): string
    {
        $template = \App\Models\Setting::get('facility_invitation_message', facility_invitation_message_default());

        return str_replace('{kurum_adi}', $facility->name, $template);
    }
}

if (! function_exists('facility_invitation_message_default')) {
    function facility_invitation_message_default(): string
    {
        return "Merhaba, {kurum_adi} için bakimevibul.com / bakimeviara.com / bakimevleri.com üzerinde ücretsiz kurum profiliniz oluşturuldu.\n\n"
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

if (! function_exists('sanitize_admin_html')) {
    /**
     * ContentPage::body gibi admin tarafindan yazilan ama sitede TUM
     * ziyaretcilere {!! !!} (escape'siz) gosterilen HTML alanlari icin.
     * 21 Temmuz 2026 guvenlik denetiminde bulundu: admin hesabi ele
     * gecirilirse (veya yanlislikla yapistirilan bir script) bu alana
     * <script>/onclick vb. yazilip TUM sitenin ziyaretcilerine calistirilan
     * stored XSS'e donusebiliyordu. Bu icerik turu sadece baslik/paragraf/
     * liste/kalin-italik metin icerdigi icin (bkz. page.blade.php .page-
     * content CSS'i) - hicbir zaman link/gorsel/stil gerekmiyor - once
     * izin verilen etiket disindakiler tamamen kaldirilir (strip_tags),
     * SONRA kalan etiketlerdeki TUM ozellikler (onclick gibi olay
     * yakalayicilar dahil) silinir. HTML Purifier gibi bir kutuphane
     * kurulu degil (composer.lock/vendor degisikligi FTP-only bu hostingte
     * agir bir deploy adimi olurdu) - bu dar/sabit etiket kumesi icin
     * kutuphanesiz de guvenli, cunku hicbir izinli etikte ozellige ihtiyac yok.
     */
    function sanitize_admin_html(string $html): string
    {
        $allowedTags = '<h2><h3><h4><p><ul><ol><li><strong><em><b><i><br>';
        $stripped = strip_tags($html, $allowedTags);

        return preg_replace('/<([a-z0-9]+)[^>]*>/i', '<$1>', $stripped);
    }
}

if (! function_exists('notify_admin_of_exception')) {
    /**
     * Production'da beklenmeyen hatalari config('platform.admin_alert_email')'e mail
     * atarak bildirir (bkz. bootstrap/app.php withExceptions). Beklenen/gurultulu
     * istisnalar (404, validation, throttle, CSRF vb.) haric tutulur. Ayni hata
     * (sinif+dosya+satir) 1 saat icinde tekrar ederse spam olmasin diye cache ile
     * susturulur - kritik hata bize ancak saatte bir mail olarak ulasir.
     */
    function notify_admin_of_exception(\Throwable $e): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $ignored = [
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
            \Illuminate\Session\TokenMismatchException::class,
        ];
        foreach ($ignored as $class) {
            if ($e instanceof $class) {
                return;
            }
        }

        $cacheKey = 'admin-alert:'.md5(get_class($e).'|'.$e->getFile().'|'.$e->getLine());
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHour());

        $host = request()?->getHost() ?? 'CLI';
        $url = request()?->fullUrl() ?? 'CLI/console';

        $body = sprintf(
            "Hata: %s\nMesaj: %s\nDosya: %s:%d\nURL: %s\nZaman: %s\n\nStack trace (ilk 20 satir):\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $url,
            now()->toDateTimeString(),
            implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 20))
        );

        try {
            \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($host) {
                $message->to(config('platform.admin_alert_email'))
                    ->subject('['.config('app.name').'] Kritik hata: '.$host);
            });
        } catch (\Throwable $mailError) {
            // Mail gonderimi de basarisiz olursa (ör. mail sunucusu coktuyse) sessizce
            // yut - hata zaten Laravel'in varsayilan log kanalina yazilmis olacak.
        }

        // 11 Agustos 2026: kullanicinin acik talebi - "herhangi bir hatada
        // admin paneline dussun ve admine bildirim gelsin". Yukaridaki mail
        // (admin_alert_email) genelde bana/gelistiriciye gidiyordu, admin
        // panelinde HICBIR kalici iz birakmiyordu. Ayni throttle penceresini
        // (yukaridaki cache kontrolu) kullanarak simdi ayrica platform_errors
        // tablosuna da kaydediyor ve GERCEK admin hesap(lar)ina (Admin
        // tablosu, admin panelini kullanan kisi) mail atiyor.
        record_platform_error(
            'exception',
            get_class($e).' — '.$host,
            $body,
            ['exception_class' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine(), 'url' => $url]
        );
    }
}

if (! function_exists('record_platform_error')) {
    /**
     * Genel amacli hata kayit mekanizmasi: hem notify_admin_of_exception()
     * (tum beklenmeyen uygulama hatalari) hem de ozel kontrol komutlari
     * (ör. gallery:check-health) tarafindan kullanilir. platform_errors
     * tablosuna KALICI bir kayit birakir (admin panelinde "Hatalar"
     * ekraninda gorunur) ve her Admin hesabina (gercek admin panelini
     * kullanan kisi) mail atar.
     */
    function record_platform_error(string $source, string $title, string $message, array $context = []): void
    {
        \App\Models\PlatformError::create([
            'source' => $source,
            'title' => $title,
            'message' => $message,
            'context' => $context,
        ]);

        \App\Models\Admin::all()->each(function (\App\Models\Admin $admin) use ($title, $message, $source) {
            try {
                \Illuminate\Support\Facades\Mail::to($admin->email)->send(
                    new \App\Mail\PlatformErrorAlertMail($title, $message, $source)
                );
            } catch (\Throwable $mailError) {
                \Illuminate\Support\Facades\Log::warning('Hata bildirim maili gonderilemedi: '.$mailError->getMessage(), ['admin_id' => $admin->id]);
            }
        });
    }
}

if (! function_exists('detect_chat_section')) {
    /**
     * Canli destek sohbetinde misafirin yazdigi metne bakarak hangi bolume
     * (yasli-bakim/cocuk/rehabilitasyon) ilgi duydugunu tahmin eder - sadece
     * bir ONERI karti icin kullanilir, asla otomatik yonlendirme yapmaz
     * (bkz. config/chat_routing.php). Eslesme yoksa null doner.
     *
     * @return array{slug: string, label: string}|null
     */
    function detect_chat_section(?string $text): ?array
    {
        if (! $text) {
            return null;
        }

        $normalized = mb_strtolower($text, 'UTF-8');

        foreach (config('chat_routing', []) as $slug => $section) {
            foreach ($section['keywords'] as $keyword) {
                if (str_contains($normalized, mb_strtolower($keyword, 'UTF-8'))) {
                    return ['slug' => $slug, 'label' => $section['label']];
                }
            }
        }

        return null;
    }
}
