<?php

namespace App\Console\Commands;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

// 12 Agustos 2026: kullanicinin talebi - "her sabah goruntu kontrolu yapan
// script" (gallery:check-health) yaninda, artik 3 markanin da (bakimevleri,
// bakimevibul, bakimeviara) ustunde GERCEK HTTP istekleriyle - tarayici
// olmadan ama tam olarak bir ziyaretcinin yapacagi form gonderimleriyle -
// en kritik kullanici gorevlerini (aile kaydi/girisi, kurum sahiplenme,
// kurum kaydi, teklif talebi, ziyaret talebi, soru, iletisim, kurum girisi)
// uctan uca dener. Bu sunucuda gercek bir tarayici (Playwright/Selenium)
// calistirilamiyor (root yok, sistem kutuphaneleri eksik - daha once
// denenip basarisiz oldu), bu yuzden dogrulama TARAYICI yerine dogrudan
// HTTP + veritabani kontrolu ile yapiliyor - CSRF token'i gercek sayfadan
// okuyup, gercek cerezle oturum tasiyip, POST sonrasi veritabaninda
// BEKLENEN kaydin gercekten olusup olusmadigini kontrol ediyor. Boylece
// "sayfa 200 donuyor ama form aslinda hicbir sey kaydetmiyor" turu SESSIZ
// hatalar da (bu oturumda birkac kez tam olarak bu sekilde gercek hatalar
// yasandi) yakalanir.
//
// TUM test verisi qatest-*/@example.com kurallarina uyar (bkz. OpsController
// qaSetup/qaTeardown) - gercek kullaniciya/veriye asla dokunulmaz. Bulunan
// HER hata TEK bir record_platform_error() cagrisinda birlestirilir (admin
// paneli + mail) - her hata icin ayri mail atmak (once CheckGalleryHealth'te
// yasandigi gibi) gereksiz spam'e yol acar; detay kaybi olmasin diye tum
// bulgular TEK mesajin icinde satir satir listelenir.
//
// 13 Agustos 2026: kullanicinin talebi - test verisi temizligi artik TOPLU
// degil, AKIS BAZLI: her checkX() akisi BASARILIYSA kendi urettigi veriyi
// hemen siler; BASARISIZSA (bir sorun bulunduysa) o veri ELLE incelenip
// duzeltilene kadar veritabaninda birakilir - boylece bir hata "Hatalar"
// panelinde sadece metin olarak degil, GERCEK kayitla birlikte incelenebilir.
// Bu yuzden ayni akisin e-postalari artik GUNE OZEL (ör. ...claim.20260813@
// example.com) - sabit olsaydi, dunku BASARISIZ (bilerek silinmemis) bir
// kayit, bugunku calismanin "once temizle" adimiyla sessizce silinirdi.
class CheckUserFlows extends Command
{
    protected $signature = 'platform:check-user-flows';

    protected $description = '3 markada (bakimevleri, bakimevibul, bakimeviara) aile/kurum kaydi, sahiplenme, teklif talebi gibi kritik kullanici gorevlerini gercek HTTP istekleriyle uctan uca dener, hata bulursa admin paneline ve admine bildirir';

    /** @var array<int, string> */
    private array $failures = [];

    private ?int $qaCityId = null;

    private ?int $qaCategoryId = null;

    public function handle(): int
    {
        // 15 Agustos 2026: kullanicinin "hata mesajini incele" talebi uzerine
        // bulundu - bu komut 3 marka icin AYNI kodu paylasan 3 AYRI doc root'a
        // deploy edilmis, her biri kendi cron'unda schedule:run'i tetikliyor.
        // Saat 08:45'te UCU DE ayni anda bu komutu calistirdi (platform_errors
        // #29/#30/#31, birbirine cok yakin zaman damgalariyla dogrulandi) -
        // her biri TUM 3 markayi HTTP ile deniyor, bu yuzden gune-ozel test
        // e-postalari uzerinde yaris durumu (Duplicate entry) olustu. Cache
        // tabanli withoutOverlapping() 3 ayri doc root'un AYRI dosya cache'i
        // oldugu icin (CACHE_STORE=file) bu yarisi engelleyemez - ucu de ayni
        // paylasimli MySQL'e bagli oldugu icin MySQL'in kendi named lock'u
        // (GET_LOCK) kullanildi, gercekten paylasimli tek kilit.
        // GET_LOCK() sadece MySQL'de var - yerel gelistirme/test ortami (sqlite)
        // bu korumaya ihtiyac duymaz (tek surec, paylasimli DB yok).
        if (DB::connection()->getDriverName() !== 'mysql') {
            return $this->runChecks();
        }

        $lockAcquired = (bool) (DB::selectOne("SELECT GET_LOCK('platform_check_user_flows', 0) AS locked")?->locked ?? false);
        if (! $lockAcquired) {
            $this->info('Baska bir surec (baska bir marka doc root\'u) zaten calistiriyor, bu calisma atlaniyor.');

            return self::SUCCESS;
        }

        try {
            return $this->runChecks();
        } finally {
            DB::selectOne("SELECT RELEASE_LOCK('platform_check_user_flows')");
        }
    }

