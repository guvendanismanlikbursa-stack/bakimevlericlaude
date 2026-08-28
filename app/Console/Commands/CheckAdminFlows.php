<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\AccountDeletionController;
use App\Http\Controllers\Admin\BalanceController;
use App\Http\Controllers\Admin\BrokerController;
use App\Http\Controllers\Admin\ChatSettingsController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContentPageController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\FacilityCategoryController;
use App\Http\Controllers\Admin\FacilityClaimController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\FacilityInvitationController;
use App\Http\Controllers\Admin\FacilityQuestionController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\OccupancyController;
use App\Http\Controllers\Admin\PlatformErrorController;
use App\Http\Controllers\Admin\SubscriptionPackageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletTopupController;
use App\Http\Controllers\Admin\WhatsappClickController;
use App\Http\Controllers\Public\ImpersonationController;
use App\Http\Middleware\FacilityUserAuth;
use App\Http\Middleware\FamilyAuth;
use App\Mail\FacilityPasswordManuallyResetMail;
use App\Models\AccountDeletionRequest;
use App\Models\BalanceLog;
use App\Models\BrokerReferral;
use App\Models\ChatWorkingHour;
use App\Models\City;
use App\Models\ContentPage;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityClaim;
use App\Models\FacilityQuestion;
use App\Models\FamilyUser;
use App\Models\FacilityUser;
use App\Models\Faq;
use App\Models\PlatformError;
use App\Models\Setting;
use App\Models\SubscriptionPackage;
use App\Models\WalletTopup;
use App\Models\WhatsappClick;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// 26 Agustos 2026: kullanicinin talebi - "her bolumde ki her ozellik mutlaka
// farkli senaryolarla test edilmeli". App\Console\Commands\CheckUserFlows.php
// (gunluk 08:45'te GERCEK HTTP istekleriyle genel siteyi dener) ile AYNI
// desen/guvenlik kurallari, ama admin paneli icin: bu hafta canliya sizan 2
// gercek hata (yerinde sahiplendirme sifreyi hic gostermiyordu, "on kayit"a
// dondurme bonus hakkini sifirlamiyordu) TAM OLARAK admin panelindeydi ve
// CheckUserFlows bunlarin hicbirini yakalayamazdi - o kontrol sadece genel
// site akislarini (aile kaydi, kurum girisi vb.) dener, admin koduna hic
// dokunmaz. Bu komut o kor noktayi kapatir.
//
// HTTP yerine (admin paneli disariya acik bir "genel kullanici" akisi
// olmadigi, giris sonrasi oturum durumu gerektirdigi icin) OpsController'daki
// qaInstantClaimTest/qaVerifyBalanceBrandFixes'te KANITLANMIS yontem
// kullanilir: sahte bir admin oturumu acilip GERCEK admin controller kodu
// dogrudan cagrilir (Request::create() + controller->method() cagrisi,
// yonlendirme/middleware YOK - cunku bu bir cron surecidir, tarayici degil).
//
// Her gece GERCEK mail atmamasi icin Mail::fake() kullanilir - mailin
// GONDERILMESI degil, ICERIGININ dogru olup olmadigi (ör. sifrenin
// gercekten mailde yazip yazmadigi) kontrol edilir.
class CheckAdminFlows extends Command
{
    protected $signature = 'platform:check-admin-flows';

    protected $description = 'Admin panelindeki kritik islemleri (sahiplendirme, bakiye/hak, bakiye yukleme onayi gibi) sahte test verisiyle GERCEK kodu calistirarak dener, hata bulursa admin paneline ve admine bildirir';

    /** @var array<int, string> */
    private array $failures = [];

    private ?int $adminId = null;

    private ?int $qaCityId = null;

    private ?int $qaCategoryId = null;

    public function handle(): int
    {
        // Mail::assertSent() PHPUnit disinda (bir Command icinde) firlatirsa
        // komutu durdurur - bu yuzden asagida FIRLATMAYAN Mail::sent(...)
        // koleksiyon donen metodu kullanilir, assertSent() DEGIL.
        Mail::fake();

        // bkz. CheckUserFlows::handle() ayni tarihli/mantikli yorum - 3 marka
        // doc root'u AYNI kodu, AYNI veritabanini paylasiyor, ucu de kendi
        // cron'unda schedule:run tetikliyor - GET_LOCK olmadan bu komut da
        // ayni yaris durumuna dusebilir.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return $this->runChecks();
        }

        $lockAcquired = (bool) (DB::selectOne("SELECT GET_LOCK('platform_check_admin_flows', 0) AS locked")?->locked ?? false);
        if (! $lockAcquired) {
            $this->info('Baska bir surec (baska bir marka doc root\'u) zaten calistiriyor, bu calisma atlaniyor.');

            return self::SUCCESS;
        }

