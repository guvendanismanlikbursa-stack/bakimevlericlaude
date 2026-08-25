<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityImage;
use App\Services\FacilityArchiveService;
use App\Services\GeocodingService;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacilityController extends Controller
{
    use RedirectsOutOfRangePagination;

    private const MAX_GALLERY_IMAGES = 10;
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request, includeCategory: true)->with(['city', 'category', 'images', 'facilityUsers']);

        $facilities = $query->latest()->paginate(15)->withQueryString();
        $ownershipTypes = ['ozel' => 'Özel', 'kamu' => 'Kamu', 'belediye' => 'Belediye', 'vakif' => 'Vakıf'];

        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilities)) {
            return $redirect;
        }

        // 12 Agustos 2026: admin panelindeki arama/filtre kutulari da (aynen
        // site tarafi gibi) sayfa yenilenmeden aninda sonuc guncelliyor -
        // kategori kirilimi gibi agir aggregate'i her tus vurusunda tekrar
        // hesaplamamak icin bu yolda atlaniyor, sadece tablo+sayfalama doner.
        if ($request->ajax()) {
            // 18 Agustos 2026: kullanicinin bildirdigi gercek hata - kurum
            // gorseli eklerken tarayici GERI tusuna basinca ekranda ham
            // JSON metni ({"count":...,"html":"..."}) goruldu. Kok neden:
            // bu AJAX (fetch) yaniti hicbir cache basligi tasimiyordu,
            // adres cubugu location-filter-script.blade.php'de
            // history.replaceState ile URL'i degistiriyor - tarayici GERI
            // tusuna basinca o URL'e AYNI (fetch'ten kalma) yaniti
            // sunucuya sormadan onbellekten geri getirebiliyordu. no-store
            // bu onbelleklemeyi tamamen engeller.
            return response()->json([
                'count' => $facilities->total(),
                'html' => view('admin.facilities._results', compact('facilities', 'ownershipTypes'))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $brands = config('brands.brands');
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);

        // Secilen kategori disindaki tum filtreler uygulanmis haliyle, kategori
        // bazinda kirilim: admin "Tum Kategoriler" secili iken bile bu filtreye
        // (il/ilce/marka/kurulus turu/sahiplenme) uyan kurumlarin kategoriye gore
        // dagilimini gorebilsin.
        $categoryBreakdown = $this->filteredQuery($request, includeCategory: false)
            ->selectRaw('facility_category_id, count(*) as total')
            ->groupBy('facility_category_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$categories->firstWhere('id', $row->facility_category_id)?->name ?? 'Diğer' => $row->total])
            ->sortByDesc(fn ($count) => $count)
            ->all();

        return view('admin.facilities.index', compact('facilities', 'brands', 'cities', 'categories', 'districtMap', 'ownershipTypes', 'categoryBreakdown'));
    }

    private function filteredQuery(Request $request, bool $includeCategory)
    {
        $query = Facility::query();

        if ($request->filled('brand')) {
            $scope = config("brands.brands.{$request->brand}.category_scope", []);
            $query->forBrand($scope);
        }

        if ($request->filled('claim_status')) {
            $query->where('is_claimed', $request->claim_status === 'claimed');
        }

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($includeCategory && $request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('ownership_type')) {
            $query->where('ownership_type', $request->ownership_type);
        }

        // 28 Temmuz 2026: kurum ismiyle arama - toplu Excel ice aktarmada
        // yanlis kategoriye dusmus kurumlari (ör. "kres"/"rehabilitasyon"
        // gecen bir isim Yasli Bakim Evi kategorisinde) admin'in yapisal
        // filtrelerle degil, isim/anahtar kelimeyle bulup duzeltebilmesi
        // icin (bkz. miscategory-scan/-fix ops uclariyla ayni ihtiyac).
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        return $query;
    }

    public function create(Request $request)
    {
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $serviceSections = service_sections();

        $facility = new Facility($request->only([
            'name',
            'city_id',
            'district',
            'address',
            'lat',
            'lng',
            'phone',
            'email',
            'description',
            'capacity',
            'price_min',
            'price_max',
            'cover_image',
            'facility_category_id',
        ]));

        return view('admin.facilities.form', [
            'facility' => $facility,
            'cities' => $cities,
            'categories' => $categories,
            'serviceSections' => $serviceSections,
        ]);
    }

    public function store(Request $request, GeocodingService $geocodingService)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['services'] = $this->parseServices($request->input('services_raw'), $request->input('services', []));
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_claimed'] = false;
        $data['district_id'] = $this->resolveDistrictId($data['district'] ?? null, $data['city_id']);

        // 17 Agustos 2026: kullanicinin talebi - admin panelden elle kurum
        // eklerken koordinat girmez, sadece adres yazar; girilen adrese gore
        // otomatik konumlandirilmazsa kurum sayfasinda Google Haritalar hic
        // gorunmez (bkz. Facility::hasPreciseLocation()).
        if (! filled($data['lat'] ?? null) && ! filled($data['lng'] ?? null) && filled($data['address'] ?? null)) {
            $cityName = City::find($data['city_id'])?->name;
            $coords = $geocodingService->geocodeAddress($data['address'], $data['district'] ?? null, $cityName);
            if ($coords) {
                $data['lat'] = $coords['lat'];
                $data['lng'] = $coords['lng'];
            }
        }

        $facility = Facility::create($data);

        $this->storeUploadedImages($request, $facility);

        return redirect()->route('admin.facilities.edit', $facility)->with('success', 'Kurum ön kayıt olarak eklendi. Şimdi demo görseller ekleyebilirsiniz.');
    }

    public function edit(Request $request, Facility $facility)
    {
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $serviceSections = service_sections();
        $facility->load(['images', 'facilityUsers', 'claims' => fn ($q) => $q->latest(), 'balanceLogs', 'category']);
        // 18 Agustos 2026: kullanicinin talebi - filtrelenmis listeden gelip
        // ayni kurumda birden fazla gorsel ekleyip/silen admin artik HER
        // kayittan sonra listeye geri atilmiyor (bkz. update() ayni tarihli
        // yorum) - bu yuzden "hangi filtreli listeden geldi" bilgisi artik
        // query string uzerinden (?return_to=...) TASINIYOR, aksi halde
        // ikinci kayittan sonra url()->previous() bu DUZENLEME sayfasinin
        // kendisini gosterir ve orijinal liste linki kaybolurdu.
        $returnTo = $this->safeReturnTo($request->query('return_to')) ?? $this->safeReturnTo(url()->previous());

        return view('admin.facilities.form', compact('facility', 'cities', 'categories', 'serviceSections', 'returnTo'));
    }

    public function update(Request $request, Facility $facility, GeocodingService $geocodingService)
    {
        $data = $this->validateData($request, $facility->id);

        if ($data['name'] !== $facility->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $facility->id);
            // 17 Agustos 2026: kullanicinin talebi - eski, artik canli
            // gorulmeyecek bir slug (ör. "-curltest" gibi teknik bir ek
            // tasiyan eski bir kayit) Google'da indekslenmis/paylasilmis
            // olabilir; isim degisip slug yenilenince eski adres sessizce
            // 404 vermesin diye SADECE en son eski slug saklanir (tam
            // gecmis degil - basit tutmak icin) ve Public\FacilityController::
            // show() bulamayinca buraya bakip 301 ile yeni adrese yonlendirir.
            $data['old_slug'] = $facility->slug;
        }

        // 17 Agustos 2026: kullanicinin talebi (bkz. store() ayni tarihli
        // yorum) - admin koordinati elle degistirmemis (lat/lng bos
        // birakilmis) ama adresi guncellemis/ilk kez girmisse, otomatik
        // yeniden konumlandir. Admin daha once elle veya otomatik dogru bir
        // koordinat girmisse ve adresi degistirmemisse DOKUNULMAZ.
        if (! filled($data['lat'] ?? null) && ! filled($data['lng'] ?? null)
            && filled($data['address'] ?? null)
            && $data['address'] !== $facility->address) {
            $cityName = City::find($data['city_id'])?->name;
            $coords = $geocodingService->geocodeAddress($data['address'], $data['district'] ?? null, $cityName);
            if ($coords) {
                $data['lat'] = $coords['lat'];
                $data['lng'] = $coords['lng'];
            }
        }

        $data['services'] = $this->parseServices($request->input('services_raw'), $request->input('services', []));
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        // 14 Agustos 2026: kullanicinin talebi - "ilce senkronizasyonu"
        // (bkz. DataQualityService::districtAudit() - metin/FK uyumsuzlugu
        // sorunu). Eslesme bulunamazsa MEVCUT district_id'ye DOKUNULMAZ -
        // yanlislikla daha once dogru kurulmus bir baglantiyi kirmamak icin.
        $resolvedDistrictId = $this->resolveDistrictId($data['district'] ?? null, $data['city_id']);
        if ($resolvedDistrictId !== null) {
            $data['district_id'] = $resolvedDistrictId;
        }
        // 14 Agustos 2026: kullanicinin talebi - admin telefon numarasi
        // ekleyip/degistirip kaydettiginde, kurum otomatik olarak dogru
        // gruba (cep/sabit hat) siniflandirilsin - bu alan WhatsApp davet
        // kuyrugunu belirliyor (bkz. helpers.php classify_phone_type()),
        // once sadece toplu "Veri Denetimi" taramasiyla duzeltiliyordu.
        $data['phone_type'] = classify_phone_type($data['phone'] ?? null);

        $facility->update($data);

        $this->storeUploadedImages($request, $facility);

        // 14 Agustos 2026: kullanicinin talebi - "kaydet'e basinca 2 defa
        // geri tusuna basmam gerekiyor" -> ilk cozum: her zaman dogrudan
        // filtrelenmis listeye donmekti.
        //
        // 18 Agustos 2026: kullanicinin talebi uzerine bu GERI ALINDI - bir
        // kuruma gorsel ekleyip ayni ziyarette baska bir gorseli SILMEK
        // isteyen admin, kaydettikten sonra listeye atilinca ayni kurumun
        // duzenleme sayfasina TEKRAR girmek zorunda kaliyordu. Artik kayittan
        // sonra HER ZAMAN ayni duzenleme sayfasinda kalinir (birden fazla
        // gorsel islemi tek ziyarette rahatca yapilabilir); filtrelenmis
        // listeye donus artik sayfadaki GORUNUR "◀ Listeye dön" butonuyla,
        // admin isini bitirdiginde kendi kontrolunde yapilir - orijinal
        // "2 defa geri tusu" sikayeti de boylece cozulmus olur (tarayici
        // geri tusuna hic gerek kalmiyor).
        $returnTo = $this->safeReturnTo($request->input('return_to'));

        return redirect(route('admin.facilities.edit', $facility).($returnTo ? '?return_to='.urlencode($returnTo) : ''))
            ->with('success', 'Kurum güncellendi.');
    }

    /**
     * 14 Agustos 2026: kullanicinin talebi - kurumun serbest metin "district"
     * alaniyla "districts" tablosundaki district_id FK'i senkron tutar (bkz.
     * DataQualityService::districtAudit() - "metin dolu, FK bos/uyumsuz"
     * sorunu). Kucuk/buyuk harf farkini yoksayar; eslesme yoksa null doner
     * (cagiran taraf bu durumda mevcut degere DOKUNMAZ).
     */
    private function resolveDistrictId(?string $districtText, int $cityId): ?int
    {
        $districtText = trim((string) $districtText);
        if ($districtText === '') {
            return null;
        }

        return District::where('city_id', $cityId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($districtText)])
            ->value('id');
    }

    /**
     * Sadece /admin/kurumlar (filtreli liste) ile baslayan, ayni siteye ait
     * URL'leri gecerli sayar - acik yonlendirme (open redirect) riskini
     * onlemek icin disaridan gelen return_to degeri asla dogrudan
     * guvenilmez.
     */
    private function safeReturnTo(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $allowed = route('admin.facilities.index');

        return str_starts_with($url, $allowed) ? $url : null;
    }

    public function destroy(Facility $facility, FacilityArchiveService $archiveService, \App\Services\FacilityCascadeService $cascadeService)
    {
        $archivePath = $archiveService->archiveBeforeDelete($facility);

        // 21 Temmuz 2026: Facility soft-delete edilince ('deleted_at' set
        // edilince), SoftDeletes'in global scope'u yuzunden $user->facility
        // iliskisi artik null doner - o kurumun yetkilisi panele girmeye
        // calisinca "isInBrandScope() on null" ile 500 hatasi aliyordu
        // (temiz bir "hesabiniz aktif degil" mesaji yerine). Kurum silinince
        // yetkilileri de askiya alinir - ayni suspend deseni OfferRequest
        // Controller::suspendFacility'de zaten kullaniliyor.
        \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);

        // 17 Agustos 2026: kullanicinin bildirdigi hata - kurum silinince
        // bagli sahiplenme basvurusu/teklif talebi/bakiye yuklemesi kayitlari
        // kendi admin listelerinde "kurum silinmemis gibi" gorunmeye devam
        // ediyordu. Bkz. FacilityCascadeService ayni tarihli yorum.
        $cascadeService->softDeleteRelated($facility);

        $facility->delete();

        return back()->with('success', 'Kurum silindi ve silinenler arşivine taşındı: '.$archivePath);
    }

    public function revertToPreRegistered(Facility $facility)
    {
        // 25 Agustos 2026: kullanicinin bildirdigi gercek hata - "Yerinde
        // Sahiplendirme" denemeleri sonrasi buradan "ön kayıtlı"ya
        // dondurulen bir kurum, her sahiplendirme+geri alma turunde
        // kazandigi sahiplenme bonus hakkini (free_quote_credits) ASLA
        // kaybetmiyordu - ayni kurum tekrar sahiplendirildiginde ustune
        // tekrar bonus ekleniyor, sayi sinirsiz birikiyordu (5 -> 10 -> 15...).
        // "Ön kayıtlı" bir kurumun temel hali her zaman free_quote_credits=0/
        // balance=0'dir (bkz. DataImportRowApprovalService, DataExtractorImportService),
        // bu yuzden geri alirken de ayni temele donulur - kaybolan tutar
        // Bakiye/Hak Gecmisi'ne ayri bir kayit olarak dusulur ki iz kaybolmasin.
        \Illuminate\Support\Facades\DB::transaction(function () use ($facility) {
            $locked = Facility::whereKey($facility->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->balance != 0 || (int) $locked->free_quote_credits != 0) {
                \App\Models\BalanceLog::create([
                    'facility_id' => $locked->id,
                    'type' => 'claim_reverted',
                    'amount' => -1 * (float) $locked->balance,
                    'credits_amount' => -1 * (int) $locked->free_quote_credits,
                    'balance_after' => 0,
                    'credits_after' => 0,
                    'admin_id' => session('admin_id'),
                    'note' => 'Kurum ön kayıtlıya döndürüldü, sahiplenme bonusu geri alındı.',
                ]);
            }

            $locked->update([
                'is_claimed' => false,
                'claimed_at' => null,
                'source' => 'google_maps_veri_cekici',
                'balance' => 0,
                'free_quote_credits' => 0,
            ]);
        });

        // 30 Temmuz 2026: bir kurum yanlislikla/deneme amacli sahiplenilip
        // sonra buradan "ön kayıtlı"ya döndürüldüğünde, o sahiplenmeyle
        // acilan FacilityUser hesabi(lari) askiya alinmiyordu - kurum artik
        // "sahipsiz" gorunse bile o hesap Kurum Yetkilileri'nde "Aktif"
        // kalmaya ve kurum panelinden giris yapip yonetmeye devam
        // edebiliyordu. destroy()'daki ayni suspend deseni burada da
        // uygulanir.
        \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);

        return back()->with('success', 'Kurum ön kayıtlı hale getirildi, sahiplenme bonusu sıfırlandı ve bağlı kurum yetkilisi hesapları askıya alındı.');
    }

    /**
     * 19 Agustos 2026: kullanicinin talebi - kurumlari yerinde ziyaret edip
     * fotograf/durum kontrolu yapan admin, kurum yetkilisi o an sahiplenmek
     * isterse normal akistaki (basvuru + belge yukleme + admin onayi + mail
     * bekleme) surece GEREK DUYMADAN dogrudan buradan gecici sifre
     * verebilsin diye. GUVENLIK: belge/online basvuru burada YOKTUR cunku
     * kimlik dogrulamasi zaten YUZ YUZE (admin bizzat orada) yapiliyor - bu
     * normal FacilityClaim akisinin YERINE degil, ONA ALTERNATIF, sadece
     * admin oturumundan tetiklenebilen ayri bir yoldur. Islem audit log'a
     * "yerinde sahiplendirme" olarak acikca isaretlenir.
     */
    public function instantClaim(Request $request, Facility $facility)
    {
        abort_if($facility->is_claimed, 400, 'Bu kurum zaten sahiplenilmiş.');

        $data = $request->validate([
            'applicant_name' => 'required|string|max:120',
            'applicant_email' => 'required|email|max:150',
            'applicant_phone' => 'required|string|max:30',
        ]);

        if ($error = email_taken_by_other_account_type($data['applicant_email'])) {
            return back()->withErrors(['applicant_email' => $error]);
        }

        if (\App\Models\FacilityUser::where('email', $data['applicant_email'])->exists()) {
            return back()->withErrors(['applicant_email' => 'Bu e-posta zaten bir kurum hesabına ait. Başka bir e-posta gerekiyor.']);
        }

        // 25 Agustos 2026: bkz. Facility\TeamController::invite() ayni
        // tarihli yorum - sembolsuz sifre, mailde tam secilebilir/kopyalanabilir.
        $temporaryPassword = Str::password(14, symbols: false);
        $freeCredits = (int) config('platform.free_claim_credits', 5);

        \Illuminate\Support\Facades\DB::transaction(function () use ($facility, $data, $temporaryPassword, $freeCredits) {
            $facility = Facility::whereKey($facility->id)->lockForUpdate()->firstOrFail();
            abort_if($facility->is_claimed, 400, 'Bu kurum zaten sahiplenilmiş.');

            $facilityUser = \App\Models\FacilityUser::create([
                'facility_id' => $facility->id,
                'name' => $data['applicant_name'],
                'email' => $data['applicant_email'],
                'phone' => $data['applicant_phone'],
                'password' => \Illuminate\Support\Facades\Hash::make($temporaryPassword),
                'must_change_password' => true,
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            $update = [
                'is_claimed' => true,
                'claimed_at' => now(),
                'free_quote_credits' => (int) $facility->free_quote_credits + $freeCredits,
                'invitation_status' => 'approved',
                'invitation_status_at' => now(),
            ];
            if (facility_featured_campaign_active()) {
                $update['is_featured'] = true;
            }
            $facility->update($update);

            FacilityImage::where('facility_id', $facility->id)
                ->where('path', 'like', 'facilities/demo/%')
                ->delete();

            \App\Models\BalanceLog::create([
                'facility_id' => $facility->id,
                'type' => 'claim_bonus_credits',
                'amount' => 0,
                'credits_amount' => $freeCredits,
                'balance_after' => $facility->balance,
                'credits_after' => $facility->free_quote_credits,
                'admin_id' => session('admin_id'),
                'note' => 'Yerinde (elle) sahiplendirme bonus hakkı.',
            ]);

            log_admin_event('facility_instant_claimed', $facility, [
                'facility_user_email' => $facilityUser->email,
            ]);
        });

        $facilityUser = \App\Models\FacilityUser::where('email', $data['applicant_email'])->firstOrFail();

        // 25 Agustos 2026: kurumun hangi markaya ait sayilacagini (hosgeldin
        // maili/giris linki icin) bulan mantik artik tek bir yerde -
        // facility_login_brand_slug() (bkz. app/helpers.php ayni tarihli
        // yorum). Burada zaten hicbir basvuru kaydı YOK (yerinde
        // sahiplendirme), o yuzden fonksiyon kategoriye gore tahmine
        // dusecek - bu onceki davranisla ayni.
        $brandSlug = facility_login_brand_slug($facility);
        $loginUrl = facility_brand_login_url($brandSlug);

        // 25 Agustos 2026: kullanicinin bildirdigi gercek hata - kurum
        // gercekten sahiplendiriliyordu ama giris bilgisi ekranda hic
        // GORUNMUYORDU (back()->with() ile tasinan ozel 'instant_claim_
        // credentials' anahtari nedense kayboluyordu) VE eski
        // FacilityWelcomeMail'de zaten sifre alani YOKTU (mail gitse bile
        // sifreyi icermezdi). Kullanicinin acik talebi uzerine: (1) zaten
        // calistigi kanitlanmis FacilityPasswordManuallyResetMail (bkz.
        // Admin\UserController::resetFacilityUserPassword - AYNI sablon,
        // sifreyi gercekten iceriyor) kullanilir, (2) back() yerine ayni
        // sayfaya KESIN/degismez bir redirect() yapilir (back()'in
        // guvendigi HTTP Referer/onceki-URL izlemesi bir sekilde
        // basarisiz olmus olabilir), (3) 'success' mesajina da (admin
        // layout'ta HER zaman calistigi kanitlanmis, genel bir mekanizma)
        // ayni bilgiler yedek olarak eklenir - ozel kutu bir sekilde yine
        // gorunmezse bile bilgi KESINLIKLE ekranda bir yerde olsun.
        try {
            \Illuminate\Support\Facades\Mail::to($facilityUser->email)->sendNow(
                new \App\Mail\FacilityPasswordManuallyResetMail($facilityUser, $temporaryPassword, $loginUrl)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Yerinde sahiplendirme giris bilgisi maili gonderilemedi: '.$e->getMessage(), ['facility_id' => $facility->id]);
            notify_admin_of_exception($e);
        }

        try {
            \Illuminate\Support\Facades\Mail::to($facilityUser->email)->sendNow(
                new \App\Mail\FacilityWelcomeMail($facility->fresh(), $facilityUser->email, config('brands.brands.'.$brandSlug.'.name', $brandSlug), $loginUrl)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Yerinde sahiplendirme hos geldin maili gonderilemedi: '.$e->getMessage(), ['facility_id' => $facility->id]);
        }

        try {
            \App\Http\Controllers\Facility\EmailVerificationController::send($facilityUser, config("brands.brands.{$brandSlug}"));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Yerinde sahiplendirme dogrulama maili gonderilemedi: '.$e->getMessage(), ['facility_id' => $facility->id]);
        }

        return redirect()->route('admin.facilities.edit', $facility)
            ->with('instant_claim_credentials', [
                'email' => $facilityUser->email,
                'password' => $temporaryPassword,
                'login_url' => $loginUrl,
                'whatsapp_url' => facility_whatsapp_url_with_message($facility, "Merhaba, \"{$facility->name}\" kurum panelinize giriş bilgileriniz:\n\nGiriş adresi: {$loginUrl}\nE-posta: {$facilityUser->email}\nGeçici şifre: {$temporaryPassword}\n\nİlk girişte yeni bir şifre belirlemeniz istenecektir."),
            ])
            ->with('success', "Kurum sahiplendirildi, giriş bilgileri {$facilityUser->email} adresine gönderildi. E-posta: {$facilityUser->email} · Geçici şifre: {$temporaryPassword}");
    }

    /**
     * 19 Agustos 2026: kullanicinin talebi - 10 gorselden hangisinin ANA
     * (kapak) gorsel oldugu secilebilsin. Bkz. Facility::primaryImage().
     */
    public function setPrimaryImage(FacilityImage $image)
    {
        FacilityImage::where('facility_id', $image->facility_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Ana görsel güncellendi.');
    }

    public function deleteImage(FacilityImage $image, \App\Services\CrossDomainImageSync $imageSync)
    {
        Storage::disk('public')->delete($image->path);
        $imageSync->syncDelete($image->path);
        $image->delete();

        return back()->with('success', 'Görsel silindi.');
    }

    private function storeUploadedImages(Request $request, Facility $facility): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $files = collect($request->file('images', []));
        $start = $facility->images()->count();

        if ($start + $files->count() > self::MAX_GALLERY_IMAGES) {
            $remaining = max(0, self::MAX_GALLERY_IMAGES - $start);
            throw ValidationException::withMessages(['images' => "Bir kurum için en fazla 10 görsel eklenebilir. Kalan yükleme hakkı: {$remaining}."]);
        }

        // 3 Agustos 2026: bkz. Facility\ProfileController::uploadImage ayni
        // yorum - yazimdan sonra dosyanin gercekten var oldugu dogrulanir.
        foreach ($files as $i => $file) {
            $path = app(ImageCompressionService::class)->store($file, 'facilities');
            if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                \Illuminate\Support\Facades\Log::error('Kurum galeri gorseli (admin) kaydedilemedi.', ['facility_id' => $facility->id]);
                \Sentry\captureException(new \RuntimeException('Gorsel diske yazildiktan sonra dogrulanamadi.'));
                continue;
            }
            FacilityImage::create([
                'facility_id' => $facility->id,
                'path' => $path,
                'sort_order' => $start + $i,
            ]);
            app(\App\Services\CrossDomainImageSync::class)->syncStore($path);
        }
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:180',
            'city_id' => 'required|exists:cities,id',
            'facility_category_id' => 'required|exists:facility_categories,id',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:500',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:5000',
            'capacity' => 'nullable|integer|min:0',
            'price_min' => 'nullable|numeric|min:0',
            // gte:price_min sadece price_min de doluysa uygulanir; aksi halde
            // admin sadece price_max girdiginde (price_min bos) dogrulama
            // tum formu reddedip diger tum alanlardaki degisiklikleri de
            // kaydetmeden geri donduruyordu.
            'price_max' => ['nullable', 'numeric', 'min:0', Rule::when($request->filled('price_min'), ['gte:price_min'])],
            'cover_image' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'services_raw' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'nullable|string|max:120',
            'ministry_verification' => 'nullable|in:verified,kamu_vakif,review,unverified',
        ]);
    }

    private function parseServices(?string $raw, array $selected = []): array
    {
        return collect(array_merge(explode(',', $raw ?? ''), $selected))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Facility::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}