    private function runChecks(): int
    {
        $this->qaCityId = DB::table('cities')->where('slug', 'bursa')->value('id');
        $this->qaCategoryId = DB::table('facility_categories')->where('slug', 'huzurevi')->value('id');

        if (! $this->qaCityId || ! $this->qaCategoryId) {
            record_platform_error('daily-user-flows-check', 'Kullanıcı akışı testi başlatılamadı', 'Test için gereken şehir (bursa) veya kategori (huzurevi) bulunamadı - veritabanı tutarsız olabilir.');

            return self::SUCCESS;
        }

        foreach (config('brands.brands') as $brandSlug => $brand) {
            $domain = $brand['domains'][0] ?? null;
            if (! $domain || ! str_ends_with($domain, '.com')) {
                continue;
            }
            $baseUrl = 'https://'.$domain;
            $this->info("=== {$brandSlug} ({$baseUrl}) ===");

            $claimedFacilitySlug = $this->ensureClaimedFacility($brandSlug);
            $unclaimedFacilitySlug = $this->ensureUnclaimedFacility($brandSlug);
            $facilityUserEmail = $this->ensureFacilityUser($brandSlug, $claimedFacilitySlug);

            $failuresBeforeBrand = count($this->failures);

            $this->safeRun($brandSlug, 'Aile Kaydı ve Girişi', fn () => $this->checkFamilyRegisterAndLogin($brandSlug, $baseUrl));
            $this->safeRun($brandSlug, 'Kurum Sahiplenme Başvurusu', fn () => $this->checkFacilityClaim($brandSlug, $baseUrl, $unclaimedFacilitySlug));
            $this->safeRun($brandSlug, 'Kurum Kaydı (Sıfırdan Başvuru)', fn () => $this->checkFacilityRegistration($brandSlug, $baseUrl));
            $this->safeRun($brandSlug, 'Ücret / Teklif Talebi', fn () => $this->checkOfferRequest($brandSlug, $baseUrl, $claimedFacilitySlug));
            $this->safeRun($brandSlug, 'Ziyaret Talebi', fn () => $this->checkVisitRequest($brandSlug, $baseUrl, $claimedFacilitySlug));
            $this->safeRun($brandSlug, 'Kurum Sorusu', fn () => $this->checkQuestion($brandSlug, $baseUrl, $claimedFacilitySlug));
            $this->safeRun($brandSlug, 'İletişim Formu', fn () => $this->checkContact($brandSlug, $baseUrl));
            $this->safeRun($brandSlug, 'Kurum Girişi', fn () => $this->checkFacilityLogin($brandSlug, $baseUrl, $facilityUserEmail));

            // 17 Agustos 2026: kullanicinin kesin talebi - "olusturulan test
            // verileri akabinde silinmeli", sabit QATEST Daily kurumlarinin
            // (claimed/unclaimed + yetkili hesabi) admin panelinde SUREKLI
            // gorunmesi kabul edilemezdi. Onceki tasarim bunlari GUNLER
            // BOYUNCA yeniden kullaniyordu (idempotent upsert) - artik ayni
            // gunun TUM kontrolleri hatasiz tamamlandiysa bu 2 kurum + yetkili
            // hesabi hemen siliniyor (diger tum test-verisi turleriyle AYNI
            // "basarili ise sil" kurali). Bir sonraki calisma onlari sifirdan
            // yeniden olusturur - bu yuzden kalici degil, GECICI olmalari
            // sagliklarini etkilemez.
            if (count($this->failures) === $failuresBeforeBrand) {
                $this->cleanupDailyFixtures($claimedFacilitySlug, $unclaimedFacilitySlug);
            }
        }

        // 13 Agustos 2026: kullanicinin talebi - "her testten sonra basarili
        // olanlarin verisi silinsin, basarisiz/hatali olanlar duzeltilmesi
        // icin kalsin". Eskiden burada TEK bir toplu cleanupFixtures()
        // cagrisi TUM qatest-daily-% verisini (basarisiz olanlar dahil)
        // siliyordu - artik her checkX() KENDI urettigi veriyi, SADECE o
        // akis basariliysa, kendi icinde hemen siliyor (bkz. yukarida her
        // metodun sonundaki else/basari dallari). Sabit test-altyapisi
        // (qatest-daily-{brand}-claimed/-unclaimed kurumlari ve kurum
        // yetkilisi - ensureClaimedFacility/ensureUnclaimedFacility/
        // ensureFacilityUser) zaten her gun idempotent olarak yeniden
        // kullanildigi icin hic silinmez.
        if ($this->failures) {
            $this->error(count($this->failures).' hata bulundu.');
            record_platform_error(
                'daily-user-flows-check',
                count($this->failures).' kritik kullanıcı akışında hata bulundu',
                "Günlük otomatik kontrol, aşağıdaki akışlarda beklenmeyen bir sonuç tespit etti. Her satır ayrı bir bulgudur:\n\n".implode("\n\n", $this->failures)
            );
        } else {
            $this->info('Tüm kritik kullanıcı akışları (3 markada) sorunsuz çalışıyor.');
        }

        // Is bulgusu bir KOMUT hatasi degildir (zaten yukarida ayrica
        // raporlandi) - FAILURE donmek Laravel Scheduler'in kendi, alakasiz
        // "scheduled command failed" hatasini AYRICA uretmesine yol acar
        // (bkz. CheckGalleryHealth'te ayni sebeple duzeltilen ayni hata).
        return self::SUCCESS;
    }