        try {
            return $this->runChecks();
        } finally {
            DB::selectOne("SELECT RELEASE_LOCK('platform_check_admin_flows')");
        }
    }

    private function runChecks(): int
    {
        $this->adminId = DB::table('admins')->value('id');
        $this->qaCityId = DB::table('cities')->where('slug', 'bursa')->value('id');
        $this->qaCategoryId = DB::table('facility_categories')->where('slug', 'huzurevi')->value('id');

        if (! $this->adminId || ! $this->qaCityId || ! $this->qaCategoryId) {
            record_platform_error('daily-admin-flows-check', 'Admin akışı testi başlatılamadı', 'Test için gereken admin hesabı veya şehir (bursa)/kategori (huzurevi) bulunamadı.');

            return self::SUCCESS;
        }

        session(['admin_id' => $this->adminId]);

        $this->safeRun('Yerinde Sahiplendirme (normal senaryo)', fn () => $this->checkInstantClaimHappyPath());
        $this->safeRun('Yerinde Sahiplendirme (güvenlik kontrolleri)', fn () => $this->checkInstantClaimGuards());
        $this->safeRun('Sahiplenmeyi geri alma (bonus sıfırlama)', fn () => $this->checkRevertZeroesBonus());
        $this->safeRun('Sahiplenme döngüsü (bonus birikmemeli)', fn () => $this->checkClaimRevertCycleNotCumulative());
        $this->safeRun('Doğru site yönlendirmesi', fn () => $this->checkBrandLoginUrlResolution());
        $this->safeRun('Bakiye/Hak manuel düzenleme', fn () => $this->checkBalanceAdjust());
        $this->safeRun('Bakiye/Hak geçmiş kaydını düzenleme', fn () => $this->checkBalanceLogUpdate());
        $this->safeRun('Bakiye/Hak geçmiş kaydını silme', fn () => $this->checkBalanceLogDestroy());
        $this->safeRun('Bakiye yükleme onayı', fn () => $this->checkWalletTopupApprove());
        $this->safeRun('Bakiye yükleme reddi', fn () => $this->checkWalletTopupReject());
        $this->safeRun('Bakiye yükleme çift onay engeli', fn () => $this->checkWalletTopupDoubleApproveGuard());
        $this->safeRun('Sahiplenme başvurusu onayı', fn () => $this->checkFacilityClaimApprove());

        // 26 Agustos 2026: Asama 2 - hesap durumu ve erisim islemleri
        // (bkz. plan dosyasi). Sadece veritabani alanini degil, gercek
        // ERISIM ENGELLEMESINI (FamilyAuth/FacilityUserAuth middleware'i)
        // dogrudan calistirarak test eder.
        $this->safeRun('Hesap askıya alma/aktifleştirme (Aile)', fn () => $this->checkFamilyStatusToggleEnforced());
        $this->safeRun('Hesap askıya alma/aktifleştirme (Kurum Yetkilisi)', fn () => $this->checkFacilityUserStatusToggleEnforced());
        $this->safeRun('Kullanıcı olarak görüntüleme (impersonation)', fn () => $this->checkImpersonationRoundTrip());
        $this->safeRun('Kurum yetkilisi şifre sıfırlama', fn () => $this->checkFacilityPasswordResetInvalidatesOld());
        $this->safeRun('Kurum yetkilisi hesabını silme', fn () => $this->checkDestroyFacilityUserDoesNotOrphan());
        $this->safeRun('Sahiplenmeyi geri alma (erişim engeli)', fn () => $this->checkRevertSuspensionBlocksAccess());
        $this->safeRun('Hesap silme talebi onayı', fn () => $this->checkAccountDeletionApprove());
        $this->safeRun('Hesap silme talebi reddi', fn () => $this->checkAccountDeletionRejectLeavesAccountActive());

        // 26 Agustos 2026: Asama 3 - su ana kadar hicbir yerde (ne PHPUnit'te
        // ne adminPanelSmokeTest'te) test edilmemis bolumler (bkz. plan
        // dosyasi): Doluluk Durumu, Aracilik CRM, Belge Goruntuleme, Sehir/
        // Kategori olusturma-silme.
        $this->safeRun('Doluluk Durumu güncelleme', fn () => $this->checkOccupancyUpdate());
        $this->safeRun('Aracılık - Anlaşmalı Kurum işaretleme', fn () => $this->checkBrokerFacilityToggle());
        $this->safeRun('Aracılık - Yönlendirme yaşam döngüsü', fn () => $this->checkBrokerReferralLifecycle());
        $this->safeRun('Belge görüntüleme (dosya sunumu)', fn () => $this->checkDocumentShowServesFile());
        $this->safeRun('Belge görüntüleme (güvenlik/hata kontrolleri)', fn () => $this->checkDocumentShowRejectsInvalid());
        $this->safeRun('Şehir oluşturma ve silme koruması', fn () => $this->checkCityCreateAndTrashedGuard());
        $this->safeRun('Kategori oluşturma ve fiyat segmenti güncelleme', fn () => $this->checkCategoryCreateAndPriceTiers());
        $this->safeRun('Kategori silme koruması (çöp kutusu)', fn () => $this->checkCategoryTrashedGuard());

        // 26 Agustos 2026: Asama 4 (son asama) - geri kalan, daha dusuk
        // riskli CRUD bolumleri (bkz. plan dosyasi). BILEREK DISINDA
        // BIRAKILAN: DataQualityController'daki fixPhoneType/fixDistrict/
        // fixNameCleanup/fixMiscategory - bunlar TEK bir kuruma degil
        // TUM facilities tablosuna GERCEK, KALICI degisiklik uygulayan
        // toplu islemler (bkz. DataQualityService); gunluk otomatik bir
        // kontrolun bunlari sessizce (admin onayi olmadan) her gece tum
        // veritabaninda tetiklemesi is riski tasir - bu islemler zaten
        // PlatformFeatureTest.php icinde IZOLE bir test veritabanina karsi
        // test ediliyor, o yeterli kapsam.
        $this->safeRun('Sayfa oluşturma/güncelleme/silme + HTML temizliği', fn () => $this->checkContentPageLifecycleAndSanitization());
        $this->safeRun('SSS oluşturma/güncelleme/silme + önbellek temizliği', fn () => $this->checkFaqLifecycleAndCacheInvalidation());
        $this->safeRun('Paket oluşturma/güncelleme/silme + önbellek temizliği', fn () => $this->checkSubscriptionPackageLifecycle());
        $this->safeRun('Canlı sohbet çalışma saatleri', fn () => $this->checkChatSettingsUpdate());
        $this->safeRun('Aile sorusu silme (moderasyon)', fn () => $this->checkFacilityQuestionDestroy());
        $this->safeRun('Hata kaydı çözüldü işaretleme/silme', fn () => $this->checkPlatformErrorResolveAndDestroy());
        $this->safeRun('WhatsApp tıklama kaydı silme', fn () => $this->checkWhatsappClickDestroy());
        $this->safeRun('Kurum davet durumu güncelleme', fn () => $this->checkFacilityInvitationStatusUpdate());

        if ($this->failures) {
            $this->error(count($this->failures).' hata bulundu.');
            record_platform_error(
                'daily-admin-flows-check',
                count($this->failures).' kritik admin panel işleminde hata bulundu',
                "Günlük otomatik kontrol, admin panelindeki aşağıdaki işlemlerde beklenmeyen bir sonuç tespit etti. Her satır ayrı bir bulgudur:\n\n".implode("\n\n", $this->failures)
            );
        } else {
            $this->info('Admin panelindeki tüm kritik işlemler sorunsuz çalışıyor.');
        }

        // bkz. CheckUserFlows::runChecks() ayni yorum - bulgu zaten yukarida
        // ayrica raporlandi, FAILURE donmek Scheduler'in alakasiz "komut
        // basarisiz oldu" hatasini ayrica uretmesine yol acar.
        return self::SUCCESS;
    }

    private function safeRun(string $flowName, \Closure $check): void
    {
        try {
            $check();
        } catch (\Throwable $e) {
            $this->recordFailure($flowName, 'Beklenmeyen istisna: '.$e->getMessage());
        }
    }

    private function recordFailure(string $flowName, string $detail): void
    {
        $line = "[{$flowName}] {$detail}";
        $this->failures[] = $line;
        $this->warn($line);
    }

    // ------------------------------------------------------------------
    // Fixture (test verisi) yardimcilari
    // ------------------------------------------------------------------

    private function freshUnclaimedFacility(string $suffix): Facility
    {
        $slug = "qatest-admin-check-{$suffix}";
        $facility = Facility::withTrashed()->where('slug', $slug)->first();

        if ($facility) {
            $facility->forceFill([
                'is_claimed' => false,
                'claimed_at' => null,
                'invitation_status' => 'pending',
                'invitation_status_at' => null,
                // 0: gercek "on kayitli" bir kurumun tabani daima 0'dir (bkz.
                // DataImportRowApprovalService/DataExtractorImportService) -
                // senaryolardaki beklenen deger hesaplarinin (config'teki
                // bonus miktariyla BIREBIR eslesmesi icin) baz alinan deger.
                'balance' => 0,
                'free_quote_credits' => 0,
                'deleted_at' => null,
            ])->save();
            FacilityUser::where('facility_id', $facility->id)->delete();
            BalanceLog::where('facility_id', $facility->id)->delete();
            FacilityClaim::where('facility_id', $facility->id)->delete();

            return $facility;
        }

        return Facility::create([
            'name' => 'QATEST Admin Check '.$suffix,
            'slug' => $slug,
            'city_id' => $this->qaCityId,
            'facility_category_id' => $this->qaCategoryId,
            'ownership_type' => 'ozel',
            'address' => 'Test adresi',
            'phone' => '0532 000 00 09',
            'phone_type' => 'mobile',
            'is_published' => true,
            'is_claimed' => false,
            'invitation_status' => 'pending',
            'free_quote_credits' => 0,
            'balance' => 0,
            'source' => 'qa_test',
        ]);
    }

    private function cleanupFacility(Facility $facility): void
    {
        FacilityUser::where('facility_id', $facility->id)->delete();
        BalanceLog::where('facility_id', $facility->id)->delete();
        FacilityClaim::where('facility_id', $facility->id)->delete();
        WalletTopup::withTrashed()->where('facility_id', $facility->id)->forceDelete();
        $facility->forceDelete();
    }

    /**
     * FacilityUserAuth middleware'ini DOGRUDAN calistirir - sadece
     * $user->status alanini degil, GERCEK erisim engelini test eder
     * (bkz. Asama 2 senaryolari).
     */
    private function passesFacilityUserAuth(FacilityUser $facilityUser): bool
    {
        session(['facility_user_id' => $facilityUser->id]);
        $request = Request::create('/');
        $request->setLaravelSession(app('session.store'));

        $reached = false;
        (new FacilityUserAuth())->handle($request, function () use (&$reached) {
            $reached = true;

            return response('ok');
        });

        return $reached;
    }

    private function instantClaimRequest(string $email): Request
    {
        return Request::create('/', 'POST', [
            'applicant_name' => 'QATEST Admin Check',
            'applicant_email' => $email,
            'applicant_phone' => '0532 000 00 09',
        ]);
    }

    // ------------------------------------------------------------------
    // Senaryolar
    // ------------------------------------------------------------------

    private function checkInstantClaimHappyPath(): void
    {
        $failuresBefore = count($this->failures);
        $facility = $this->freshUnclaimedFacility('instant-claim-happy');
        $email = 'qatest.admin.check.instant-claim.happy@example.com';
        FacilityUser::where('email', $email)->delete();
        session()->forget('instant_claim_credentials');

        app(FacilityController::class)->instantClaim($this->instantClaimRequest($email), $facility);
        $facility->refresh();

        $facilityUser = FacilityUser::where('email', $email)->first();
        $expectedCredits = (int) config('platform.free_claim_credits', 5);
        $flow = 'Yerinde Sahiplendirme (normal senaryo)';

        if (! $facility->is_claimed) {
            $this->recordFailure($flow, 'Kurum sahiplenilmiş olarak işaretlenmedi.');
        }
        if (! $facilityUser) {
            $this->recordFailure($flow, 'Kurum yetkilisi hesabı oluşturulmadı.');
        } elseif (! $facilityUser->must_change_password) {
            $this->recordFailure($flow, 'must_change_password işaretlenmedi - kullanıcı ilk girişte şifre değiştirmeye zorlanmaz.');
        }
        if ((int) $facility->free_quote_credits !== $expectedCredits) {
            $this->recordFailure($flow, "Beklenen ücretsiz hak {$expectedCredits}, gerçek {$facility->free_quote_credits}.");
        }

        $credentials = session('instant_claim_credentials');
        if (! $credentials || empty($credentials['password'])) {
            $this->recordFailure($flow, 'Giriş bilgileri (şifre) ekrana/oturuma yansıtılmadı.');
        } else {
            if (! preg_match('/^[A-Za-z0-9]+$/', $credentials['password'])) {
                $this->recordFailure($flow, 'Üretilen geçici şifre sembol içeriyor (kopyalama/yazma sorunu yaratır).');
            }
            if (Mail::sent(FacilityPasswordManuallyResetMail::class, fn ($mail) => $mail->temporaryPassword === $credentials['password'])->isEmpty()) {
                $this->recordFailure($flow, 'Giriş bilgisi maili (doğru şifreyle) gönderilmedi.');
            }
        }

        if (count($this->failures) === $failuresBefore) {
            FacilityUser::where('email', $email)->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkInstantClaimGuards(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Yerinde Sahiplendirme (güvenlik kontrolleri)';
        $controller = app(FacilityController::class);

        // (a) zaten sahiplenilmis bir kurum tekrar sahiplendirilemez
        $claimed = $this->freshUnclaimedFacility('instant-claim-guard-claimed');
        $claimed->update(['is_claimed' => true, 'claimed_at' => now()]);
        $creditsBefore = $claimed->free_quote_credits;
        $blocked = false;
        try {
            $controller->instantClaim($this->instantClaimRequest('qatest.admin.check.guard.claimed@example.com'), $claimed);
        } catch (\Throwable $e) {
            $blocked = method_exists($e, 'getStatusCode') && $e->getStatusCode() === 400;
        }
        if (! $blocked) {
            $this->recordFailure($flow, 'Zaten sahiplenilmiş bir kurum tekrar sahiplendirilebildi.');
        }
        $claimed->refresh();
        if ((int) $claimed->free_quote_credits !== $creditsBefore) {
            $this->recordFailure($flow, 'Engellenmesi gereken denemede bile hak değeri değişti.');
        }

        // (b) baska bir kurum hesabinin e-postasiyla sahiplendirilemez
        $unclaimedA = $this->freshUnclaimedFacility('instant-claim-guard-email-a');
        $unclaimedB = $this->freshUnclaimedFacility('instant-claim-guard-email-b');
        $takenEmail = 'qatest.admin.check.guard.taken-facility@example.com';
        FacilityUser::updateOrCreate(['email' => $takenEmail], [
            'facility_id' => $unclaimedB->id, 'name' => 'QATEST', 'password' => Hash::make(Str::random(20)),
            'status' => 'active', 'must_change_password' => true,
        ]);
        $controller->instantClaim($this->instantClaimRequest($takenEmail), $unclaimedA);
        $unclaimedA->refresh();
        if ($unclaimedA->is_claimed) {
            $this->recordFailure($flow, 'Başka bir kurum hesabına ait e-posta ile sahiplendirme engellenmedi.');
        }

        // (c) bir aile hesabinin e-postasiyla sahiplendirilemez
        $unclaimedC = $this->freshUnclaimedFacility('instant-claim-guard-email-c');
        $familyEmail = 'qatest.admin.check.guard.taken-family@example.com';
        FamilyUser::updateOrCreate(['email' => $familyEmail], [
            'name' => 'QATEST', 'phone' => '05320000000', 'password' => Hash::make(Str::random(20)), 'status' => 'active',
        ]);
        $controller->instantClaim($this->instantClaimRequest($familyEmail), $unclaimedC);
        $unclaimedC->refresh();
        if ($unclaimedC->is_claimed) {
            $this->recordFailure($flow, 'Bir aile hesabına ait e-posta ile sahiplendirme engellenmedi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($claimed);
            $this->cleanupFacility($unclaimedA);
            $this->cleanupFacility($unclaimedB);
            $this->cleanupFacility($unclaimedC);
            FamilyUser::where('email', $familyEmail)->delete();
        }
    }

    private function checkRevertZeroesBonus(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Sahiplenmeyi geri alma (bonus sıfırlama)';
        $facility = $this->freshUnclaimedFacility('revert-zero');
        $email = 'qatest.admin.check.revert-zero@example.com';
        FacilityUser::where('email', $email)->delete();

        $controller = app(FacilityController::class);
        $controller->instantClaim($this->instantClaimRequest($email), $facility);
        $facility->refresh();

        $controller->revertToPreRegistered($facility);
        $facility->refresh();

        if ((float) $facility->balance !== 0.0 || (int) $facility->free_quote_credits !== 0) {
            $this->recordFailure($flow, "Geri alma sonrası bakiye/hak sıfırlanmadı (bakiye={$facility->balance}, hak={$facility->free_quote_credits}).");
        }
        if (! BalanceLog::where('facility_id', $facility->id)->where('type', 'claim_reverted')->exists()) {
            $this->recordFailure($flow, "claim_reverted türünde bir Bakiye/Hak Geçmişi kaydı oluşturulmadı.");
        }

        if (count($this->failures) === $failuresBefore) {
            FacilityUser::where('email', $email)->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkClaimRevertCycleNotCumulative(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Sahiplenme döngüsü (bonus birikmemeli)';
        $facility = $this->freshUnclaimedFacility('cycle-not-cumulative');
        $email = 'qatest.admin.check.cycle-not-cumulative@example.com';
        $expectedCredits = (int) config('platform.free_claim_credits', 5);
        $controller = app(FacilityController::class);

        for ($i = 1; $i <= 3; $i++) {
            FacilityUser::where('email', $email)->delete();
            $controller->instantClaim($this->instantClaimRequest($email), $facility);
            $facility->refresh();
            $controller->revertToPreRegistered($facility);
            $facility->refresh();
        }

        FacilityUser::where('email', $email)->delete();
        $controller->instantClaim($this->instantClaimRequest($email), $facility);
        $facility->refresh();

        if ((int) $facility->free_quote_credits !== $expectedCredits) {
            $this->recordFailure($flow, "3 sahiplendir/geri-al döngüsü sonrası 4. sahiplendirmede hak {$facility->free_quote_credits}, beklenen {$expectedCredits} - bonus birikiyor olabilir.");
        }

        $controller->revertToPreRegistered($facility);
        $facility->refresh();
        if ((int) $facility->free_quote_credits !== 0 || (float) $facility->balance !== 0.0) {
            $this->recordFailure($flow, 'Son geri alma sonrası bakiye/hak sıfırlanmadı.');
        }

        if (count($this->failures) === $failuresBefore) {
            FacilityUser::where('email', $email)->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkBrandLoginUrlResolution(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Doğru site yönlendirmesi';
        $facility = $this->freshUnclaimedFacility('brand-login-url');

        $claim = FacilityClaim::create([
            'facility_id' => $facility->id,
            'brand' => 'bakimeviara',
            'applicant_name' => 'QATEST',
            'applicant_email' => 'qatest.admin.check.brand-login-url@example.com',
            'applicant_phone' => '0532 000 00 09',
            'document_path' => 'qatest-admin-check.jpg',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $resolvedBrand = facility_login_brand_slug($facility->fresh());
        if ($resolvedBrand !== 'bakimeviara') {
            $this->recordFailure($flow, "Kurumun onaylanmış başvurusu bakimeviara markasından geldiği halde tespit edilen marka: {$resolvedBrand}.");
        }
        $url = facility_brand_login_url($resolvedBrand);
        if (! str_contains($url, 'bakimeviara')) {
            $this->recordFailure($flow, "Üretilen giriş linki beklenen markayı içermiyor: {$url}.");
        }

        if (count($this->failures) === $failuresBefore) {
            $claim->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkBalanceAdjust(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Bakiye/Hak manuel düzenleme';
        $facility = $this->freshUnclaimedFacility('balance-adjust');
        $controller = app(BalanceController::class);

        $controller->adjust(Request::create('/', 'POST', ['balance_delta' => 100, 'credits_delta' => 3]), $facility);
        $facility->refresh();
        if ((float) $facility->balance !== 100.0 || (int) $facility->free_quote_credits !== 3) {
            $this->recordFailure($flow, "Pozitif düzenleme sonrası bakiye={$facility->balance} hak={$facility->free_quote_credits}, beklenen bakiye=100 hak=3.");
        }

        $controller->adjust(Request::create('/', 'POST', ['balance_delta' => -500, 'credits_delta' => -50]), $facility);
        $facility->refresh();
        if ((float) $facility->balance !== 0.0 || (int) $facility->free_quote_credits !== 0) {
            $this->recordFailure($flow, "Aşırı negatif düzenleme sıfırın altına düşürdü (bakiye={$facility->balance} hak={$facility->free_quote_credits}) - taban kontrolü çalışmıyor.");
        }

        $logCountBefore = BalanceLog::where('facility_id', $facility->id)->count();
        $controller->adjust(Request::create('/', 'POST', ['balance_delta' => 0, 'credits_delta' => 0]), $facility);
        $logCountAfter = BalanceLog::where('facility_id', $facility->id)->count();
        if ($logCountAfter !== $logCountBefore) {
            $this->recordFailure($flow, 'Etkisiz (net sıfır) bir düzenleme sahte bir geçmiş kaydı yazdı.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkBalanceLogUpdate(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Bakiye/Hak geçmiş kaydını düzenleme';
        $facility = $this->freshUnclaimedFacility('balance-log-update');
        $controller = app(BalanceController::class);

        $log = BalanceLog::create([
            'facility_id' => $facility->id, 'type' => 'admin_adjust_credits', 'amount' => 0,
            'credits_amount' => 10, 'balance_after' => $facility->balance, 'credits_after' => $facility->free_quote_credits + 10,
            'admin_id' => $this->adminId, 'note' => 'QATEST admin check',
        ]);
        $facility->increment('free_quote_credits', 10);
        $facility->refresh();
        $creditsBefore = $facility->free_quote_credits;

        $controller->updateLog(Request::create('/', 'PUT', ['amount' => 0, 'credits_amount' => 4, 'note' => 'guncellendi']), $facility, $log);
        $facility->refresh();
        $expected = $creditsBefore - (10 - 4);
        if ((int) $facility->free_quote_credits !== $expected) {
            $this->recordFailure($flow, "Kayıt 10 haktan 4 hakka düzenlendi ama kurumun güncel hakkı {$facility->free_quote_credits}, beklenen {$expected}.");
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkBalanceLogDestroy(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Bakiye/Hak geçmiş kaydını silme';
        $facility = $this->freshUnclaimedFacility('balance-log-destroy');
        $controller = app(BalanceController::class);

        $log = BalanceLog::create([
            'facility_id' => $facility->id, 'type' => 'admin_adjust_credits', 'amount' => 0,
            'credits_amount' => 6, 'balance_after' => $facility->balance, 'credits_after' => $facility->free_quote_credits + 6,
            'admin_id' => $this->adminId, 'note' => 'QATEST admin check',
        ]);
        $facility->increment('free_quote_credits', 6);
        $facility->refresh();
        $creditsBefore = $facility->free_quote_credits;

        $controller->destroyLog($facility, $log);
        $facility->refresh();

        if ((int) $facility->free_quote_credits !== $creditsBefore - 6) {
            $this->recordFailure($flow, 'Kayıt silindi ama kurumun güncel hakkına doğru yansımadı ('.$facility->free_quote_credits.', beklenen '.($creditsBefore - 6).').');
        }
        if (BalanceLog::find($log->id)) {
            $this->recordFailure($flow, 'Silinen kayıt veritabanında hâlâ mevcut.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkWalletTopupApprove(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Bakiye yükleme onayı';
        $facility = $this->freshUnclaimedFacility('topup-approve');
        $controller = app(WalletTopupController::class);

        $topup = WalletTopup::create([
            'facility_id' => $facility->id, 'amount' => 250, 'receipt_path' => 'qatest-admin-check.jpg',
            'bonus_quote_credits_snapshot' => 4, 'status' => 'pending',
        ]);

        $controller->approve($topup);
        $facility->refresh();
        $topup->refresh();

        if ((float) $facility->balance !== 250.0) {
            $this->recordFailure($flow, "Onay sonrası bakiye {$facility->balance}, beklenen 250.");
        }
        if ((int) $facility->free_quote_credits !== 4) {
            $this->recordFailure($flow, "Onay sonrası hak {$facility->free_quote_credits}, beklenen 4.");
        }
        if ($topup->status !== 'approved') {
            $this->recordFailure($flow, 'Talep durumu onaylandı olarak işaretlenmedi.');
        }
        if (! BalanceLog::where('facility_id', $facility->id)->where('type', 'topup_approved')->exists()) {
            $this->recordFailure($flow, 'topup_approved türünde bir geçmiş kaydı oluşturulmadı.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkWalletTopupReject(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Bakiye yükleme reddi';
        $facility = $this->freshUnclaimedFacility('topup-reject');
        $controller = app(WalletTopupController::class);

        $topup = WalletTopup::create([
            'facility_id' => $facility->id, 'amount' => 300, 'receipt_path' => 'qatest-admin-check.jpg', 'status' => 'pending',
        ]);
        $balanceBefore = $facility->balance;

        $controller->reject(Request::create('/', 'POST', ['admin_note' => 'QATEST red gerekcesi']), $topup);
        $facility->refresh();
        $topup->refresh();

        if ((float) $facility->balance !== (float) $balanceBefore) {
            $this->recordFailure($flow, "Reddedilen bir talep bakiyeyi değiştirdi ({$facility->balance}, beklenen {$balanceBefore}).");
        }
        if ($topup->status !== 'rejected' || $topup->admin_note !== 'QATEST red gerekcesi') {
            $this->recordFailure($flow, 'Durum/gerekçe doğru kaydedilmedi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkWalletTopupDoubleApproveGuard(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Bakiye yükleme çift onay engeli';
        $facility = $this->freshUnclaimedFacility('topup-double-approve');
        $controller = app(WalletTopupController::class);

        $topup = WalletTopup::create([
            'facility_id' => $facility->id, 'amount' => 150, 'receipt_path' => 'qatest-admin-check.jpg', 'status' => 'pending',
        ]);
        $controller->approve($topup);
        $facility->refresh();
        $balanceAfterFirst = $facility->balance;

        $blocked = false;
        try {
            $controller->approve($topup->fresh());
        } catch (\Throwable $e) {
            $blocked = method_exists($e, 'getStatusCode') && $e->getStatusCode() === 400;
        }
        $facility->refresh();

        if (! $blocked) {
            $this->recordFailure($flow, 'Zaten onaylanmış bir talep tekrar onaylanabildi.');
        }
        if ((float) $facility->balance !== (float) $balanceAfterFirst) {
            $this->recordFailure($flow, "Çift onay bakiyeyi tekrar artırdı ({$facility->balance}, beklenen {$balanceAfterFirst}).");
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkFacilityClaimApprove(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Sahiplenme başvurusu onayı';
        $facility = $this->freshUnclaimedFacility('claim-approve');
        $email = 'qatest.admin.check.claim-approve@example.com';
        FacilityUser::where('email', $email)->delete();
        $expectedCredits = (int) config('platform.free_claim_credits', 5);

        $claim = FacilityClaim::create([
            'facility_id' => $facility->id, 'brand' => 'bakimevleri', 'applicant_name' => 'QATEST',
            'applicant_email' => $email, 'applicant_phone' => '0532 000 00 09',
            'document_path' => 'qatest-admin-check.jpg', 'status' => 'pending',
        ]);

        app(FacilityClaimController::class)->approve(Request::create('/', 'POST'), $claim);
        $facility->refresh();
        $claim->refresh();

        if (! $facility->is_claimed) {
            $this->recordFailure($flow, 'Onay sonrası kurum sahiplenilmiş olarak işaretlenmedi.');
        }
        if ($claim->status !== 'approved') {
            $this->recordFailure($flow, 'Başvuru durumu onaylandı olarak işaretlenmedi.');
        }
        if ((int) $facility->free_quote_credits !== $expectedCredits) {
            $this->recordFailure($flow, "Onay sonrası hak {$facility->free_quote_credits}, beklenen {$expectedCredits}.");
        }
        if (! FacilityUser::where('email', $email)->exists()) {
            $this->recordFailure($flow, 'Kurum yetkilisi hesabı oluşturulmadı.');
        }

        if (count($this->failures) === $failuresBefore) {
            FacilityUser::where('email', $email)->delete();
            $this->cleanupFacility($facility);
        }
    }

    // ------------------------------------------------------------------
    // Asama 2 senaryolari - hesap durumu ve erisim islemleri
    // ------------------------------------------------------------------

    private function checkFamilyStatusToggleEnforced(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Hesap askıya alma/aktifleştirme (Aile)';
        $email = 'qatest.admin.check.family-status@example.com';
        FamilyUser::where('email', $email)->delete();
        $family = FamilyUser::create([
            'name' => 'QATEST', 'email' => $email, 'phone' => '05320000000',
            'password' => Hash::make('QaTest12345!'), 'status' => 'active', 'email_verified_at' => now(),
        ]);

        app(UserController::class)->toggleFamilyStatus($family);
        $family->refresh();
        if ($family->status !== 'suspended') {
            $this->recordFailure($flow, 'Askıya alma sonrası durum "suspended" olmadı.');
        }

        session(['family_user_id' => $family->id]);
        $request = Request::create('/');
        $request->setLaravelSession(app('session.store'));
        $reached = false;
        (new FamilyAuth())->handle($request, function () use (&$reached) {
            $reached = true;

            return response('ok');
        });
        if ($reached) {
            $this->recordFailure($flow, 'Askıya alınmış bir aile hesabı panele erişebildi (middleware engellemedi).');
        }

        app(UserController::class)->toggleFamilyStatus($family->fresh());
        $family->refresh();
        if ($family->status !== 'active') {
            $this->recordFailure($flow, 'Yeniden aktifleştirme sonrası durum "active" olmadı.');
        }

        session(['family_user_id' => $family->id]);
        $request2 = Request::create('/');
        $request2->setLaravelSession(app('session.store'));
        $reached2 = false;
        (new FamilyAuth())->handle($request2, function () use (&$reached2) {
            $reached2 = true;

            return response('ok');
        });
        if (! $reached2) {
            $this->recordFailure($flow, 'Yeniden aktifleştirilen hesap hâlâ erişim sağlayamıyor.');
        }

        session()->forget('family_user_id');
        if (count($this->failures) === $failuresBefore) {
            $family->delete();
        }
    }

    private function checkFacilityUserStatusToggleEnforced(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Hesap askıya alma/aktifleştirme (Kurum Yetkilisi)';
        $facility = $this->freshUnclaimedFacility('status-toggle');
        $email = 'qatest.admin.check.facility-status@example.com';
        FacilityUser::where('email', $email)->delete();
        $facilityUser = FacilityUser::create([
            'facility_id' => $facility->id, 'name' => 'QATEST', 'email' => $email,
            'password' => Hash::make('QaTest12345!'), 'status' => 'active',
            'must_change_password' => false, 'email_verified_at' => now(),
        ]);

        app(UserController::class)->toggleFacilityUserStatus($facilityUser);
        $facilityUser->refresh();
        if ($facilityUser->status !== 'suspended') {
            $this->recordFailure($flow, 'Askıya alma sonrası durum "suspended" olmadı.');
        }
        if ($this->passesFacilityUserAuth($facilityUser)) {
            $this->recordFailure($flow, 'Askıya alınmış bir kurum yetkilisi panele erişebildi (middleware engellemedi).');
        }

        app(UserController::class)->toggleFacilityUserStatus($facilityUser->fresh());
        $facilityUser->refresh();
        if ($facilityUser->status !== 'active') {
            $this->recordFailure($flow, 'Yeniden aktifleştirme sonrası durum "active" olmadı.');
        }
        if (! $this->passesFacilityUserAuth($facilityUser)) {
            $this->recordFailure($flow, 'Yeniden aktifleştirilen hesap hâlâ erişim sağlayamıyor.');
        }

        session()->forget('facility_user_id');
        if (count($this->failures) === $failuresBefore) {
            $facilityUser->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkImpersonationRoundTrip(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Kullanıcı olarak görüntüleme (impersonation)';
        session(['admin_id' => $this->adminId, 'admin_name' => 'QATEST Admin']);

        $email = 'qatest.admin.check.impersonation@example.com';
        FamilyUser::where('email', $email)->delete();
        $family = FamilyUser::create([
            'name' => 'QATEST', 'email' => $email, 'phone' => '05320000000',
            'password' => Hash::make('QaTest12345!'), 'status' => 'active', 'email_verified_at' => now(),
        ]);

        app(UserController::class)->impersonateFamilyUser($family);

        if (session('admin_id') !== null) {
            $this->recordFailure($flow, 'Görüntüleme başlarken admin oturumu tamamen temizlenmedi.');
        }
        if ((int) session('family_user_id') !== $family->id) {
            $this->recordFailure($flow, 'Görüntüleme başlarken hedef aile oturumu kurulmadı.');
        }
        if ((int) session('impersonator_admin_id') !== $this->adminId) {
            $this->recordFailure($flow, 'Orijinal admin kimliği impersonator_admin_id altında saklanmadı.');
        }

        $stopRequest = Request::create('/');
        $stopRequest->setLaravelSession(app('session.store'));
        app(ImpersonationController::class)->stop($stopRequest);

        if ((int) session('admin_id') !== $this->adminId) {
            $this->recordFailure($flow, 'Görüntüleme bitince orijinal admin oturumu geri yüklenmedi.');
        }
        if (session('family_user_id') !== null || session('impersonator_admin_id') !== null) {
            $this->recordFailure($flow, 'Görüntüleme bitince hedef kullanıcı/impersonator izleri oturumda kaldı.');
        }

        session(['admin_id' => $this->adminId]);
        if (count($this->failures) === $failuresBefore) {
            $family->delete();
        }
    }

    private function checkFacilityPasswordResetInvalidatesOld(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Kurum yetkilisi şifre sıfırlama';
        $facility = $this->freshUnclaimedFacility('password-reset');
        $email = 'qatest.admin.check.password-reset@example.com';
        FacilityUser::where('email', $email)->delete();
        $oldPassword = 'QaTestEski12345!';
        $facilityUser = FacilityUser::create([
            'facility_id' => $facility->id, 'name' => 'QATEST', 'email' => $email,
            'password' => Hash::make($oldPassword), 'status' => 'active',
            'must_change_password' => false, 'email_verified_at' => now(),
        ]);

        app(UserController::class)->resetFacilityUserPassword($facilityUser);
        $facilityUser->refresh();

        if (Hash::check($oldPassword, $facilityUser->password)) {
            $this->recordFailure($flow, 'Şifre sıfırlandıktan sonra ESKİ şifre hâlâ geçerli.');
        }
        if (! $facilityUser->must_change_password) {
            $this->recordFailure($flow, 'must_change_password işaretlenmedi.');
        }

        $sentMails = Mail::sent(FacilityPasswordManuallyResetMail::class, fn ($mail) => $mail->facilityUser->is($facilityUser));
        if ($sentMails->isEmpty()) {
            $this->recordFailure($flow, 'Yeni şifre maili gönderilmedi.');
        } elseif (! Hash::check($sentMails->first()->temporaryPassword, $facilityUser->password)) {
            $this->recordFailure($flow, 'Mailde gönderilen yeni şifre, kayıtlı şifreyle eşleşmiyor.');
        }

        if (count($this->failures) === $failuresBefore) {
            $facilityUser->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkDestroyFacilityUserDoesNotOrphan(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Kurum yetkilisi hesabını silme';
        $facility = $this->freshUnclaimedFacility('destroy-user');
        $email = 'qatest.admin.check.destroy-user@example.com';
        FacilityUser::where('email', $email)->delete();
        $facilityUser = FacilityUser::create([
            'facility_id' => $facility->id, 'name' => 'QATEST', 'email' => $email,
            'password' => Hash::make('QaTest12345!'), 'status' => 'active',
            'must_change_password' => false, 'email_verified_at' => now(),
        ]);
        $topup = WalletTopup::create([
            'facility_id' => $facility->id, 'facility_user_id' => $facilityUser->id,
            'amount' => 50, 'receipt_path' => 'qatest-admin-check.jpg', 'status' => 'pending',
        ]);

        app(UserController::class)->destroyFacilityUser($facilityUser);

        if (FacilityUser::find($facilityUser->id)) {
            $this->recordFailure($flow, 'Hesap silindikten sonra hâlâ veritabanında mevcut.');
        }
        if (! Facility::find($facility->id)) {
            $this->recordFailure($flow, 'Kurum yetkilisi silinince kurumun kendisi de yanlışlıkla silindi.');
        }
        $topup->refresh();
        if ($topup->facility_user_id !== null) {
            $this->recordFailure($flow, 'Silinen hesaba ait bakiye yükleme kaydının facility_user_id alanı null olmadı (yetim referans riski).');
        }

        if (count($this->failures) === $failuresBefore) {
            $topup->forceDelete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkRevertSuspensionBlocksAccess(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Sahiplenmeyi geri alma (erişim engeli)';
        $facility = $this->freshUnclaimedFacility('revert-access-block');
        $email = 'qatest.admin.check.revert-access-block@example.com';
        FacilityUser::where('email', $email)->delete();

        $controller = app(FacilityController::class);
        $controller->instantClaim($this->instantClaimRequest($email), $facility);
        $facility->refresh();
        $facilityUser = FacilityUser::where('email', $email)->first();
        if (! $facilityUser) {
            $this->recordFailure($flow, 'Test kurulumu başarısız - kurum yetkilisi oluşturulamadı.');

            return;
        }
        $facilityUser->update(['email_verified_at' => now(), 'must_change_password' => false]);

        $controller->revertToPreRegistered($facility);
        $facilityUser->refresh();

        if ($facilityUser->status !== 'suspended') {
            $this->recordFailure($flow, 'Geri alma sonrası kurum yetkilisi hesabı askıya alınmadı.');
        }
        if ($this->passesFacilityUserAuth($facilityUser)) {
            $this->recordFailure($flow, 'Askıya alınmış olmasına rağmen kurum yetkilisi panele erişebildi.');
        }

        session()->forget('facility_user_id');
        if (count($this->failures) === $failuresBefore) {
            FacilityUser::where('email', $email)->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkAccountDeletionApprove(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Hesap silme talebi onayı';
        $email = 'qatest.admin.check.deletion-approve@example.com';
        FamilyUser::where('email', $email)->delete();
        $family = FamilyUser::create([
            'name' => 'QATEST Silinecek', 'email' => $email, 'phone' => '05320000000',
            'password' => Hash::make('QaTest12345!'), 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $deletionRequest = AccountDeletionRequest::create([
            'requestable_type' => FamilyUser::class, 'requestable_id' => $family->id,
            'requested_at' => now(), 'status' => 'pending',
        ]);

        app(AccountDeletionController::class)->approve($deletionRequest);
        $family->refresh();
        $deletionRequest->refresh();

        if ($family->status !== 'deleted') {
            $this->recordFailure($flow, 'Onay sonrası hesap durumu "deleted" olmadı.');
        }
        if ($family->email === $email) {
            $this->recordFailure($flow, 'Onay sonrası e-posta anonimleştirilmedi (KVKK riski).');
        }
        if ($deletionRequest->status !== 'completed') {
            $this->recordFailure($flow, 'Talep durumu "completed" olarak işaretlenmedi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $deletionRequest->delete();
            $family->delete();
        }
    }

    private function checkAccountDeletionRejectLeavesAccountActive(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Hesap silme talebi reddi';
        $email = 'qatest.admin.check.deletion-reject@example.com';
        FamilyUser::where('email', $email)->delete();
        $family = FamilyUser::create([
            'name' => 'QATEST Reddedilecek', 'email' => $email, 'phone' => '05320000000',
            'password' => Hash::make('QaTest12345!'), 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $deletionRequest = AccountDeletionRequest::create([
            'requestable_type' => FamilyUser::class, 'requestable_id' => $family->id,
            'requested_at' => now(), 'status' => 'pending',
        ]);

        app(AccountDeletionController::class)->reject(Request::create('/', 'POST', ['admin_note' => 'QATEST red']), $deletionRequest);
        $family->refresh();
        $deletionRequest->refresh();

        if ($family->status !== 'active' || $family->email !== $email) {
            $this->recordFailure($flow, 'Reddedilen bir talep hesabı değiştirdi (kısmen silinmiş olabilir).');
        }
        if ($deletionRequest->status !== 'rejected') {
            $this->recordFailure($flow, 'Talep durumu "rejected" olarak işaretlenmedi.');
        }

        $blocked = false;
        try {
            app(AccountDeletionController::class)->approve($deletionRequest->fresh());
        } catch (\Throwable $e) {
            $blocked = method_exists($e, 'getStatusCode') && $e->getStatusCode() === 400;
        }
        if (! $blocked) {
            $this->recordFailure($flow, 'Zaten reddedilmiş bir talep sonradan onaylanabildi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $deletionRequest->delete();
            $family->delete();
        }
    }

    // ------------------------------------------------------------------
    // Asama 3 senaryolari - su ana kadar hicbir yerde test edilmemis bolumler
    // ------------------------------------------------------------------

    private function checkOccupancyUpdate(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Doluluk Durumu güncelleme';
        $facility = $this->freshUnclaimedFacility('occupancy');
        $controller = app(OccupancyController::class);

        $controller->update(Request::create('/', 'POST', ['vacant_beds_male' => 3, 'vacant_beds_female' => 5]), $facility);
        $facility->refresh();
        if ((int) $facility->vacant_beds_male !== 3 || (int) $facility->vacant_beds_female !== 5 || ! $facility->vacant_beds_updated_at) {
            $this->recordFailure($flow, 'Geçerli doluluk bilgisi doğru kaydedilmedi.');
        }

        $rejected = false;
        try {
            $controller->update(Request::create('/', 'POST', ['vacant_beds_male' => -1]), $facility);
        } catch (ValidationException) {
            $rejected = true;
        }
        if (! $rejected) {
            $this->recordFailure($flow, 'Negatif boş yatak sayısı reddedilmedi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkBrokerFacilityToggle(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Aracılık - Anlaşmalı Kurum işaretleme';
        $facility = $this->freshUnclaimedFacility('broker-toggle');
        $controller = app(BrokerController::class);

        $controller->toggleFacility($facility);
        $facility->refresh();
        if (! $facility->is_broker_managed) {
            $this->recordFailure($flow, 'İlk işaretlemede is_broker_managed true olmadı.');
        }
        // 28 Agustos 2026: kullanicinin talebi - kurum tanitim broşüründe
        // vaat edilen "anlaşmalı kurumlar otomatik Öne Çıkan'da gösterilir"
        // sozunun GERCEKTEN tutuldugunu dogrular (bkz. BrokerController::
        // toggleFacility ayni tarihli yorum).
        if (! $facility->is_featured) {
            $this->recordFailure($flow, 'Anlaşmalı işaretlenince kurum otomatik Öne Çıkan yapılmadı.');
        }

        $controller->toggleFacility($facility);
        $facility->refresh();
        if ($facility->is_broker_managed) {
            $this->recordFailure($flow, 'İkinci işaretlemede (geri alma) is_broker_managed false olmadı.');
        }
        if (! $facility->is_featured) {
            $this->recordFailure($flow, 'Anlaşmalı statüsü geri alınınca Öne Çıkan durumu (yanlışlıkla) kaldırılmış - bu durum korunmalıydı.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkBrokerReferralLifecycle(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Aracılık - Yönlendirme yaşam döngüsü';
        $facility = $this->freshUnclaimedFacility('broker-referral');
        $controller = app(BrokerController::class);

        $controller->storeReferral(Request::create('/', 'POST', [
            'facility_id' => $facility->id, 'family_name' => 'QATEST Aile',
            'patient_name' => 'QATEST Hasta', 'patient_age' => 70, 'patient_mobility' => 'yurutebiliyor',
            'referred_at' => now()->toDateString(),
        ]));
        $referral = BrokerReferral::where('facility_id', $facility->id)->first();

        if (! $referral) {
            $this->recordFailure($flow, 'Yeni yönlendirme oluşturulmadı.');
        } else {
            if ($referral->status !== 'yonlendirildi' || $referral->fee_status !== 'bekliyor') {
                $this->recordFailure($flow, 'Yeni yönlendirmenin varsayılan durumu yanlış.');
            }

            $controller->updateReferral(Request::create('/', 'POST', [
                'status' => 'yerlesti', 'fee_status' => 'odendi', 'fee_amount' => 500,
            ]), $referral);
            $referral->refresh();
            if ($referral->status !== 'yerlesti' || $referral->fee_status !== 'odendi' || (float) $referral->fee_amount !== 500.0) {
                $this->recordFailure($flow, 'Yönlendirme güncellemesi doğru uygulanmadı.');
            }

            $controller->destroyReferral($referral);
            if (BrokerReferral::find($referral->id)) {
                $this->recordFailure($flow, 'Silinen yönlendirme hâlâ veritabanında mevcut.');
            }
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkDocumentShowServesFile(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Belge görüntüleme (dosya sunumu)';
        $facility = $this->freshUnclaimedFacility('document-show');
        $path = 'claims/qatest-admin-check-document.png';
        Storage::disk('local')->put($path, 'QATEST-BINARY-CONTENT');

        $claim = FacilityClaim::create([
            'facility_id' => $facility->id, 'brand' => 'bakimevleri', 'applicant_name' => 'QATEST',
            'applicant_email' => 'qatest.admin.check.document-show@example.com', 'applicant_phone' => '0532 000 00 09',
            'document_path' => $path, 'status' => 'pending',
        ]);

        $response = app(DocumentController::class)->show('claim', $claim->id);
        if ($response->getStatusCode() !== 200) {
            $this->recordFailure($flow, "Geçerli bir belge için beklenmeyen HTTP durumu: {$response->getStatusCode()}.");
        }

        Storage::disk('local')->delete($path);
        if (count($this->failures) === $failuresBefore) {
            $claim->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkDocumentShowRejectsInvalid(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Belge görüntüleme (güvenlik/hata kontrolleri)';
        $controller = app(DocumentController::class);

        $blockedType = false;
        try {
            $controller->show('invalid-type', 1);
        } catch (\Throwable $e) {
            $blockedType = method_exists($e, 'getStatusCode') && $e->getStatusCode() === 404;
        }
        if (! $blockedType) {
            $this->recordFailure($flow, 'Geçersiz belge türü 404 ile reddedilmedi.');
        }

        $blockedMissing = false;
        try {
            $controller->show('claim', 999999999);
        } catch (\Throwable $e) {
            $blockedMissing = ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)
                || (method_exists($e, 'getStatusCode') && $e->getStatusCode() === 404);
        }
        if (! $blockedMissing) {
            $this->recordFailure($flow, 'Var olmayan bir belge kaydı için 404 dönmedi.');
        }

        $facility = $this->freshUnclaimedFacility('document-missing-file');
        $claim = FacilityClaim::create([
            'facility_id' => $facility->id, 'brand' => 'bakimevleri', 'applicant_name' => 'QATEST',
            'applicant_email' => 'qatest.admin.check.document-missing@example.com', 'applicant_phone' => '0532 000 00 09',
            'document_path' => 'claims/qatest-hic-olmayan-dosya.png', 'status' => 'pending',
        ]);
        $blockedNoFile = false;
        try {
            $controller->show('claim', $claim->id);
        } catch (\Throwable $e) {
            $blockedNoFile = method_exists($e, 'getStatusCode') && $e->getStatusCode() === 404;
        }
        if (! $blockedNoFile) {
            $this->recordFailure($flow, 'Diskte dosyası olmayan bir kayıt için 404 dönmedi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $claim->delete();
            $this->cleanupFacility($facility);
        }
    }

    private function checkCityCreateAndTrashedGuard(): void
    {
        $flow = 'Şehir oluşturma ve silme koruması';
        $name = 'QATEST Admin Check Şehir';
        City::where('name', $name)->delete();
        $controller = app(CityController::class);

        $controller->store(Request::create('/', 'POST', ['name' => $name]));
        $city = City::where('name', $name)->first();
        if (! $city) {
            $this->recordFailure($flow, 'Yeni şehir oluşturulmadı.');

            return;
        }

        $rejectedDuplicate = false;
        try {
            $controller->store(Request::create('/', 'POST', ['name' => $name]));
        } catch (ValidationException) {
            $rejectedDuplicate = true;
        }
        if (! $rejectedDuplicate) {
            $this->recordFailure($flow, 'Aynı isimle ikinci bir şehir oluşturulabildi.');
        }

        $facility = $this->freshUnclaimedFacility('city-trashed-guard');
        $facility->update(['city_id' => $city->id]);
        $facility->delete();

        $controller->destroy($city);
        if (! City::find($city->id)) {
            $this->recordFailure($flow, 'Sadece çöp kutusundaki bir kuruma sahip şehir silinebildi - geri dönüşü olmayan kurum kaybı riski.');
        }

        $facility->forceDelete();
        $controller->destroy($city->fresh());
        if (City::find($city->id)) {
            $this->recordFailure($flow, 'Bağlı kurum kalmayınca (blocker temizlendikten sonra) şehir yine de silinemedi.');
        }
    }

    private function checkCategoryCreateAndPriceTiers(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Kategori oluşturma ve fiyat segmenti güncelleme';
        $name = 'QATEST Admin Check Kategori';
        FacilityCategory::where('name', $name)->delete();
        $controller = app(FacilityCategoryController::class);

        $controller->store(Request::create('/', 'POST', ['name' => $name, 'brand_scope' => 'yasli-bakim']));
        $category = FacilityCategory::where('name', $name)->first();
        if (! $category) {
            $this->recordFailure($flow, 'Yeni kategori oluşturulmadı.');

            return;
        }

        $controller->updatePriceTiers(Request::create('/', 'POST', [
            'price_tier_standart_min' => 1000, 'price_tier_premium_min' => 2000, 'price_tier_ultra_min' => 3000,
        ]), $category);
        $category->refresh();
        if ((int) $category->price_tier_standart_min !== 1000 || (int) $category->price_tier_premium_min !== 2000 || (int) $category->price_tier_ultra_min !== 3000) {
            $this->recordFailure($flow, 'Fiyat segmenti eşikleri doğru kaydedilmedi.');
        }

        $rejected = false;
        try {
            $controller->updatePriceTiers(Request::create('/', 'POST', [
                'price_tier_standart_min' => 3000, 'price_tier_premium_min' => 2000, 'price_tier_ultra_min' => 1000,
            ]), $category);
        } catch (ValidationException) {
            $rejected = true;
        }
        if (! $rejected) {
            $this->recordFailure($flow, 'Sıralaması bozuk (artan olmayan) eşikler kabul edildi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $category->delete();
        }
    }

    private function checkCategoryTrashedGuard(): void
    {
        $flow = 'Kategori silme koruması (çöp kutusu)';
        $name = 'QATEST Admin Check Kategori Guard';
        FacilityCategory::where('name', $name)->delete();
        $controller = app(FacilityCategoryController::class);
        $category = FacilityCategory::create(['name' => $name, 'slug' => Str::slug($name), 'brand_scope' => 'yasli-bakim']);

        $facility = $this->freshUnclaimedFacility('category-trashed-guard');
        $facility->update(['facility_category_id' => $category->id]);
        $facility->delete();

        $controller->destroy($category);
        if (! FacilityCategory::find($category->id)) {
            $this->recordFailure($flow, 'Sadece çöp kutusundaki bir kuruma sahip kategori silinebildi.');
        }

        $facility->forceDelete();
        $controller->destroy($category->fresh());
        if (FacilityCategory::find($category->id)) {
            $this->recordFailure($flow, 'Bağlı kurum kalmayınca kategori yine de silinemedi.');
        }
    }

    // ------------------------------------------------------------------
    // Asama 4 senaryolari - geri kalan, daha dusuk riskli CRUD bolumleri
    // ------------------------------------------------------------------

    private function checkContentPageLifecycleAndSanitization(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Sayfa oluşturma/güncelleme/silme + HTML temizliği';
        $title = 'QATEST Admin Check Sayfa';
        ContentPage::where('title', $title)->delete();
        $controller = app(ContentPageController::class);

        $controller->store(Request::create('/', 'POST', [
            'brand' => 'bakimevleri', 'type' => 'page', 'title' => $title,
            'body' => '<p>Merhaba</p><script>alert(1)</script><p onclick="evil()">tıkla</p>',
        ]));
        $page = ContentPage::where('title', $title)->first();
        if (! $page) {
            $this->recordFailure($flow, 'Yeni sayfa oluşturulmadı.');

            return;
        }
        if (str_contains($page->body, '<script') || str_contains($page->body, 'onclick')) {
            $this->recordFailure($flow, 'Sayfa içeriğindeki tehlikeli HTML (script/onclick) temizlenmedi - XSS riski.');
        }
        if (! str_contains($page->body, 'Merhaba')) {
            $this->recordFailure($flow, 'Zararsız içerik de yanlışlıkla silinmiş olabilir.');
        }

        $newTitle = 'QATEST Admin Check Sayfa Güncellendi';
        $controller->update(Request::create('/', 'POST', [
            'brand' => 'bakimevleri', 'type' => 'page', 'title' => $newTitle, 'body' => '<p>Güncel</p>',
        ]), $page);
        $page->refresh();
        if ($page->title !== $newTitle || $page->slug !== Str::slug($newTitle)) {
            $this->recordFailure($flow, 'Başlık değişince sayfa/slug doğru güncellenmedi.');
        }

        $controller->destroy($page);
        if (ContentPage::find($page->id)) {
            $this->recordFailure($flow, 'Sayfa silindikten sonra hâlâ mevcut.');
        }
    }

    private function checkFaqLifecycleAndCacheInvalidation(): void
    {
        $flow = 'SSS oluşturma/güncelleme/silme + önbellek temizliği';
        $question = 'QATEST Admin Check Soru?';
        Faq::where('question', $question)->delete();
        $controller = app(FaqController::class);
        Cache::put('faqs:bakimevleri', 'ESKI-DEGER', 60);

        $controller->store(Request::create('/', 'POST', [
            'brand' => 'bakimevleri', 'question' => $question, 'answer' => 'QATEST cevap',
        ]));
        $faq = Faq::where('question', $question)->first();
        if (! $faq) {
            $this->recordFailure($flow, 'Yeni SSS kaydı oluşturulmadı.');

            return;
        }
        if (Cache::has('faqs:bakimevleri')) {
            $this->recordFailure($flow, 'Yeni SSS eklenince ilgili önbellek temizlenmedi.');
        }

        Cache::put('faqs:bakimevleri', 'ESKI-DEGER', 60);
        $controller->update(Request::create('/', 'POST', ['question' => $question, 'answer' => 'QATEST güncellenmiş cevap']), $faq);
        $faq->refresh();
        if ($faq->answer !== 'QATEST güncellenmiş cevap') {
            $this->recordFailure($flow, 'SSS güncellemesi kaydedilmedi.');
        }
        if (Cache::has('faqs:bakimevleri')) {
            $this->recordFailure($flow, 'SSS güncellenince önbellek temizlenmedi.');
        }

        $controller->destroy($faq);
        if (Faq::find($faq->id)) {
            $this->recordFailure($flow, 'SSS silindikten sonra hâlâ mevcut.');
        }
    }

    private function checkSubscriptionPackageLifecycle(): void
    {
        $flow = 'Paket oluşturma/güncelleme/silme + önbellek temizliği';
        $name = 'QATEST Admin Check Paket';
        SubscriptionPackage::where('name', $name)->delete();
        $controller = app(SubscriptionPackageController::class);
        Cache::put('subscription_packages:active', 'ESKI-DEGER', 60);

        $controller->store(Request::create('/', 'POST', ['name' => $name, 'price' => 199, 'bonus_quote_credits' => 3]));
        $package = SubscriptionPackage::where('name', $name)->first();
        if (! $package) {
            $this->recordFailure($flow, 'Yeni paket oluşturulmadı.');

            return;
        }
        if (Cache::has('subscription_packages:active')) {
            $this->recordFailure($flow, 'Yeni paket eklenince önbellek temizlenmedi.');
        }

        Cache::put('subscription_packages:active', 'ESKI-DEGER', 60);
        $controller->update(Request::create('/', 'POST', ['name' => $name, 'price' => 299]), $package);
        $package->refresh();
        if ((float) $package->price !== 299.0) {
            $this->recordFailure($flow, 'Paket güncellemesi kaydedilmedi.');
        }
        if (Cache::has('subscription_packages:active')) {
            $this->recordFailure($flow, 'Paket güncellenince önbellek temizlenmedi.');
        }

        $controller->destroy($package);
        if (SubscriptionPackage::find($package->id)) {
            $this->recordFailure($flow, 'Paket silindikten sonra hâlâ mevcut.');
        }
    }

    /**
     * 26 Agustos 2026: bu ayarlar (canli sohbet mesaji + haftalik calisma
     * saatleri) qatest- ile izole edilemeyen KURESEL/GERCEK ayarlardir -
     * test oncesi orijinal degerler yedeklenir, try/finally ile SONUNDA
     * KESIN olarak geri yuklenir ki gercek admin ayarlarina asla kalici
     * dokunulmasin (basarili/basarisiz farketmez).
     */
    private function checkChatSettingsUpdate(): void
    {
        $flow = 'Canlı sohbet çalışma saatleri';
        $controller = app(ChatSettingsController::class);

        $originalMessage = Setting::where('key', 'chat_offline_message')->value('value');
        $originalHour = ChatWorkingHour::where('weekday', 1)->first();
        $originalHourData = $originalHour ? $originalHour->only(['open_time', 'close_time', 'is_active']) : null;

        try {
            $message = 'QATEST çevrimdışı mesajı '.now()->timestamp;
            $controller->update(Request::create('/', 'POST', [
                'offline_message' => $message,
                'days' => [1 => ['is_active' => '1', 'open_time' => '09:00', 'close_time' => '18:00']],
            ]));

            if (Setting::get('chat_offline_message') !== $message) {
                $this->recordFailure($flow, 'Çevrimdışı mesajı kaydedilmedi.');
            }
            $hour = ChatWorkingHour::where('weekday', 1)->first();
            if (! $hour || ! str_starts_with((string) $hour->open_time, '09:00')) {
                $this->recordFailure($flow, 'Çalışma saati doğru kaydedilmedi.');
            }

            $rejected = false;
            try {
                $controller->update(Request::create('/', 'POST', [
                    'offline_message' => $message,
                    'days' => [1 => ['is_active' => '1', 'open_time' => '18:00', 'close_time' => '09:00']],
                ]));
            } catch (ValidationException) {
                $rejected = true;
            }
            if (! $rejected) {
                $this->recordFailure($flow, 'Kapanış saati açılıştan önce olan geçersiz bir aralık kabul edildi.');
            }
        } finally {
            if ($originalMessage !== null) {
                Setting::set('chat_offline_message', $originalMessage);
            } else {
                Setting::where('key', 'chat_offline_message')->delete();
                Cache::forget('setting:chat_offline_message');
            }
            if ($originalHourData) {
                ChatWorkingHour::where('weekday', 1)->update($originalHourData);
            } else {
                ChatWorkingHour::where('weekday', 1)->delete();
            }
        }
    }

    private function checkFacilityQuestionDestroy(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Aile sorusu silme (moderasyon)';
        $facility = $this->freshUnclaimedFacility('question-destroy');
        $question = FacilityQuestion::create([
            'facility_id' => $facility->id, 'brand' => 'bakimevleri', 'asker_name' => 'QATEST',
            'question' => 'QATEST admin check sorusu', 'status' => 'pending',
        ]);

        app(FacilityQuestionController::class)->destroy($question);

        if (FacilityQuestion::find($question->id)) {
            $this->recordFailure($flow, 'Soru silindikten sonra hâlâ mevcut.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }

    private function checkPlatformErrorResolveAndDestroy(): void
    {
        $flow = 'Hata kaydı çözüldü işaretleme/silme';
        $error = PlatformError::create([
            'source' => 'qatest_admin_check', 'title' => 'QATEST admin check hatası',
            'message' => 'Bu kayıt otomatik admin panel kontrolü tarafından oluşturulmuştur.',
        ]);
        $controller = app(PlatformErrorController::class);

        $controller->resolve($error);
        $error->refresh();
        if (! $error->resolved_at) {
            $this->recordFailure($flow, 'Hata "çözüldü" olarak işaretlenmedi.');
        }

        $controller->destroy($error);
        if (PlatformError::find($error->id)) {
            $this->recordFailure($flow, 'Hata kaydı silindikten sonra hâlâ mevcut.');
        }
    }

    private function checkWhatsappClickDestroy(): void
    {
        $flow = 'WhatsApp tıklama kaydı silme';
        $click = WhatsappClick::create(['brand' => 'bakimevleri', 'page_url' => 'https://bakimevleri.com/qatest-admin-check']);

        app(WhatsappClickController::class)->destroy($click);

        if (WhatsappClick::find($click->id)) {
            $this->recordFailure($flow, 'Kayıt silindikten sonra hâlâ mevcut.');
        }
    }

    private function checkFacilityInvitationStatusUpdate(): void
    {
        $failuresBefore = count($this->failures);
        $flow = 'Kurum davet durumu güncelleme';
        $facility = $this->freshUnclaimedFacility('invitation-status');
        $facility->update(['invitation_status' => 'not_started', 'ownership_type' => 'ozel']);
        $controller = app(FacilityInvitationController::class);

        $controller->updateStatus(Request::create('/', 'POST', ['status' => 'do_not_contact']), $facility);
        $facility->refresh();
        if ($facility->invitation_status !== 'do_not_contact' || ! $facility->invitation_status_at) {
            $this->recordFailure($flow, 'Davet durumu doğru güncellenmedi.');
        }

        $rejected = false;
        try {
            $controller->updateStatus(Request::create('/', 'POST', ['status' => 'gecersiz-bir-durum']), $facility);
        } catch (ValidationException) {
            $rejected = true;
        }
        if (! $rejected) {
            $this->recordFailure($flow, 'Geçersiz bir davet durumu kabul edildi.');
        }

        if (count($this->failures) === $failuresBefore) {
            $this->cleanupFacility($facility);
        }
    }
}