    private function safeRun(string $brandSlug, string $flowName, \Closure $check): void
    {
        try {
            $check();
        } catch (\Throwable $e) {
            $this->recordFailure($brandSlug, $flowName, 'Beklenmeyen istisna: '.$e->getMessage());
        }
    }

    private function recordFailure(string $brandSlug, string $flowName, string $detail): void
    {
        $line = "[{$brandSlug}] {$flowName}: {$detail}";
        $this->failures[] = $line;
        $this->warn($line);
    }

    // ------------------------------------------------------------------
    // HTTP yardimcilari
    // ------------------------------------------------------------------

    /**
     * @return array{0: PendingRequest, 1: CookieJar}
     */
    private function newClient(?CookieJar $jar = null, bool $followRedirects = true): array
    {
        $jar ??= new CookieJar();
        $client = Http::withOptions([
            'cookies' => $jar,
            'timeout' => 25,
            'allow_redirects' => $followRedirects,
            'verify' => true,
        ])->withHeaders(['User-Agent' => 'BakimPlatformDailyCheck/1.0']);

        return [$client, $jar];
    }

    private function csrfToken(Response $response): ?string
    {
        if (preg_match('/name="csrf-token" content="([^"]+)"/', $response->body(), $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @throws \RuntimeException
     */
    private function tempTestImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'qacheck_').'.png';
        $im = imagecreatetruecolor(4, 4);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 200, 200));
        imagepng($im, $path);
        imagedestroy($im);

        return $path;
    }

    // ------------------------------------------------------------------
    // Fixture (test verisi) hazirlama - OpsController::qaSetup ile ayni desen
    // ------------------------------------------------------------------

    // 14 Agustos 2026: kullanicinin bildirdigi canli hata - 3 marka AYNI
    // veritabanini paylastigi icin ve her marka kendi sunucusunda ayni
    // saatte (08:45) bu komutu tetikledigi icin, "var mi diye bak, yoksa
    // ekle" adimlari arasinda BASKA bir markanin sureci ayni ekleme
    // islemini ayni anda yapabiliyor - saniyeler icinde iki surec de
    // "yok" gorup ikisi de INSERT denedi, ikincisi benzersizlik hatasi
    // (UniqueConstraintViolationException) aldi. try/catch ile bu yarisi
    // GUVENLI sekilde cozuyoruz: INSERT basarisiz olursa (kayit aslinda
    // baska bir surec tarafindan az once olusturulmus demektir) sessizce
    // guncellemeye geciyoruz.
    private function ensureClaimedFacility(string $brandSlug): string
    {
        $slug = "qatest-daily-{$brandSlug}-claimed";
        // 15 Agustos 2026: kullanicinin "hata mesajini incele" talebi uzerine
        // bulundu - Facility::class SoftDeletes kullanir ama burasi ham
        // DB::table() sorgusu oldugu icin deleted_at dolu (soft-silinmis) bir
        // kaydi da GORUYOR ve "var" saniyordu, ama deleted_at'i hic temizlemiyordu.
        // Sonuc: kurum is_published/is_claimed alanlari dogru olsa bile
        // Eloquent'in Facility::published() sorgusu (gercek /kurumlar/{slug}
        // sayfasinin kullandigi) soft-silinmis kaydi HICBIR ZAMAN gostermiyordu -
        // test sayfasi kalici olarak 404 veriyordu, bu da zincirleme olarak
        // teklif/ziyaret/soru testlerinin "guvenlik anahtari bulunamadi"
        // hatasi vermesine yol aciyordu (aslinda 404 sayfasinda token yoktu).
        $existing = DB::table('facilities')->where('slug', $slug)->first();
        if ($existing) {
            DB::table('facilities')->where('id', $existing->id)->update([
                'is_claimed' => true, 'is_published' => true, 'free_quote_credits' => 100, 'deleted_at' => null, 'updated_at' => now(),
            ]);

            return $slug;
        }

        try {
            DB::table('facilities')->insert([
                'name' => 'QATEST Daily '.ucfirst($brandSlug).' Claimed',
                'slug' => $slug,
                'city_id' => $this->qaCityId,
                'facility_category_id' => $this->qaCategoryId,
                'ownership_type' => 'ozel',
                'address' => 'Test adresi',
                'phone' => '05320000001',
                'phone_type' => 'mobile',
                'is_published' => true,
                'is_claimed' => true,
                'claimed_at' => now(),
                'invitation_status' => 'approved',
                'free_quote_credits' => 100,
                'balance' => 0,
                'source' => 'qa_test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            DB::table('facilities')->where('slug', $slug)->update([
                'is_claimed' => true, 'is_published' => true, 'free_quote_credits' => 100, 'deleted_at' => null, 'updated_at' => now(),
            ]);
        }

        return $slug;
    }

    private function ensureUnclaimedFacility(string $brandSlug): string
    {
        $slug = "qatest-daily-{$brandSlug}-unclaimed";
        $existing = DB::table('facilities')->where('slug', $slug)->first();
        if ($existing) {
            DB::table('facilities')->where('id', $existing->id)->update([
                'is_claimed' => false, 'claimed_at' => null, 'invitation_status' => 'pending', 'deleted_at' => null, 'updated_at' => now(),
            ]);
            DB::table('facility_users')->where('facility_id', $existing->id)->delete();

            return $slug;
        }

        try {
            DB::table('facilities')->insert([
                'name' => 'QATEST Daily '.ucfirst($brandSlug).' Unclaimed',
                'slug' => $slug,
                'city_id' => $this->qaCityId,
                'facility_category_id' => $this->qaCategoryId,
                'ownership_type' => 'ozel',
                'address' => 'Test adresi',
                'phone' => '05320000002',
                'phone_type' => 'mobile',
                'is_published' => true,
                'is_claimed' => false,
                'invitation_status' => 'pending',
                'free_quote_credits' => 5,
                'balance' => 0,
                'source' => 'qa_test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            DB::table('facilities')->where('slug', $slug)->update([
                'is_claimed' => false, 'claimed_at' => null, 'invitation_status' => 'pending', 'deleted_at' => null, 'updated_at' => now(),
            ]);
        }

        return $slug;
    }

    private function ensureFacilityUser(string $brandSlug, string $claimedFacilitySlug): string
    {
        $email = "qatest.daily.{$brandSlug}.facility@example.com";
        $facilityId = DB::table('facilities')->where('slug', $claimedFacilitySlug)->value('id');

        $existing = DB::table('facility_users')->where('email', $email)->first();
        if ($existing) {
            DB::table('facility_users')->where('id', $existing->id)->update([
                'password' => Hash::make('QaTest12345!'),
                'status' => 'active',
                'must_change_password' => false,
                'facility_id' => $facilityId,
                'updated_at' => now(),
            ]);

            return $email;
        }

        try {
            DB::table('facility_users')->insert([
                'facility_id' => $facilityId,
                'name' => 'QATEST Daily Yetkili',
                'email' => $email,
                'password' => Hash::make('QaTest12345!'),
                'status' => 'active',
                'email_verified_at' => now(),
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            DB::table('facility_users')->where('email', $email)->update([
                'password' => Hash::make('QaTest12345!'),
                'status' => 'active',
                'must_change_password' => false,
                'facility_id' => $facilityId,
                'updated_at' => now(),
            ]);
        }

        return $email;
    }

    /**
     * 17 Agustos 2026: kullanicinin kesin talebi - bkz. runChecks() ayni
     * tarihli yorum. Sadece o gunun sabit fixture'larini (claimed/unclaimed
     * kurum + yetkili hesabi) siler - gercek kullanici verisine dokunmaz.
     */
    private function cleanupDailyFixtures(string $claimedSlug, string $unclaimedSlug): void
    {
        foreach ([$claimedSlug, $unclaimedSlug] as $slug) {
            $facility = DB::table('facilities')->where('slug', $slug)->first();
            if (! $facility) {
                continue;
            }
            DB::table('facility_users')->where('facility_id', $facility->id)->delete();
            DB::table('facility_images')->where('facility_id', $facility->id)->delete();
            DB::table('facilities')->where('id', $facility->id)->delete();
        }
    }

    // ------------------------------------------------------------------
    // Akis kontrolleri
    // ------------------------------------------------------------------

    private function checkFamilyRegisterAndLogin(string $brandSlug, string $baseUrl): void
    {
        $email = "qatest.daily.{$brandSlug}.".now()->format('Ymd').'@example.com';
        DB::table('family_users')->where('email', $email)->delete();

        [$client] = $this->newClient();
        $page = $client->get("{$baseUrl}/aile/kayit");
        $token = $this->csrfToken($page);
        if (! $page->ok() || ! $token) {
            $this->recordFailure($brandSlug, 'Aile Kaydı', "Kayıt sayfası açılamadı (HTTP {$page->status()}) veya güvenlik anahtarı bulunamadı.");

            return;
        }

        $resp = $client->asForm()->post("{$baseUrl}/aile/kayit", [
            '_token' => $token,
            'name' => 'QATEST Daily Aile',
            'email' => $email,
            'phone' => '05320000000',
            'password' => 'QaTest12345!',
            'password_confirmation' => 'QaTest12345!',
            'consent' => '1',
        ]);

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'Aile Kaydı', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $user = DB::table('family_users')->where('email', $email)->first();
        if (! $user) {
            $this->recordFailure($brandSlug, 'Aile Kaydı', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir aile hesabı oluşmadı - kayıt sessizce başarısız oluyor olabilir.");

            return;
        }

        DB::table('family_users')->where('id', $user->id)->update(['email_verified_at' => now()]);

        [$loginClient, $jar] = $this->newClient();
        $loginPage = $loginClient->get("{$baseUrl}/aile/giris");
        $loginToken = $this->csrfToken($loginPage);
        if (! $loginToken) {
            $this->recordFailure($brandSlug, 'Aile Girişi', 'Giriş sayfası açıldı ama güvenlik anahtarı bulunamadı.');

            return;
        }

        $loginClient->asForm()->post("{$baseUrl}/aile/giris", [
            '_token' => $loginToken,
            'email' => $email,
            'password' => 'QaTest12345!',
        ]);

        [$checkClient] = $this->newClient($jar, followRedirects: false);
        $dash = $checkClient->get("{$baseUrl}/aile/panel");
        $failuresBefore = count($this->failures);
        if ($dash->status() >= 500) {
            $this->recordFailure($brandSlug, 'Aile Girişi', "Panel sayfası sunucu hatası döndü (HTTP {$dash->status()}).");
        } elseif ($dash->status() === 302 && str_contains((string) $dash->header('Location'), 'giris')) {
            $this->recordFailure($brandSlug, 'Aile Girişi', 'Kayıt sonrası doğru bilgilerle giriş yapılamadı, panel yerine giriş sayfasına yönlendirildi.');
        }

        // 13 Agustos 2026: kullanicinin talebi - akis (kayit + giris) ucdan
        // uca basariliysa urettigi test verisi hemen silinir; herhangi bir
        // adimi basarisizsa (yukarida recordFailure cagrildiysa) kayit
        // ELLE incelenip duzeltilene kadar veritabaninda birakilir.
        if (count($this->failures) === $failuresBefore) {
            DB::table('family_users')->where('id', $user->id)->delete();
        }
    }

    private function checkFacilityClaim(string $brandSlug, string $baseUrl, string $unclaimedSlug): void
    {
        // 13 Agustos 2026: kullanicinin talebi - "basarili testin verisi
        // silinsin, basarisiz olan incelenmek uzere kalsin" isteginin
        // gercekten calismasi icin e-posta artik GUNE OZEL (once sabitti,
        // dunku BASARISIZ bir kaydi bugunku "once temizle" adimi sessizce
        // silerdi - kullanicinin istegini fiilen 1 gunle sinirlardi).
        $email = "qatest.daily.{$brandSlug}.claim.".now()->format('Ymd')."@example.com";

        [$client] = $this->newClient();
        $page = $client->get("{$baseUrl}/kurumlar/{$unclaimedSlug}/sahiplen");
        $token = $this->csrfToken($page);
        if (! $page->ok() || ! $token) {
            $this->recordFailure($brandSlug, 'Kurum Sahiplenme Başvurusu', "Sahiplenme sayfası açılamadı (HTTP {$page->status()}) veya güvenlik anahtarı bulunamadı.");

            return;
        }

        $imagePath = $this->tempTestImage();

        try {
            $resp = $client->attach('document', file_get_contents($imagePath), 'test-belge.png')
                ->asMultipart()
                ->post("{$baseUrl}/kurumlar/{$unclaimedSlug}/sahiplen", [
                    ['name' => '_token', 'contents' => $token],
                    ['name' => 'applicant_name', 'contents' => 'QATEST Daily Başvuran'],
                    ['name' => 'applicant_email', 'contents' => $email],
                    ['name' => 'applicant_phone', 'contents' => '05320000003'],
                    ['name' => 'note', 'contents' => 'Otomatik gunluk kontrol'],
                ]);
        } finally {
            @unlink($imagePath);
        }

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'Kurum Sahiplenme Başvurusu', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $claim = DB::table('facility_claims')->where('applicant_email', $email)->first();
        if (! $claim) {
            $this->recordFailure($brandSlug, 'Kurum Sahiplenme Başvurusu', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir başvuru oluşmadı.");
        } else {
            // 13 Agustos 2026: bkz. checkFamilyRegisterAndLogin ayni yorum -
            // basarili akisin verisi hemen silinir, basarisiz olan (yukarida)
            // incelenmek uzere kalir.
            DB::table('facility_claims')->where('id', $claim->id)->delete();
        }
    }

    private function checkFacilityRegistration(string $brandSlug, string $baseUrl): void
    {
        // 13 Agustos 2026: bkz. checkFacilityClaim ayni yorum - gune ozel e-posta.
        $email = "qatest.daily.{$brandSlug}.register.".now()->format('Ymd')."@example.com";

        [$client] = $this->newClient();
        $page = $client->get("{$baseUrl}/kurum-kaydi");
        $token = $this->csrfToken($page);
        if (! $page->ok() || ! $token) {
            $this->recordFailure($brandSlug, 'Kurum Kaydı (Sıfırdan Başvuru)', "Kayıt sayfası açılamadı (HTTP {$page->status()}) veya güvenlik anahtarı bulunamadı.");

            return;
        }

        $resp = $client->asForm()->post("{$baseUrl}/kurum-kaydi", [
            '_token' => $token,
            'facility_category_id' => $this->qaCategoryId,
            'name' => 'QATEST Daily Yeni Kurum',
            'city_id' => $this->qaCityId,
            'district' => 'Nilüfer',
            'address' => 'Test adresi',
            'phone' => '05320000004',
            'description' => 'Otomatik gunluk kontrol icin olusturulmus test kaydi.',
            'capacity' => 10,
            'applicant_name' => 'QATEST Daily Yetkili',
            'applicant_email' => $email,
            'applicant_phone' => '05320000005',
        ]);

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'Kurum Kaydı (Sıfırdan Başvuru)', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $registration = DB::table('facility_registrations')->where('applicant_email', $email)->first();
        if (! $registration) {
            $this->recordFailure($brandSlug, 'Kurum Kaydı (Sıfırdan Başvuru)', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir kayıt başvurusu oluşmadı.");
        } else {
            DB::table('facility_registrations')->where('id', $registration->id)->delete();
        }
    }

    private function checkOfferRequest(string $brandSlug, string $baseUrl, string $claimedSlug): void
    {
        // 13 Agustos 2026: bkz. checkFacilityClaim ayni yorum - gune ozel e-posta.
        $email = "qatest.daily.{$brandSlug}.offer.".now()->format('Ymd')."@example.com";
        DB::table('family_users')->insert([
            'name' => 'QATEST Daily Teklif Ailesi',
            'email' => $email,
            'phone' => '05320000006',
            'password' => Hash::make('QaTest12345!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $facilityId = DB::table('facilities')->where('slug', $claimedSlug)->value('id');

        [$client, $jar] = $this->newClient();
        $loginPage = $client->get("{$baseUrl}/aile/giris");
        $loginToken = $this->csrfToken($loginPage);
        if (! $loginToken) {
            $this->recordFailure($brandSlug, 'Ücret / Teklif Talebi', 'Test için gerekli aile girişi yapılamadı (güvenlik anahtarı bulunamadı).');

            return;
        }
        $client->asForm()->post("{$baseUrl}/aile/giris", ['_token' => $loginToken, 'email' => $email, 'password' => 'QaTest12345!']);

        $formPage = $client->get("{$baseUrl}/kurumlar/{$claimedSlug}");
        $formToken = $this->csrfToken($formPage);
        if (! $formToken) {
            $this->recordFailure($brandSlug, 'Ücret / Teklif Talebi', 'Kurum detay sayfasından güvenlik anahtarı alınamadı.');

            return;
        }

        $resp = $client->asForm()->post("{$baseUrl}/teklif-talebi", [
            '_token' => $formToken,
            'facility_id' => $facilityId,
            'full_name' => 'QATEST Daily Teklif Ailesi',
            'phone' => '05320000006',
            'email' => $email,
            'message' => 'Otomatik gunluk kontrol',
        ]);

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'Ücret / Teklif Talebi', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $offerRequestId = DB::table('offer_requests')->where('facility_id', $facilityId)->where('email', $email)->value('id');
        if (! $offerRequestId) {
            $this->recordFailure($brandSlug, 'Ücret / Teklif Talebi', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir talep oluşmadı.");
        } else {
            DB::table('messages')->where('offer_request_id', $offerRequestId)->delete();
            DB::table('quotes')->where('offer_request_id', $offerRequestId)->delete();
            DB::table('offer_requests')->where('id', $offerRequestId)->delete();
            DB::table('family_users')->where('email', $email)->delete();
        }
    }

    private function checkVisitRequest(string $brandSlug, string $baseUrl, string $claimedSlug): void
    {
        $phone = '0532000000'.random_int(7, 9);

        [$client] = $this->newClient();
        $page = $client->get("{$baseUrl}/kurumlar/{$claimedSlug}");
        $token = $this->csrfToken($page);
        if (! $token) {
            $this->recordFailure($brandSlug, 'Ziyaret Talebi', 'Kurum detay sayfasından güvenlik anahtarı alınamadı.');

            return;
        }

        $resp = $client->asForm()->post("{$baseUrl}/kurumlar/{$claimedSlug}/ziyaret-talebi", [
            '_token' => $token,
            'full_name' => 'QATEST Daily Ziyaretçi',
            'phone' => $phone,
            'message' => 'Otomatik gunluk kontrol',
        ]);

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'Ziyaret Talebi', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $facilityId = DB::table('facilities')->where('slug', $claimedSlug)->value('id');
        $exists = DB::table('visit_requests')->where('facility_id', $facilityId)->where('phone', $phone)->exists();
        if (! $exists) {
            $this->recordFailure($brandSlug, 'Ziyaret Talebi', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir kayıt oluşmadı.");
        } else {
            DB::table('visit_requests')->where('facility_id', $facilityId)->where('phone', $phone)->delete();
        }
    }

    private function checkQuestion(string $brandSlug, string $baseUrl, string $claimedSlug): void
    {
        $marker = 'QATEST Daily Soru '.now()->timestamp;

        [$client] = $this->newClient();
        $page = $client->get("{$baseUrl}/kurumlar/{$claimedSlug}");
        $token = $this->csrfToken($page);
        if (! $token) {
            $this->recordFailure($brandSlug, 'Kurum Sorusu', 'Kurum detay sayfasından güvenlik anahtarı alınamadı.');

            return;
        }

        $resp = $client->asForm()->post("{$baseUrl}/kurumlar/{$claimedSlug}/soru-sor", [
            '_token' => $token,
            'asker_name' => 'QATEST Daily Soran',
            'question' => $marker,
        ]);

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'Kurum Sorusu', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $exists = DB::table('facility_questions')->where('question', $marker)->exists();
        if (! $exists) {
            $this->recordFailure($brandSlug, 'Kurum Sorusu', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir soru oluşmadı.");
        } else {
            DB::table('facility_questions')->where('question', $marker)->delete();
        }
    }

    private function checkContact(string $brandSlug, string $baseUrl): void
    {
        // 13 Agustos 2026: bkz. checkFacilityClaim ayni yorum - gune ozel e-posta.
        $email = "qatest.daily.{$brandSlug}.contact.".now()->format('Ymd')."@example.com";

        [$client] = $this->newClient();
        $page = $client->get("{$baseUrl}/iletisim");
        $token = $this->csrfToken($page);
        if (! $page->ok() || ! $token) {
            $this->recordFailure($brandSlug, 'İletişim Formu', "İletişim sayfası açılamadı (HTTP {$page->status()}) veya güvenlik anahtarı bulunamadı.");

            return;
        }

        $resp = $client->asForm()->post("{$baseUrl}/iletisim", [
            '_token' => $token,
            'name' => 'QATEST Daily İletişim',
            'email' => $email,
            'subject' => 'Otomatik gunluk kontrol',
            'message' => 'Bu mesaj gunluk otomatik kontrol tarafindan gonderilmistir.',
        ]);

        if ($resp->status() >= 500) {
            $this->recordFailure($brandSlug, 'İletişim Formu', "Form gönderilince sunucu hatası döndü (HTTP {$resp->status()}).");

            return;
        }

        $exists = DB::table('contact_messages')->where('email', $email)->exists();
        if (! $exists) {
            $this->recordFailure($brandSlug, 'İletişim Formu', "Form HTTP {$resp->status()} ile yanıtlandı ama veritabanında yeni bir mesaj oluşmadı.");
        } else {
            DB::table('contact_messages')->where('email', $email)->delete();
        }
    }

    private function checkFacilityLogin(string $brandSlug, string $baseUrl, string $facilityUserEmail): void
    {
        [$client, $jar] = $this->newClient();
        $page = $client->get("{$baseUrl}/kurum-panel/giris");
        $token = $this->csrfToken($page);
        if (! $page->ok() || ! $token) {
            $this->recordFailure($brandSlug, 'Kurum Girişi', "Giriş sayfası açılamadı (HTTP {$page->status()}) veya güvenlik anahtarı bulunamadı.");

            return;
        }

        $client->asForm()->post("{$baseUrl}/kurum-panel/giris", [
            '_token' => $token,
            'email' => $facilityUserEmail,
            'password' => 'QaTest12345!',
        ]);

        [$checkClient] = $this->newClient($jar, followRedirects: false);
        $panel = $checkClient->get("{$baseUrl}/kurum-panel/panel");
        if ($panel->status() >= 500) {
            $this->recordFailure($brandSlug, 'Kurum Girişi', "Panel sayfası sunucu hatası döndü (HTTP {$panel->status()}).");
        } elseif ($panel->status() === 302 && str_contains((string) $panel->header('Location'), 'giris')) {
            $this->recordFailure($brandSlug, 'Kurum Girişi', 'Bilinen doğru bilgilerle giriş yapılamadı, panel yerine giriş sayfasına yönlendirildi.');
        }
    }
}
