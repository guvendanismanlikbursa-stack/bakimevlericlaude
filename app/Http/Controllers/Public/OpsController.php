<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\FacilityArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

// Deploy script'inin migrate + cache yenileme gibi birkac SABIT komutu
// uzaktan tetikleyebilmesi icin - CronRunnerController'daki paylasilan-
// sifre deseninin birebir ayni. SADECE asagidaki sabit listedeki eylemler
// calisir (keyfi shell komutu YOK). Bugune kadar her deploy'da public/'e
// kimliksiz, gecici bir PHP script atip is bitince silme deseninin
// yerini alir - o script orada durdugu saniyeler bile yetkisiz erisime
// acik bir pencereydi, bu uc kalici ve token korumali.
class OpsController extends Controller
{
    private const ACTIONS = ['migrate', 'seed', 'storage-link', 'create-admin', 'package-discover', 'cache-refresh', 'log-tail', 'sentry-test', 'queue-status', 'queue-work', 'queue-test', 'diagnostics-image', 'backup-now', 'geo-status', 'geo-missing-list', 'geo-apply', 'legal-page-set', 'geo-fill-city-centroid', 'python-check', 'category-audit', 'category-audit-city', 'invitation-status-audit', 'invitation-status-fix', 'invitation-detail', 'phone-type-audit', 'phone-type-fix', 'ownership-audit', 'ownership-fix', 'miscategory-scan', 'miscategory-fix', 'facility-remove', 'district-audit', 'district-fix', 'ownership-verify', 'facility-remove-by-ownership', 'ownership-fix-bulk', 'mail-render-test', 'qa-pick-facilities', 'qa-check', 'qa-setup', 'qa-setup-unclaimed', 'qa-password-reset-link', 'qa-registration-edit-link', 'qa-push-fix-subscription', 'qa-facility-set-known-password', 'qa-admin-push-diagnostic', 'qa-admin-push-test', 'fix-push-encoding', 'qa-staging-htpasswd-add', 'qa-staging-htpasswd-remove', 'qa-teardown', 'qa-verify-family-email', 'qa-debug-quote', 'qa-approve-claim', 'qa-cleanup-claim', 'qa-reject-claim', 'qa-reset-invitation-status', 'qa-approve-topup', 'qa-reject-topup', 'facility-user-unclaimed-audit', 'facility-user-unclaimed-fix', 'facility-set-city', 'php-upload-limits', 'queue-failed-detail', 'registration-revert-to-pending', 'registration-detail', 'document-diagnostic', 'admin-panel-smoke-test', 'qa-approve-registration', 'queue-flush-failed', 'gallery-health-scan', 'gallery-prune-broken', 'demo-images-cleanup', 'gallery-check-health', 'check-user-flows', 'cleanup-stale-qa-debris', 'test-platform-error', 'cleanup-test-platform-errors', 'name-cleanup-audit', 'name-cleanup-fix', 'facility-lookup', 'facility-borrow-demo-images', 'facility-borrow-demo-images-bulk', 'invite-review-families', 'snapshot-facility-stats', 'menu-image-demo-apply', 'restore-accidentally-deleted-claimed-facility-demo-images', 'sessions-gc', 'menu-image-repair', 'bursa-visit-export', 'mysql-tmp-diagnostics', 'qa-instant-claim-test', 'qa-verify-balance-brand-fixes', 'check-admin-flows', 'platform-errors-list', 'ffmpeg-check', 'ffmpeg-install', 'ffmpeg-x264-diagnose', 'admin-facility-edit-render', 'admin-facility-update-simulate', 'facilities-with-video-list', 'facility-trash-check', 'facility-find-and-restore', 'facility-clear-video-only', 'qa-delete-video-test', 'qa-facility-impersonate-test', 'site-visits-diagnose', 'broker-claimed-overlap-check', 'qa-admin-review-add-test', 'services-column-diagnose', 'recent-activity-scan', 'snapshot-category-views', 'admin-dashboard-render', 'qa-video-upload-test', 'qa-facility-panel-video-test', 'disk-usage', 'vacancy-set-default-available', 'category-demand-stats', 'seed-bursa-kres-rehberi', 'seed-bursa-bakimevi-rehberi', 'seed-bursa-rehabilitasyon-rehberi', 'geocode-missing-now'];

    // 28 Temmuz 2026: KVKK denetiminde metin guncellemesi icin sadece bu
    // 3 statik hukuk sayfasina yazma izni verilir - baska bir slug asla
    // kabul edilmez (ör. anasayfa/kurum sayfalari bu uctan hic etkilenmez).
    private const LEGAL_SLUGS = ['kvkk', 'gizlilik-politikasi', 'cerez-politikasi'];

    public function run(Request $request, string $action): Response
    {
        $secret = (string) config('platform.ops_secret');
        $provided = (string) str($request->header('Authorization', ''))->after('Bearer ');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            abort(403);
        }

        if (! in_array($action, self::ACTIONS, true)) {
            abort(404);
        }

        $output = match ($action) {
            'migrate' => $this->migrate(),
            'seed' => $this->seed(),
            'storage-link' => $this->storageLink(),
            'create-admin' => $this->createAdmin($request),
            'diagnostics-image' => $this->diagnosticsImage(),
            'backup-now' => $this->backupNow(),
            'package-discover' => $this->packageDiscover(),
            'cache-refresh' => $this->cacheRefresh(),
            'log-tail' => $this->logTail((int) $request->query('bytes', 8000)),
            'sentry-test' => $this->sentryTest(),
            'queue-status' => $this->queueStatus(),
            'queue-work' => $this->queueWork(),
            'queue-test' => $this->queueTest(),
            'geo-status' => $this->geoStatus(),
            'geo-missing-list' => $this->geoMissingList($request),
            'geo-apply' => $this->geoApply($request),
            'geocode-missing-now' => $this->geocodeMissingNow($request),
            'legal-page-set' => $this->legalPageSet($request),
            'geo-fill-city-centroid' => $this->geoFillCityCentroid(),
            'python-check' => $this->pythonCheck(),
            'ffmpeg-check' => $this->ffmpegCheck(),
            'ffmpeg-install' => $this->ffmpegInstall(),
            'ffmpeg-x264-diagnose' => $this->ffmpegX264Diagnose(),
            'admin-facility-edit-render' => $this->adminFacilityEditRender($request),
            'admin-facility-update-simulate' => $this->adminFacilityUpdateSimulate($request),
            'facilities-with-video-list' => $this->facilitiesWithVideoList(),
            'facility-trash-check' => $this->facilityTrashCheck($request),
            'facility-find-and-restore' => $this->facilityFindAndRestore($request),
            'facility-clear-video-only' => $this->facilityClearVideoOnly($request),
            'qa-delete-video-test' => $this->qaDeleteVideoTest(),
            'qa-facility-impersonate-test' => $this->qaFacilityImpersonateTest(),
            'site-visits-diagnose' => $this->siteVisitsDiagnose(),
            'broker-claimed-overlap-check' => $this->brokerClaimedOverlapCheck(),
            'qa-admin-review-add-test' => $this->qaAdminReviewAddTest(),
            'services-column-diagnose' => $this->servicesColumnDiagnose($request),
            'recent-activity-scan' => $this->recentActivityScan($request),
            'qa-video-upload-test' => $this->qaVideoUploadTest($request),
            'disk-usage' => $this->diskUsage(),
            'qa-facility-panel-video-test' => $this->qaFacilityPanelVideoTest(),
            'category-audit' => $this->categoryAudit(),
            'category-audit-city' => $this->categoryAuditCity($request),
            'invitation-status-audit' => $this->invitationStatusAudit(),
            'invitation-status-fix' => $this->invitationStatusFix(),
            'invitation-detail' => $this->invitationDetail($request),
            'phone-type-audit' => $this->phoneTypeAudit($request),
            'phone-type-fix' => $this->phoneTypeFix($request),
            'ownership-audit' => $this->ownershipAudit(),
            'ownership-fix' => $this->ownershipFix($request),
            'miscategory-scan' => $this->miscategoryScan($request),
            'miscategory-fix' => $this->miscategoryFix($request),
            'facility-remove' => $this->facilityRemove($request),
            'district-audit' => $this->districtAudit($request),
            'district-fix' => $this->districtFix($request),
            'name-cleanup-audit' => $this->nameCleanupAudit(),
            'name-cleanup-fix' => $this->nameCleanupFix(),
            'facility-lookup' => $this->facilityLookup($request),
            'facility-borrow-demo-images' => $this->facilityBorrowDemoImages($request),
            'facility-borrow-demo-images-bulk' => $this->facilityBorrowDemoImagesBulk($request),
            'ownership-verify' => $this->ownershipVerify($request),
            'ownership-fix-bulk' => $this->ownershipFixBulk($request),
            'facility-remove-by-ownership' => $this->facilityRemoveByOwnership($request),
            'mail-render-test' => $this->mailRenderTest($request),
            'qa-pick-facilities' => $this->qaPickFacilities($request),
            'qa-check' => $this->qaCheck($request),
            'qa-setup' => $this->qaSetup($request),
            'qa-setup-unclaimed' => $this->qaSetupUnclaimed(),
            'qa-instant-claim-test' => $this->qaInstantClaimTest($request),
            'qa-verify-balance-brand-fixes' => $this->qaVerifyBalanceBrandFixes(),
            'qa-password-reset-link' => $this->qaPasswordResetLink($request),
            'qa-registration-edit-link' => $this->qaRegistrationEditLink($request),
            'qa-push-fix-subscription' => $this->qaPushFixSubscription($request),
            'qa-facility-set-known-password' => $this->qaFacilitySetKnownPassword($request),
            'qa-admin-push-diagnostic' => $this->qaAdminPushDiagnostic(),
            'qa-admin-push-test' => $this->qaAdminPushTest(),
            'fix-push-encoding' => $this->fixPushEncoding(),
            'qa-staging-htpasswd-add' => $this->qaStagingHtpasswdAdd(),
            'qa-staging-htpasswd-remove' => $this->qaStagingHtpasswdRemove(),
            'qa-teardown' => $this->qaTeardown($request),
            'qa-verify-family-email' => $this->qaVerifyFamilyEmail($request),
            'qa-debug-quote' => $this->qaDebugQuote($request),
            'qa-approve-claim' => $this->qaApproveClaim($request),
            'qa-cleanup-claim' => $this->qaCleanupClaim($request),
            'qa-reject-claim' => $this->qaRejectClaim($request),
            'qa-reset-invitation-status' => $this->qaResetInvitationStatus($request),
            'qa-approve-topup' => $this->qaApproveTopup($request),
            'qa-reject-topup' => $this->qaRejectTopup($request),
            'facility-user-unclaimed-audit' => $this->facilityUserUnclaimedAudit(),
            'facility-user-unclaimed-fix' => $this->facilityUserUnclaimedFix(),
            'facility-set-city' => $this->facilitySetCity($request),
            'php-upload-limits' => $this->phpUploadLimits(),
            'queue-failed-detail' => $this->queueFailedDetail(),
            'registration-revert-to-pending' => $this->registrationRevertToPending($request),
            'registration-detail' => $this->registrationDetail($request),
            'document-diagnostic' => $this->documentDiagnostic($request),
            'admin-panel-smoke-test' => $this->adminPanelSmokeTest(),
            'qa-approve-registration' => $this->qaApproveRegistration($request),
            'queue-flush-failed' => $this->queueFlushFailed(),
            'gallery-health-scan' => $this->galleryHealthScan($request),
            'gallery-prune-broken' => $this->galleryPruneBroken($request),
            'demo-images-cleanup' => $this->demoImagesCleanup($request),
            'gallery-check-health' => $this->galleryCheckHealth($request),
            'check-user-flows' => $this->checkUserFlows(),
            'check-admin-flows' => $this->checkAdminFlows(),
            'cleanup-stale-qa-debris' => $this->cleanupStaleQaDebris($request),
            'invite-review-families' => $this->inviteReviewFamilies(),
            'snapshot-facility-stats' => $this->snapshotFacilityStats(),
            'snapshot-category-views' => $this->snapshotCategoryViews(),
            'admin-dashboard-render' => $this->adminDashboardRender($request),
            'platform-errors-list' => $this->platformErrorsList(),
            'test-platform-error' => $this->testPlatformError(),
            'cleanup-test-platform-errors' => $this->cleanupTestPlatformErrors(),
            'menu-image-demo-apply' => $this->menuImageDemoApply($request),
            'restore-accidentally-deleted-claimed-facility-demo-images' => $this->restoreAccidentallyDeletedClaimedFacilityDemoImages(),
            'sessions-gc' => $this->sessionsGc($request),
            'menu-image-repair' => $this->menuImageRepair($request),
            'bursa-visit-export' => $this->bursaVisitExport($request),
            'mysql-tmp-diagnostics' => $this->mysqlTmpDiagnostics(),
            'vacancy-set-default-available' => $this->vacancySetDefaultAvailable(),
            'category-demand-stats' => $this->categoryDemandStats(),
            'seed-bursa-kres-rehberi' => $this->seedBursaKresRehberi(),
            'seed-bursa-bakimevi-rehberi' => $this->seedBursaBakimeviRehberi(),
            'seed-bursa-rehabilitasyon-rehberi' => $this->seedBursaRehabilitasyonRehberi(),
        };

        return response($output, 200)->header('Content-Type', 'text/plain');
    }

    private function migrate(): string
    {
        Artisan::call('migrate', ['--force' => true]);

        return Artisan::output();
    }

    // 11 Agustos 2026: gallery:check-health scheduled komutunu (dailyAt
    // 09:00) beklemeden elle tetiklemek icin - hem yeni "Hatalar" admin
    // ekranini hem admin mailini uctan uca dogrulamak amaciyla.
    // 11 Agustos 2026: yeni "Hatalar" admin ekrani + admin mail bildirimi
    // (record_platform_error(), bkz. app/helpers.php) mekanizmasini gercek
    // kod yolundan uctan uca test etmek icin - test kaydini olusturur, DB
    // kayit id'sini doner (sonra qa-teardown benzeri elle silinebilir).
    private function cleanupTestPlatformErrors(): string
    {
        $count = DB::table('platform_errors')->where('source', 'ops-test')->delete();

        return "Silindi: {$count} test hata kaydi.";
    }

    // 26 Agustos 2026: kullanicinin "admin panelinde 2 hata gorunuyor" bildirimi
    // icin - /admin/hatalar ekranindaki cozulmemis kayitlari uzaktan, admin
    // oturumu gerekmeden okumak icin salt-okunur bir teshis ucu.
    private function platformErrorsList(): string
    {
        $errors = \App\Models\PlatformError::whereNull('resolved_at')->latest()->limit(20)->get();

        if ($errors->isEmpty()) {
            return 'Cozulmemis hata kaydi yok.';
        }

        $out = "Cozulmemis hata sayisi: {$errors->count()}\n\n";
        foreach ($errors as $e) {
            $out .= "#{$e->id} [{$e->source}] {$e->title}\n"
                . "Olusturulma: {$e->created_at}\n"
                . "Mesaj: {$e->message}\n"
                . "Context: " . json_encode($e->context) . "\n\n";
        }

        return $out;
    }

    private function testPlatformError(): string
    {
        record_platform_error(
            'ops-test',
            'QATEST teshis kaydi - Hatalar ekrani/mail dogrulamasi',
            "Bu, 'Hatalar' admin ekraninin ve admin mail bildiriminin gercekten calistigini dogrulamak icin olusturulmus bir test kaydidir. Gormüşseniz sistem calisiyor demektir, bu kaydi silebilirsiniz.",
            ['test' => true, 'created_at' => now()->toDateTimeString()]
        );

        $id = \App\Models\PlatformError::latest()->value('id');

        return "OK: platform_errors #{$id} olusturuldu, admin(ler)e mail gonderildi.";
    }

    private function galleryCheckHealth(Request $request): string
    {
        $hours = (int) $request->query('hours', 48);
        Artisan::call('gallery:check-health', ['--hours' => $hours]);

        return Artisan::output();
    }

    // 12 Agustos 2026: gunluk otomatik kullanici-akisi kontrolunu (bkz.
    // App\Console\Commands\CheckUserFlows) zamanlamayi beklemeden elle
    // tetiklemek icin - komutun kendi konsol ciktisini (hangi marka/akis
    // test edildi, hata bulundu mu) dogrudan gosterir.
    private function checkUserFlows(): string
    {
        Artisan::call('platform:check-user-flows');

        return Artisan::output();
    }

    // 26 Agustos 2026: bkz. App\Console\Commands\CheckAdminFlows ayni tarihli
    // yorum - check-user-flows ile birebir ayni desen, admin paneli icin.
    /**
     * 3 Eylul 2026: kullanicinin bildirdigi "Kaydet butonuna basilmiyor"
     * hatasi - hem masaustu hem mobilde. Tahmin etmek yerine GERCEK canli
     * admin oturumuyla GERCEK sayfayi (Kernel::handle) render edip, Kaydet
     * butonu ve etrafindaki ham HTML'i dogrudan gosterir - byte byte neyin
     * GERCEKTEN sunucuda oldugunu kanitlar.
     */
    private function adminFacilityEditRender(Request $incomingRequest): string
    {
        $facilityId = (int) $incomingRequest->query('facility_id', 0);
        $facility = $facilityId ? Facility::find($facilityId) : Facility::query()->latest()->first();
        if (! $facility) {
            return 'HATA: kurum bulunamadi.';
        }

        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok.';
        }

        // 3 Eylul 2026: ilk denemede $editRequest->getSession()->put(...)
        // kullanildi - bu Laravel'in degil Symfony'nin session arayuzu,
        // put() metodu yok, "Call to undefined method" ile 500 verdi.
        // Sonra Kernel::handle() ile ic ice (nested) istek denendi - bu da
        // session/middleware acisindan guvenilmez. qaVideoUploadTest() ile
        // AYNI, zaten kanitlanmis, en guvenli desene gecildi: controller
        // metodu DOGRUDAN cagrilir (middleware/routing/session katmanlari
        // tamamen atlanir, session() helper'i ayni PHP sureci icinde zaten
        // gecerlidir).
        session(['admin_id' => $admin->id]);
        $facility->load(['images', 'facilityUsers', 'claims' => fn ($q) => $q->latest(), 'balanceLogs', 'category', 'roomTypes', 'ageGroups', 'programTypes']);

        $controller = app(\App\Http\Controllers\Admin\FacilityController::class);
        $view = $controller->edit(Request::create('/admin/kurumlar/'.$facility->id.'/edit', 'GET'), $facility);
        $html = $view instanceof \Illuminate\Contracts\View\View ? $view->render() : (string) $view;

        $out = "facility_id: {$facility->id}\n";
        $out .= 'HTML uzunlugu: '.strlen($html)." bayt\n\n";

        // 4 Eylul 2026: genel amacli ad-hoc arama - ?q= verilirse o metnin
        // etrafindaki 600 karakteri gosterir (yeni eklenen alanlari dogrulamak icin).
        if ($q = $incomingRequest->query('q')) {
            $qPos = mb_strpos($html, $q);
            if ($qPos === false) {
                $out .= "'{$q}' sayfada HIC bulunamadi.\n\n";
            } else {
                $qStart = max(0, $qPos - 200);
                $out .= "'{$q}' etrafindaki ham HTML:\n---\n".mb_substr($html, $qStart, 600)."\n---\n\n";
            }
        }

        // "Kaydet" butonunu ve etrafindaki 400 karakteri (once/sonra) goster.
        $pos = mb_strpos($html, '>Kaydet<');
        if ($pos === false) {
            $out .= "'Kaydet' metni sayfada HIC bulunamadi.\n";
        } else {
            $start = max(0, $pos - 400);
            $out .= "Kaydet butonu etrafindaki ham HTML:\n---\n".mb_substr($html, $start, 800)."\n---\n\n";
        }

        // 3 Eylul 2026: kullanicinin bildirdigi kritik hata - "Videoyu Sil"
        // butonu kurumun TAMAMINI siliyor. Bu formun GERCEK action adresini
        // dogrudan gosterir.
        $videoDeletePos = mb_strpos($html, 'Videoyu Sil');
        if ($videoDeletePos === false) {
            $out .= "'Videoyu Sil' metni sayfada bulunamadi.\n";
        } else {
            $start = max(0, $videoDeletePos - 500);
            $out .= "'Videoyu Sil' etrafindaki ham HTML:\n---\n".mb_substr($html, $start, 600)."\n---\n\n";
        }

        $idPos = mb_strpos($html, 'id="video-delete-form"');
        $mainFormPos = mb_strpos($html, 'enctype="multipart/form-data"');
        $out .= "video-delete-form konumu: ".($idPos === false ? 'YOK' : $idPos)."\n";
        $out .= "ana edit form konumu: ".($mainFormPos === false ? 'YOK' : $mainFormPos)."\n";
        $out .= (($idPos !== false && $mainFormPos !== false && $idPos < $mainFormPos) ? "DOGRU: video-delete-form ana formdan ONCE (kardes, ic ice degil).\n" : "DIKKAT: sira beklenenden farkli.\n");

        // </form> ile ana formun gercekten dogru kapandigini dogrula.
        $formOpenCount = substr_count($html, '<form');
        $formCloseCount = substr_count($html, '</form>');
        $out .= "Toplam <form acilis: {$formOpenCount}, </form> kapanis: {$formCloseCount}\n";

        return $out;
    }

    /**
     * 3 Eylul 2026: "Kaydet butonuna basilmiyor" hatasi - z-index/CSS/JS
     * taraflari tek tek elendi. Son ihtimal: buton aslinda TIKLANIYOR,
     * form GONDERILIYOR ama BU KURUMUN mevcut verisinde bir dogrulama
     * hatasi (ör. price_max < price_min) VAR ve sayfa sessizce hatayla
     * geri donuyor - kullanici sayfanin altindaysa (hata mesaji ustte
     * cikar) hicbir sey degismemis gibi goruyor. Bu, GERCEK guncelleme
     * ucuna, kurumun KENDI mevcut degerleriyle (formun dolduracagi AYNI
     * degerler) gercek bir istek gonderip sonucu (redirect mi, 422 mi,
     * hangi dogrulama hatasi) dogrudan gosterir.
     */
    private function adminFacilityUpdateSimulate(Request $incomingRequest): string
    {
        $facilityId = (int) $incomingRequest->query('facility_id', 0);
        $facility = $facilityId ? Facility::find($facilityId) : null;
        if (! $facility) {
            return 'HATA: kurum bulunamadi (facility_id parametresi gerekli).';
        }

        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok.';
        }
        session(['admin_id' => $admin->id]);

        $payload = [
            'name' => $facility->name,
            'city_id' => $facility->city_id,
            'facility_category_id' => $facility->facility_category_id,
            'district' => $facility->district,
            'address' => $facility->address,
            'phone' => $facility->phone,
            'description' => trim((string) $facility->description).' [ops-test-'.now()->format('His').']',
            'capacity' => $facility->capacity,
            'price_min' => $facility->price_min,
            'price_max' => $facility->price_max,
            'is_published' => $facility->is_published ? '1' : '0',
            'is_featured' => $facility->is_featured ? '1' : '0',
            'allows_visit_service' => $facility->allows_visit_service ? '1' : '0',
        ];

        $out = "facility_id: {$facility->id}\n";
        $out .= 'Gonderilen aciklama: '.$payload['description']."\n";
        $out .= 'Mevcut price_min: '.var_export($facility->price_min, true).', price_max: '.var_export($facility->price_max, true)."\n\n";

        $updateRequest = Request::create('/admin/kurumlar/'.$facility->id, 'PUT', $payload);
        $updateRequest->headers->set('Accept', 'text/html');
        app()->instance('request', $updateRequest);

        $controller = app(\App\Http\Controllers\Admin\FacilityController::class);

        try {
            $response = $controller->update($updateRequest, $facility, app(\App\Services\GeocodingService::class));
            $out .= 'Controller yaniti sinifi: '.get_class($response)."\n";
            if (method_exists($response, 'getTargetUrl')) {
                $out .= 'Redirect hedefi: '.$response->getTargetUrl()."\n";
            }
            if (method_exists($response, 'getSession')) {
                $out .= 'Session flash "success": '.var_export(session('success'), true)."\n";
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $out .= "DOGRULAMA HATASI firladi:\n";
            foreach ($e->errors() as $field => $messages) {
                $out .= "  - {$field}: ".implode(', ', $messages)."\n";
            }

            return $out;
        } catch (\Throwable $e) {
            $out .= 'BEKLENMEYEN HATA: '.get_class($e).': '.$e->getMessage()."\n";
            $out .= $e->getFile().':'.$e->getLine()."\n";

            return $out;
        }

        $facility->refresh();
        $out .= "\nGuncelleme SONRASI aciklama (veritabaninda): ".$facility->description."\n";
        $out .= ($facility->description === $payload['description']) ? "SONUC: BASARILI - aciklama gercekten guncellendi.\n" : "SONUC: BASARISIZ - aciklama veritabaninda degismedi.\n";

        return $out;
    }

    /**
     * 3 Eylul 2026: kullanicinin belirleyici bulgusu - "Kaydet butonu
     * SADECE bu kurumun revize panelinde calismiyor, diger kurum
     * panellerinde calisiyor". Videosu olan BASKA kurum var mi kontrol
     * eder - varsa orada da ayni sorun mu diye karsilastirma yapilabilir.
     */
    private function facilitiesWithVideoList(): string
    {
        $rows = Facility::whereNotNull('video_path')->select('id', 'name', 'is_broker_managed', 'is_claimed')->get();
        if ($rows->isEmpty()) {
            return 'Videosu olan hicbir kurum yok.';
        }

        return $rows->map(fn ($f) => "#{$f->id} {$f->name} (broker_managed=".($f->is_broker_managed ? '1' : '0').', claimed='.($f->is_claimed ? '1' : '0').')')->join("\n");
    }

    /**
     * 3 Eylul 2026: kullanicinin bildirdigi gercek gelisme - facility_id=6713
     * artik "kurum bulunamadi" donuyor, canli sayfa 404 veriyor. Bu kurum
     * SILINMIS mi (yumusak/soft-delete, cop kutusunda) yoksa GERCEKTEN
     * kalici mi silinmis (force delete) kontrol eder.
     */
    /**
     * 4 Eylul 2026: kullanicinin "dashboard 30 gunluk trend verisini
     * cekmiyor" bildirimi icin - site_visits tablosunun GERCEK durumunu
     * (kac satir, en son hangi tarih, hangi brand degerleri kullaniliyor)
     * dogrudan gosterir.
     */
    private function siteVisitsDiagnose(): string
    {
        $total = DB::table('site_visits')->count();
        $last30 = DB::table('site_visits')->where('visit_date', '>=', now()->subDays(29)->toDateString())->count();
        $latest = DB::table('site_visits')->orderByDesc('visit_date')->first();
        $oldest = DB::table('site_visits')->orderBy('visit_date')->first();
        $brands = DB::table('site_visits')->select('brand')->distinct()->pluck('brand');
        $configuredBrands = array_keys(config('brands.brands'));
        $sumsByBrand = DB::table('site_visits')->selectRaw('brand, count(*) as satir_sayisi, sum(count) as toplam')->groupBy('brand')->get();

        $out = "Toplam site_visits satiri: {$total}\n";
        $out .= "Son 30 gun icindeki satir sayisi: {$last30}\n";
        $out .= 'En yeni visit_date: '.($latest->visit_date ?? 'YOK')."\n";
        $out .= 'En eski visit_date: '.($oldest->visit_date ?? 'YOK')."\n";
        $out .= 'Simdiki tarih (sunucu): '.now()->toDateString()."\n\n";
        $out .= 'Tablodaki brand degerleri: '.$brands->implode(', ')."\n";
        $out .= 'config(brands.brands) anahtarlari: '.implode(', ', $configuredBrands)."\n\n";
        foreach ($sumsByBrand as $row) {
            $out .= "  brand={$row->brand}: {$row->satir_sayisi} satir, toplam {$row->toplam} ziyaret\n";
        }

        return $out;
    }

    /**
     * 4 Eylul 2026: kullanicinin "Anlaşmalı rozeti/sıralaması hiç fark
     * etmiyor" bildirimi icin - form.blade.php, facility-card.blade.php ve
     * show.blade.php'nin HEPSI @if(is_claimed) ... @elseif(is_broker_managed)
     * sirasini kullaniyor. Eger cok sayida kurum HEM is_claimed HEM
     * is_broker_managed ise, bu kurumlar HER YERDE "Onaylı" gorunur,
     * "Anlaşmalı" rozeti/oncelik SIRALAMASI (badge gorunse bile is_claimed
     * kurumlar zaten rating'e gore ustte olabilir) pratikte hic
     * gorunmuyor olabilir - GERCEK sebep bu mu, veriyle dogrular.
     */
    private function brokerClaimedOverlapCheck(): string
    {
        $totalBroker = Facility::where('is_broker_managed', true)->count();
        $brokerAndClaimed = Facility::where('is_broker_managed', true)->where('is_claimed', true)->count();
        $brokerOnlyNotClaimed = Facility::where('is_broker_managed', true)->where('is_claimed', false)->count();

        $out = "Toplam is_broker_managed=true kurum: {$totalBroker}\n";
        $out .= "  - Bunlarin is_claimed=true OLANI (rozet hep 'Onaylı' gösterir, 'Anlaşmalı' hiç görünmez): {$brokerAndClaimed}\n";
        $out .= "  - Bunlarin is_claimed=false OLANI (rozet gerçekten 'Anlaşmalı' gösterir): {$brokerOnlyNotClaimed}\n\n";

        $sample = Facility::where('is_broker_managed', true)->where('is_claimed', true)
            ->select('id', 'name', 'is_featured')->limit(10)->get();
        if ($sample->isNotEmpty()) {
            $out .= "Ornek (hem anlasmali HEM sahiplenilmis, rozet 'Onaylı' gösteriyor):\n";
            foreach ($sample as $f) {
                $out .= "  #{$f->id} {$f->name} (is_featured=".($f->is_featured ? '1' : '0').")\n";
            }
        }

        return $out;
    }

    /**
     * 4 Eylul 2026: Admin\FacilityReviewController::store()'un KANITI -
     * sahte bir anlaşmalı test kurumuna gercek controller uzerinden bir
     * yorum ekler, approvedReviews() iliskisinde dogru gorunup gorunmedigini
     * kontrol eder, sonra hem yorumu hem test kurumunu temizler.
     */
    private function qaAdminReviewAddTest(): string
    {
        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok.';
        }
        session(['admin_id' => $admin->id]);

        $facility = Facility::create([
            'name' => 'qatest-review-add-'.now()->format('His'),
            'slug' => 'qatest-review-add-'.now()->format('His'),
            'brand' => 'bakimevleri',
            'city_id' => Facility::query()->value('city_id'),
            'facility_category_id' => Facility::query()->value('facility_category_id'),
            'is_broker_managed' => true,
            'is_published' => true,
        ]);

        $out = "Test kurumu olusturuldu: #{$facility->id}\n";

        $reviewRequest = Request::create('/admin/kurumlar/'.$facility->id.'/yorum-ekle', 'POST', [
            'reviewer_name' => 'QATEST Ailesi',
            'rating' => 5,
            'body' => 'Bu bir QA test yorumudur.',
        ]);

        try {
            $controller = app(\App\Http\Controllers\Admin\FacilityReviewController::class);
            $response = $controller->store($reviewRequest, $facility);
            $out .= 'store() calisti, yanit sinifi: '.get_class($response)."\n";
        } catch (\Throwable $e) {
            $out .= 'store() ISTISNA FIRLATTI: '.get_class($e).' - '.$e->getMessage()."\n";
        }

        $facility->refresh();
        $approved = $facility->approvedReviews;
        $out .= 'approvedReviews sayisi: '.$approved->count()."\n";
        foreach ($approved as $r) {
            $out .= "  #{$r->id} {$r->reviewer_name} - {$r->rating} yildiz - status={$r->status} - family_user_id=".var_export($r->family_user_id, true)."\n";
        }

        \App\Models\FacilityReview::where('facility_id', $facility->id)->delete();
        Facility::withTrashed()->where('id', $facility->id)->forceDelete();
        $out .= "\n(test kurumu #{$facility->id} ve yorumu temizlendi)";

        return $out;
    }

    /**
     * 5 Eylul 2026: kullanicinin "ozellik filtresi 0 sonuc donduruyor"
     * bildirimi icin - facilities.services kolonunun GERCEK icerigini
     * (whereJsonContains'in aradigi format ile ayni mi) dogrudan gosterir.
     */
    private function servicesColumnDiagnose(Request $incomingRequest): string
    {
        $service = (string) $incomingRequest->query('service', '7/24 hemşire');
        $scope = (string) $incomingRequest->query('scope', 'yasli-bakim');

        $sample = Facility::whereHas('category', fn ($q) => $q->where('brand_scope', $scope))
            ->whereNotNull('services')
            ->limit(10)
            ->get(['id', 'name', 'services']);

        $out = "Aranan servis degeri: '{$service}'\n\n";
        if ($sample->isEmpty()) {
            return $out."'{$scope}' kapsaminda services dolu HICBIR kurum yok.\n";
        }

        foreach ($sample as $f) {
            $out .= "#{$f->id} {$f->name}\n";
            $out .= '  services (ham): '.json_encode($f->services, JSON_UNESCAPED_UNICODE)."\n";
        }

        $matchCount = Facility::whereHas('category', fn ($q) => $q->where('brand_scope', $scope))
            ->whereJsonContains('services', $service)
            ->count();
        $out .= "\nwhereJsonContains('services', '{$service}') ile eslesen kurum sayisi: {$matchCount}\n";

        // 5 Eylul 2026: kok nedeni izole etmek icin - "/" veya turkce karakter
        // mi sorun cikariyor, ayri ayri test eder.
        $noSlash = Facility::whereHas('category', fn ($q) => $q->where('brand_scope', $scope))
            ->whereJsonContains('services', 'Doktor kontrolü')
            ->count();
        $out .= "whereJsonContains('services', 'Doktor kontrolü') (slash yok) eslesen: {$noSlash}\n";

        $rawSql = Facility::whereHas('category', fn ($q) => $q->where('brand_scope', $scope))
            ->whereJsonContains('services', $service)
            ->toSql();
        $out .= "Uretilen SQL: {$rawSql}\n";

        $rawResult = DB::select("select count(*) as c from facilities where JSON_CONTAINS(services, ?)", [json_encode($service, JSON_UNESCAPED_UNICODE)]);
        $out .= 'Dogrudan JSON_CONTAINS(services, '.json_encode($service, JSON_UNESCAPED_UNICODE).") sonucu: {$rawResult[0]->c}\n";

        // 5 Eylul 2026: tek bir SATIR uzerinde, join/scope'suz, en yalin test.
        $isolated = DB::select("select id, services, JSON_CONTAINS(services, '\"Doktor kontrolü\"') as sonuc, JSON_VALID(services) as gecerli_mi, JSON_TYPE(services) as tip from facilities where id = 2");
        $out .= "\nTEK SATIR testi (facility #2):\n".json_encode($isolated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";

        // 5 Eylul 2026: turkce karakter mi yoksa JSON_CONTAINS'in kendisi mi
        // sorunlu, ayirt etmek icin SALT-ASCII bir eleman uzerinde dener,
        // ayrica tablo/kolon collation'ini gosterir.
        $asciiTest = DB::select("select JSON_CONTAINS(JSON_ARRAY('a','b','c'), '\"b\"') as sonuc");
        $out .= "SALT-ASCII test (JSON_ARRAY('a','b','c') icinde 'b' var mi): ".$asciiTest[0]->sonuc."\n";

        $collationInfo = DB::select("select COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME, DATA_TYPE from information_schema.columns where table_schema = DATABASE() and table_name = 'facilities' and COLUMN_NAME = 'services'");
        $out .= 'services kolonu collation bilgisi: '.json_encode($collationInfo)."\n";

        $connCollation = DB::select("select @@collation_connection as c, @@character_set_connection as cs");
        $out .= 'Baglanti collation/charset: '.json_encode($connCollation)."\n";

        // 5 Eylul 2026: kok neden hipotezi - Unicode NORMALIZASYON formu
        // farki (NFC/NFD). Google Maps'ten gelen "Doktor kontrolü" ile
        // benim burada YAZDIGIM "Doktor kontrolü" GORSEL olarak ayni ama
        // byte duzeyinde farkli olabilir (ör. "ü" tek codepoint mi, yoksa
        // "u" + birlesen aksan mi). Iki tarafin hex dokumunu karsilastirir.
        $storedRow = DB::selectOne("select JSON_UNQUOTE(JSON_EXTRACT(services, '$[2]')) as val from facilities where id = 2");
        $storedRaw = $storedRow->val;
        $out .= "\nDB'deki 3. eleman (raw): ".$storedRaw."\n";
        $out .= 'DB hex: '.bin2hex($storedRaw)."\n";
        $myLiteral = 'Doktor kontrolü';
        $out .= 'Benim yazdigim: '.$myLiteral."\n";
        $out .= 'Benim hex: '.bin2hex($myLiteral)."\n";
        $out .= 'Byte-esit mi: '.($storedRaw === $myLiteral ? 'EVET' : 'HAYIR - FARKLI BYTE DIZISI')."\n";
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($storedRaw, \Normalizer::FORM_C);
            $out .= 'DB degeri NFC normalize edilince benimkiyle esit mi: '.($normalized === $myLiteral ? 'EVET' : 'HAYIR')."\n";
        } else {
            $out .= "PHP intl/Normalizer sinifi yuklu degil, normalizasyon testi yapilamadi.\n";
        }

        // 5 Eylul 2026: byte'lar birebir ayni cikinca (kanitlandi) - alternatif
        // JSON_SEARCH fonksiyonunun bu MariaDB'de dogru calisip calismadigini test eder.
        $jsonSearchCountDynamic = DB::selectOne("select count(*) as c from facilities where JSON_SEARCH(services, 'one', ?) is not null", [$service]);
        $out .= "\nJSON_SEARCH ile ARANAN '{$service}' degeri icin eslesen kurum sayisi: {$jsonSearchCountDynamic->c}\n";

        $matchingFacilities = DB::select("select f.id, f.name, f.is_published, f.deleted_at, fc.brand_scope from facilities f left join facility_categories fc on fc.id = f.facility_category_id where JSON_SEARCH(f.services, 'one', ?) is not null", [$service]);
        foreach ($matchingFacilities as $mf) {
            $out .= "  #{$mf->id} {$mf->name} | brand_scope={$mf->brand_scope} | is_published=".var_export((bool) $mf->is_published, true).' | silinmis='.var_export($mf->deleted_at !== null, true)."\n";
        }

        return $out;
    }

    /**
     * 5 Eylul 2026: kullanicinin "1 saat once mesaj geldi ama bulamiyorum"
     * bildirimi icin - platformdaki TUM mesaj/yorum/soru kanallarini
     * (iletisim mesaji, kurum yorumu, canli sohbet, aile sorusu, teklif
     * talebi mesaji, yakinimi ziyaret et talebi) tek ekranda, son N saat
     * icinde olusanlari, admin panelinde NEREDE goruntulenecegi bilgisiyle
     * birlikte listeler.
     */
    private function recentActivityScan(Request $incomingRequest): string
    {
        $hours = (int) $incomingRequest->query('hours', 3);
        $since = now()->subHours($hours);
        $out = "Son {$hours} saat icindeki aktivite (simdi: ".now()->toDateTimeString().", esik: {$since->toDateTimeString()}):\n\n";

        $sections = [
            'İletişim mesajları (Admin > Mesajlar)' => fn () => DB::table('contact_messages')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'name', 'subject', 'created_at']),
            'Kurum yorumları (Admin > Yorumlar)' => fn () => DB::table('facility_reviews')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'facility_id', 'reviewer_name', 'status', 'created_at']),
            'Canlı sohbet mesajları (Admin > Canlı Sohbet)' => fn () => DB::table('chat_messages')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'chat_thread_id', 'sender_type', 'created_at']),
            'Aile soruları (Admin > Aile Soruları)' => fn () => DB::table('facility_questions')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'facility_id', 'created_at']),
            'Teklif talebi mesajları (kurum/aile paneli - Admin > Teklif Talepleri > ilgili talebin mesajları)' => fn () => DB::table('messages')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'offer_request_id', 'sender_type', 'created_at']),
            '"Yakınımı Ziyaret Et" talepleri (Admin > Ziyaret Talepleri)' => fn () => DB::table('visit_service_requests')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'facility_id', 'status', 'created_at']),
            'Yeni teklif talepleri (Admin > Teklif Talepleri)' => fn () => DB::table('offer_requests')->where('created_at', '>=', $since)->orderByDesc('created_at')->get(['id', 'facility_id', 'status', 'created_at']),
        ];

        $foundAny = false;
        foreach ($sections as $label => $callback) {
            try {
                $rows = $callback();
            } catch (\Throwable $e) {
                $out .= "[{$label}] HATA: ".$e->getMessage()."\n\n";
                continue;
            }
            if ($rows->isEmpty()) {
                continue;
            }
            $foundAny = true;
            $out .= "=== {$label} ({$rows->count()} kayit) ===\n";
            foreach ($rows as $r) {
                $out .= '  '.json_encode($r, JSON_UNESCAPED_UNICODE)."\n";
            }
            $out .= "\n";
        }

        if (! $foundAny) {
            $out .= "Bu {$hours} saat icinde taranan hicbir kanalda yeni kayit bulunamadi.";
        }

        return $out;
    }

    private function facilityTrashCheck(Request $incomingRequest): string
    {
        $facilityId = (int) $incomingRequest->query('facility_id', 0);
        $trashed = Facility::withTrashed()->find($facilityId);
        if (! $trashed) {
            return "facility_id={$facilityId}: veritabaninda HIC YOK (ne aktif ne cop kutusunda) - kalici olarak silinmis.";
        }

        $out = "facility_id={$facilityId}: {$trashed->name}\n";
        $out .= 'deleted_at: '.($trashed->deleted_at ? $trashed->deleted_at->format('Y-m-d H:i:s') : '(silinmemis, aktif)')."\n";
        $out .= 'updated_at: '.$trashed->updated_at->format('Y-m-d H:i:s')."\n";
        $out .= 'video_path: '.($trashed->video_path ?? 'NULL')."\n";
        $out .= 'is_published: '.var_export($trashed->is_published, true)."\n";
        $out .= 'is_claimed: '.var_export($trashed->is_claimed, true)."\n";
        $out .= 'is_broker_managed: '.var_export($trashed->is_broker_managed, true)."\n";
        $out .= 'category brand_scope: '.($trashed->category?->brand_scope ?? 'YOK')."\n";

        return $out;
    }

    /**
     * 3 Eylul 2026: acil - isme gore kurum bulur, ?restore=1 verilirse
     * soft-delete'li (cop kutusundaki) kaydi ANINDA geri yukler. "Videoyu Sil"
     * hatasi yuzunden yayindan dusen bir kurumu ailelerin ziyaret ettigi
     * anda hizlica geri getirmek icin.
     */
    private function facilityFindAndRestore(Request $incomingRequest): string
    {
        $name = (string) $incomingRequest->query('name', '');
        if ($name === '') {
            return 'HATA: name parametresi gerekli.';
        }

        $rows = Facility::withTrashed()->where('name', 'like', '%'.$name.'%')->get();
        if ($rows->isEmpty()) {
            return "'{$name}' icin hicbir kurum bulunamadi.";
        }

        $restore = $incomingRequest->boolean('restore');
        $out = '';
        foreach ($rows as $f) {
            $wasDeleted = (bool) $f->deleted_at;
            if ($restore && $wasDeleted) {
                $f->deleted_at = null;
                $f->save();
            }
            $out .= "#{$f->id} {$f->name}\n"
                . '  deleted_at (once): '.($wasDeleted ? $f->deleted_at?->format('Y-m-d H:i:s') ?? 'onceden silinmisti, simdi GERI YUKLENDI' : '(silinmemis, aktif)')."\n"
                . '  video_path: '.($f->video_path ?? 'NULL')."\n"
                . '  updated_at: '.$f->updated_at->format('Y-m-d H:i:s')."\n\n";
        }

        return $out;
    }

    /**
     * 3 Eylul 2026: acil - "Videoyu Sil" butonunun (deleteVideo()) neden
     * kurumu sildigi henuz kanitlanmadan, GERCEK bir kuruma o butona tekrar
     * bastirmadan videosunu kaldirmak icin. Sadece video_path/video_updated_at
     * temizler ve dosyayi diskten siler - deleteVideo()'nun geri kalanini
     * (sync_video_delete_from_canonical_domain) BILEREK cagirmaz, ta ki
     * o cagrinin kendisinin sorunla ilgisi olup olmadigi anlasilana kadar.
     */
    private function facilityClearVideoOnly(Request $incomingRequest): string
    {
        $facilityId = (int) $incomingRequest->query('facility_id', 0);
        $facility = Facility::find($facilityId);
        if (! $facility) {
            return "HATA: facility_id={$facilityId} bulunamadi (silinmis olabilir, facility-trash-check ile kontrol edin).";
        }

        $oldPath = $facility->video_path;
        if ($oldPath) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }
        $facility->update(['video_path' => null, 'video_updated_at' => null]);

        $fresh = Facility::withTrashed()->find($facilityId);

        return "#{$facilityId} {$facility->name}\nEski video_path: {$oldPath}\nYeni video_path: ".var_export($fresh->video_path, true)."\ndeleted_at: ".var_export($fresh->deleted_at, true);
    }

    /**
     * 3 Eylul 2026: kullanicinin "Videoyu Sil kurumu komple siliyor" bildirimi
     * icin KESIN kanit - GERCEK kurum verisine dokunmadan, sahte bir
     * qatest- kurum + sahte video_path uzerinde deleteVideo() metodunu
     * DOGRUDAN cagirir, once/sonra deleted_at ve video_path degerlerini
     * karsilastirir. Islem sonunda test kurumu ne olursa olsun temizlenir.
     */
    private function qaDeleteVideoTest(): string
    {
        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok.';
        }
        session(['admin_id' => $admin->id]);

        $facility = Facility::create([
            'name' => 'qatest-video-delete-'.now()->format('His'),
            'slug' => 'qatest-video-delete-'.now()->format('His'),
            'brand' => 'bakimevleri',
            'city_id' => Facility::query()->value('city_id'),
            'facility_category_id' => Facility::query()->value('facility_category_id'),
            'is_broker_managed' => true,
            'is_published' => true,
            'video_path' => 'facilities/videos/qatest-fake-video.mp4',
        ]);

        $out = "Test kurumu olusturuldu: #{$facility->id}\n";
        $out .= 'ONCE -> deleted_at: '.var_export($facility->deleted_at, true).', video_path: '.$facility->video_path."\n";

        try {
            $controller = app(\App\Http\Controllers\Admin\FacilityController::class);
            $response = $controller->deleteVideo($facility);
            $out .= 'deleteVideo() calisti, yanit sinifi: '.get_class($response)."\n";
        } catch (\Throwable $e) {
            $out .= 'deleteVideo() ISTISNA FIRLATTI: '.get_class($e).' - '.$e->getMessage()."\n";
        }

        $fresh = Facility::withTrashed()->find($facility->id);
        $out .= "SONRA -> deleted_at: ".var_export($fresh?->deleted_at, true).', video_path: '.var_export($fresh?->video_path, true)."\n";
        $out .= $fresh?->deleted_at ? "\n!!! KANITLANDI: deleteVideo() facility'yi SOFT-DELETE ETTI.\n" : "\nKurum silinmedi - deleteVideo() bu haliyle sadece video alanini temizliyor.\n";

        // Test kurumunu her ihtimale karsi kalici olarak temizle.
        Facility::withTrashed()->where('id', $facility->id)->forceDelete();
        $out .= "\n(test kurumu #{$facility->id} temizlendi)";

        return $out;
    }

    /**
     * 4 Eylul 2026: "anlaşmalı kurumlarda da Panelde Gör olmali" talebi
     * icin eklenen Admin\FacilityController::impersonate()'in KANITI - sahte,
     * SAHIPLENILMEMIS (is_claimed=false, is_broker_managed=true) bir test
     * kurumuyla dogrudan cagirir. Kontrol eder: (1) istisna firlatmiyor,
     * (2) bir facility_user olusturuyor, (3) is_claimed hala FALSE (yan etki
     * yok), (4) session dogru facility_user_id'ye ayarlaniyor.
     */
    private function qaFacilityImpersonateTest(): string
    {
        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok.';
        }
        session(['admin_id' => $admin->id, 'admin_name' => 'QATEST']);

        $facility = Facility::create([
            'name' => 'qatest-impersonate-'.now()->format('His'),
            'slug' => 'qatest-impersonate-'.now()->format('His'),
            'brand' => 'bakimevleri',
            'city_id' => Facility::query()->value('city_id'),
            'facility_category_id' => Facility::query()->value('facility_category_id'),
            'is_broker_managed' => true,
            'is_claimed' => false,
            'is_published' => true,
        ]);

        $out = "Test kurumu olusturuldu: #{$facility->id} (is_claimed=".var_export($facility->is_claimed, true).", is_broker_managed=".var_export($facility->is_broker_managed, true).")\n";

        try {
            $controller = app(\App\Http\Controllers\Admin\FacilityController::class);
            $response = $controller->impersonate($facility);
            $out .= 'impersonate() calisti, yanit sinifi: '.get_class($response)."\n";
        } catch (\Throwable $e) {
            $out .= 'impersonate() ISTISNA FIRLATTI: '.get_class($e).' - '.$e->getMessage()."\n";
        }

        $freshFacility = Facility::find($facility->id);
        $createdUser = \App\Models\FacilityUser::where('facility_id', $facility->id)->first();

        $out .= 'facility_user olusturuldu mu: '.($createdUser ? "EVET (#{$createdUser->id}, email={$createdUser->email})" : 'HAYIR')."\n";
        $out .= 'is_claimed SONRA (degismemis olmali): '.var_export($freshFacility->is_claimed, true)."\n";
        $out .= 'session facility_user_id: '.var_export(session('facility_user_id'), true).' (beklenen: '.($createdUser->id ?? 'N/A').")\n";

        session()->forget(['facility_user_id', 'facility_user_name', 'impersonator_admin_id', 'impersonator_admin_name']);

        if ($createdUser) {
            \App\Models\FacilityUser::where('id', $createdUser->id)->delete();
        }
        Facility::withTrashed()->where('id', $facility->id)->forceDelete();
        $out .= "\n(test kurumu #{$facility->id} ve hesabi temizlendi)";

        return $out;
    }

    private function checkAdminFlows(): string
    {
        Artisan::call('platform:check-admin-flows');

        return Artisan::output();
    }

    private function cleanupStaleQaDebris(Request $request): string
    {
        Artisan::call('platform:cleanup-stale-qa-debris', [
            '--days' => (int) $request->query('days', 2),
        ]);

        return Artisan::output();
    }

    private function inviteReviewFamilies(): string
    {
        Artisan::call('reviews:invite-families');

        return Artisan::output();
    }

    private function snapshotFacilityStats(): string
    {
        Artisan::call('facility:snapshot-daily-stats');

        return Artisan::output();
    }

    private function snapshotCategoryViews(): string
    {
        Artisan::call('category:snapshot-views');

        return Artisan::output();
    }

    /**
     * 6 Eylul 2026: "Kurum Turune Gore Ilgi" karti karti gercekten
     * beklendigi gibi (bg-primary yerine hex renk, "veri birikiyor" notu
     * hasTrendData=false iken) render ediliyor mu diye - admin-facility-
     * edit-render ile AYNI guvenli desen, dashboard icin.
     */
    private function adminDashboardRender(Request $incomingRequest): string
    {
        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok.';
        }
        session(['admin_id' => $admin->id, 'admin_name' => $admin->name]);

        $response = app(\App\Http\Controllers\Admin\DashboardController::class)->index();
        $html = $response->render();

        $out = 'HTML uzunlugu: '.strlen($html)." bayt\n\n";
        $pos = mb_strpos($html, 'Kurum Türüne Göre İlgi');
        if ($pos === false) {
            $out .= "'Kurum Türüne Göre İlgi' bulunamadi.\n";
        } else {
            $out .= mb_substr($html, $pos, 1200)."\n";
        }

        return $out;
    }

    // 3 Agustos 2026: mail'ler artik hic kuyruklanmadigi (sendNow) icin
    // failed_jobs'a YENI kayit dusmesi beklenmez - buradaki eski kayitlar
    // 28-30 Temmuz'da, o gunku ->send()/->queue() donemi kalintisi test
    // verilerinin sonradan silinmesiyle olusmus, zararsiz ama kafa
    // karistirici gecmis kayitlar. Salt bu tabloyu temizler.
    private function queueFlushFailed(): string
    {
        $count = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->truncate();

        return "Temizlendi: {$count} eski basarisiz is kaydi silindi.";
    }

    // DatabaseSeeder yalnizca updateOrCreate() kullanir (bkz. database/seeders/
    // DatabaseSeeder.php) - bu yuzden dolu bir veritabaninda (ör. yanlislikla
    // production'da) tekrar calistirilsa bile veri kaybina/coklanmaya yol acmaz,
    // sadece admin/il/kategori/demo icerik satirlarini idempotent gunceller.
    private function seed(): string
    {
        Artisan::call('db:seed', ['--force' => true]);

        return Artisan::output();
    }

    // 13 Temmuz 2026: staging.bakimevleri.com gibi sifirdan kurulan bir ortamda
    // "php artisan storage:link" calistirmak icin - SSH/terminal erisimi olmayan
    // bu hostingde public/storage sembolik baglantisini kurmanin tek yolu bu.
    private function storageLink(): string
    {
        Artisan::call('storage:link');

        return Artisan::output();
    }

    // Test/staging ortamlarinda DatabaseSeeder bilinen-sifreli demo admin
    // hesabini kasitli atlar (bkz. DatabaseSeeder). Gercek bir sifreyle admin
    // hesabi acmanin SSH'siz tek yolu bu - sifre SADECE bu yanitta bir kez
    // gosterilir, hicbir yerde saklanmaz.
    private function createAdmin(Request $request): string
    {
        $email = (string) $request->query('email', 'admin@staging.bakimevleri.com');
        $password = str()->random(20);

        \App\Models\Admin::updateOrCreate(
            ['email' => $email],
            ['name' => 'Staging Admin', 'password' => \Illuminate\Support\Facades\Hash::make($password), 'role' => 'superadmin']
        );

        return "OK: {$email} / {$password}\n(Bu sifre bir daha gosterilmeyecek.)";
    }

    // docs/PRODUCTION.md bolum 14: deploy sonrasi bir kere calistirilmasi
    // istenen, sunucuda GD/Imagick'in gercekten calisip calismadigini
    // raporlayan salt-okunur teshis komutu - hic calistirilmamisti.
    private function diagnosticsImage(): string
    {
        Artisan::call('diagnostics:image-compression');

        return Artisan::output();
    }

    // 13 Temmuz 2026: gunluk 03:30 cron'unu beklemeden, riskli bir islem
    // oncesi elle anlik yedek almak icin.
    private function backupNow(): string
    {
        Artisan::call('backup:database');

        return Artisan::output();
    }

    private function packageDiscover(): string
    {
        // Sunucunun kendi vendor/composer/installed.json'undan bootstrap/cache/
        // packages.php + services.php dosyalarini yeniden uretir. KRITIK: Laravel'in
        // PackageManifest::build() metodu var olan cache dosyasini SIFIRDAN yazmaz,
        // ARTIMLI gunceller - yani eskiden orada olan bir paket (ör. dev-dahil bir
        // yuklemeden kalma laravel/sail) yeni kesifte artik installed.json'da
        // olmasa bile cache'de KALMAYA devam eder. Bu yuzden dosyalari once silmek
        // sart (11 Temmuz 2026'da bu tam olarak siteyi tekrar dusurdu - CSRF fix'i
        // sonrasi bu uc ilk kez gercekten calistiginda ortaya cikti).
        File::delete([
            base_path('bootstrap/cache/packages.php'),
            base_path('bootstrap/cache/services.php'),
        ]);

        Artisan::call('package:discover');

        return Artisan::output();
    }

    private function cacheRefresh(): string
    {
        $output = '';

        // 14 Agustos 2026: kullanicinin "hata var uyarisi geldi" bildirimi
        // uzerine yapilan incelemede bulundu - route:clear route:cache'den
        // ONCE calisip bootstrap/cache/routes-v7.php dosyasini kisa bir sure
        // (iki Artisan::call arasindaki an) tamamen SILIYORDU. Bu pencerede
        // ayni sunucuda calisan BASKA bir surec (bu durumda sitenin kendi
        // /_internal/cron-runner'i, schedule:run icin ayri bir 'php artisan'
        // alt sureci baslatiyor) o dosyayi require etmeye calisirsa "No such
        // file or directory" ile coker - tam olarak canli logda yakalanan
        // olay buydu (claims:expire-undocumented o dakika calismadi, bir
        // sonraki calismada sorunsuz tamamlanir, veri kaybi yok). route:cache/
        // config:cache zaten dosyayi YERINDE ustune yazar - once clear etmek
        // gereksizdi ve tam da bu bosluga yol aciyordu, kaldirildi.
        foreach (['route:cache', 'config:cache', 'view:clear', 'view:cache', 'cache:clear'] as $command) {
            Artisan::call($command);
            $output .= Artisan::output();
        }

        return $output;
    }

    // Bugune kadar her hata teshisinde public/'e ozel bir "log oku" script'i
    // atip silme deseninin yerini alir - artik kalici, token korumali bu uc
    // uzerinden log dosyasinin son N byte'ini okuyabiliyoruz.
    private function logTail(int $bytes): string
    {
        $bytes = max(1000, min($bytes, 200000));
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return 'LOG YOK';
        }

        $size = File::size($path);
        $handle = fopen($path, 'r');
        fseek($handle, max(0, $size - $bytes));
        $content = fread($handle, $bytes);
        fclose($handle);

        return $content;
    }

    // Sentry DSN production'a eklendikten sonra gercekten calisip
    // calismadigini dogrulamak icin bilincli bir test hatasi gonderir.
    // NOT: paketin kendi 'sentry:test' artisan komutu KASITLI olarak
    // SADECE console'da calisirken register ediliyor (ServiceProvider'da
    // runningInConsole() sarti var) - bu uc noktadan Artisan::call() ile
    // cagirilinca "command not found" ile 500 verdigi 12 Temmuz 2026'da
    // gorulup duzeltildi. Bunun yerine SDK'nin kendi captureException()
    // fonksiyonu dogrudan kullanilir, console kisitlamasindan etkilenmez.
    private function sentryTest(): string
    {
        try {
            throw new \Exception('Bu, /_ops/sentry-test uzerinden gonderilen bilincli bir test hatasidir.');
        } catch (\Exception $exception) {
            $eventId = \Sentry\captureException($exception);
        }

        return $eventId ? "Test olayi gonderildi: {$eventId}" : 'HATA: Sentry olayi gonderilemedi (DSN bos veya gecersiz olabilir).';
    }

    // 12 Temmuz 2026'da QUEUE_CONNECTION sync'ten database'e gecirildi
    // (mail gonderimi artik istegi yapan ziyaretciyi beklemiyor, ayrica
    // paylasimli hostingin "Entry Processes" sinirini de daha az isgal
    // ediyor). Gercek isleyici cPanel Cron Jobs uzerinden dogrudan
    // `php artisan queue:work` calistiriyor - bu iki uc SADECE elle
    // dogrulama/acil mudahale icin (ör. cron bir sure calismazsa kuyrugu
    // burdan elle bosaltmak).
    private function queueStatus(): string
    {
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        $out = "queue.default=" . config('queue.default') . "\nbekleyen=$pending\nbasarisiz=$failed\n";

        if ($pending > 0) {
            $out .= "\nBekleyen islerin turu:\n";
            foreach (DB::table('jobs')->limit(10)->get() as $job) {
                $payload = json_decode($job->payload, true);
                $out .= "  #{$job->id} " . ($payload['displayName'] ?? '?') . " (queue={$job->queue})\n";
            }
        }

        return $out;
    }

    private function queueFailedDetail(): string
    {
        $rows = DB::table('failed_jobs')->latest('failed_at')->limit(15)->get();
        $out = "Son " . $rows->count() . " basarisiz is:\n\n";
        foreach ($rows as $r) {
            $payload = json_decode($r->payload, true);
            $job = $payload['displayName'] ?? '?';
            $firstLine = strtok((string) $r->exception, "\n");
            $out .= "[{$r->failed_at}] {$job}\n  {$firstLine}\n\n";
        }

        return $out;
    }

    // 31 Temmuz 2026: kullanici "Kurum Kayit Basvurulari" akisinin
    // mail/yonlendirme davranisini tekrar test etmek icin, daha once ONAYLADIGI
    // (test amacli) basvurulari tekrar 'pending'e dondurmek istedi. approve()
    // yeni bir Facility+FacilityUser SATIRI OLUSTURUYOR (var olan bir kaydi
    // guncellemekten farkli) - bu yuzden geri almak sadece durumu degistirmek
    // degil, olusturulan kayitlari da temizlemek demek. Facility'yi forceDelete
    // ile (soft-delete degil) siliyoruz ki tekrar onaylaninca ayni slug
    // catismasi yasanmasin. GUVENLIK: sadece su an 'approved' olan basvurular
    // icin calisir, kullanici hangi basvurularin test oldugunu elle onaylamis
    // olmali - bu genel amacli, tekrar tekrar kullanilabilir bir "geri al" uc.
    private function registrationRevertToPending(Request $request): string
    {
        $id = (int) $request->query('registration_id', 0);
        $email = (string) $request->query('applicant_email', '');
        $registration = $id
            ? \App\Models\FacilityRegistration::find($id)
            : \App\Models\FacilityRegistration::where('applicant_email', $email)->where('status', 'approved')->latest()->first();
        if (! $registration) {
            return "HATA: basvuru bulunamadi (id={$id}, email={$email})";
        }
        $id = $registration->id;
        if ($registration->status !== 'approved') {
            return "HATA: basvuru #{$id} 'approved' durumunda degil (su an: {$registration->status}), sadece onaylanmis basvurular geri alinabilir";
        }

        $facilityUser = \App\Models\FacilityUser::where('email', $registration->applicant_email)->first();
        $facilityId = $facilityUser?->facility_id;

        if ($facilityUser) {
            DB::table('platform_notifications')->where('notifiable_type', 'App\\Models\\FacilityUser')->where('notifiable_id', $facilityUser->id)->delete();
            $facilityUser->delete();
        }

        if ($facilityId) {
            DB::table('balance_logs')->where('facility_id', $facilityId)->delete();
            \App\Models\Facility::where('id', $facilityId)->forceDelete();
        }

        $registration->update(['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]);

        return "OK: basvuru #{$id} ({$registration->applicant_email}) tekrar 'pending' yapildi." . ($facilityId ? " Olusturulmus facility #{$facilityId} ve facility_user silindi." : ' (Baglanti bir facility_user bulunamadi, sadece durum sifirlandi.)');
    }

    private function registrationDetail(Request $request): string
    {
        $id = (int) $request->query('id', 0);

        if ($request->boolean('restore')) {
            DB::table('facility_registrations')->where('id', $id)->update(['deleted_at' => null]);
        }

        $row = DB::table('facility_registrations')->where('id', $id)->first();
        if (! $row) {
            return "HATA: facility_registrations tablosunda #{$id} bulunamadi (satir gercekten yok).";
        }

        return "Bulundu:\n" . json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 10 Agustos 2026: "kurum resim ekliyor ve gorunmuyor" sikayeti - tum
    // facility_images kayitlarini tarayip DB'de var ama diskte OLMAYAN
    // (kirik) gorsel sayisini/oranini raporlar, sorunun bir kurumla sinirli
    // mi yoksa genel mi oldugunu gormek icin. Salt-okunur.
    private function galleryHealthScan(Request $request): string
    {
        $limit = (int) $request->query('limit', 500);
        $images = DB::table('facility_images')->orderByDesc('id')->limit($limit)->get(['id', 'facility_id', 'path', 'created_at']);

        $missing = [];
        $ok = 0;
        foreach ($images as $img) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($img->path)) {
                $ok++;
            } else {
                $missing[] = $img;
            }
        }

        $affectedFacilities = collect($missing)->pluck('facility_id')->unique()->count();
        $oldestMissing = collect($missing)->min('created_at');
        $newestMissing = collect($missing)->max('created_at');
        $demoMissing = collect($missing)->filter(fn ($img) => str_starts_with($img->path, 'facilities/demo/'));
        $realMissing = collect($missing)->filter(fn ($img) => ! str_starts_with($img->path, 'facilities/demo/'));

        $out = "Son {$limit} facility_images kaydi tarandi.\n";
        $out .= "Diskte VAR: {$ok}\n";
        $out .= "Diskte YOK (kirik) TOPLAM: " . count($missing) . "\n";
        $out .= "  - 'facilities/demo/...' (veri cekici sablon) kirik: {$demoMissing->count()}\n";
        $out .= "  - GERCEK kullanici yuklemesi kirik (facilities/RANDOM.webp): {$realMissing->count()}\n";
        $out .= "Etkilenen farkli kurum sayisi: {$affectedFacilities}\n";
        if ($missing) {
            $out .= "En eski kirik kayit: {$oldestMissing}\n";
            $out .= "En yeni kirik kayit: {$newestMissing}\n\n";
            $out .= "Ilk 25 GERCEK (demo olmayan) kirik kayit:\n";
            foreach ($realMissing->take(25) as $img) {
                $out .= "  #{$img->id} facility_id={$img->facility_id} olusturulma={$img->created_at} path={$img->path}\n";
            }
        }

        return $out;
    }

    // 10 Agustos 2026: "kurum resim ekliyor gorunmuyor" olayinin acil
    // duzeltmesi - DB'de kayitli ama diskte OLMAYAN (kirik) facility_images
    // satirlarini siler, boylece kota (MAX_GALLERY_IMAGES=10) bosalir ve
    // kurum yetkilisi gorselleri TEKRAR yukleyebilir. facility_id verilirse
    // sadece o kuruma, verilmezse (dikkatli kullanin) TUM kirik kayitlara
    // uygulanir. Sadece "diskte yok" olanlari siler - var olan hicbir
    // gorsele dokunmaz.
    private function galleryPruneBroken(Request $request): string
    {
        $facilityId = (int) $request->query('facility_id', 0);
        $query = DB::table('facility_images');
        if ($facilityId > 0) {
            $query->where('facility_id', $facilityId);
        }
        $images = $query->get(['id', 'facility_id', 'path']);

        $deleted = 0;
        foreach ($images as $img) {
            if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($img->path)) {
                DB::table('facility_images')->where('id', $img->id)->delete();
                $deleted++;
            }
        }

        return "Silinen kirik kayit: {$deleted}" . ($facilityId > 0 ? " (facility #{$facilityId})" : ' (tum kurumlar)');
    }

    // 24 Agustos 2026: kullanicinin talebi - Bursa'da yerinde ziyaret
    // yapabilmesi icin ilce/adres bilgisiyle bir liste. Ayri bir "mahalle"
    // kolonu veritabaninda yok (sadece ilce - district - var), bu yuzden
    // adres metninin TAMAMI ayri bir kolonda verilir (cogu Turkiye adresi
    // "... Mahallesi ..." iceriyor, kullanici oradan mahalleyi kendi
    // gozuyle ayirt edebilir). Ilce, sonra adres alfabetik siralanir ki
    // ayni ilcedeki/yakin adresteki kurumlar yan yana gelsin. Salt-okunur.
    private function bursaVisitExport(Request $request): string
    {
        $onlyUnclaimed = $request->query('unclaimed_only', '1') !== '0';

        $city = DB::table('cities')->where('slug', 'bursa')->first();
        if (! $city) {
            return "HATA: 'bursa' slug'li sehir bulunamadi.";
        }

        $query = DB::table('facilities')
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->where('facilities.city_id', $city->id)
            ->whereIn('facilities.ownership_type', ['ozel', 'vakif'])
            ->whereNull('facilities.deleted_at');

        if ($onlyUnclaimed) {
            $query->where('facilities.is_claimed', false);
        }

        $rows = $query
            ->orderByRaw("COALESCE(NULLIF(facilities.district, ''), 'ZZZ_Ilce_Belirtilmemis')")
            ->orderBy('facilities.address')
            ->get([
                'facilities.id',
                'facilities.name',
                'facilities.district',
                'facilities.address',
                'facilities.phone',
                'facility_categories.name as category_name',
                'facilities.invitation_status',
            ]);

        $out = "id\tKurum Adi\tIlce\tAdres (mahalleyi buradan ayirt edin)\tTelefon\tKategori\tDavet Durumu\n";
        foreach ($rows as $r) {
            $out .= implode("\t", [
                $r->id,
                $r->name,
                $r->district ?: '(ilce yok)',
                $r->address ?: '(adres yok)',
                $r->phone ?: '(telefon yok)',
                $r->category_name,
                $r->invitation_status ?: 'not_started',
            ])."\n";
        }
        $out .= "\nToplam satir: {$rows->count()}";

        return $out;
    }

    // 12 Agustos 2026: kurum adiyla hizli arama - id, sahiplenme, kategori,
    // her galeri gorselinin diskte var/yok durumunu tek ekranda gosterir.
    // Salt-okunur, hicbir sey degistirmez.
    private function facilityLookup(Request $request): string
    {
        $q = (string) $request->query('q', '');
        if ($q === '') {
            return 'q parametresi gerekli (kurum adinda arama).';
        }

        $facilities = DB::table('facilities')
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->where('facilities.name', 'like', '%'.$q.'%')
            ->whereNull('facilities.deleted_at')
            ->select('facilities.id', 'facilities.name', 'facilities.slug', 'facilities.is_claimed', 'facility_categories.name as category_name', 'facility_categories.id as category_id')
            ->limit(20)
            ->get();

        if ($facilities->isEmpty()) {
            return "'{$q}' icin kurum bulunamadi.";
        }

        $out = '';
        foreach ($facilities as $f) {
            $out .= "#{$f->id} {$f->name} | slug: {$f->slug} | kategori: {$f->category_name} (#{$f->category_id}) | sahiplenme: " . ($f->is_claimed ? 'SAHIPLENILMIS' : 'on kayitli/sahiplenilmemis') . "\n";
            $images = DB::table('facility_images')->where('facility_id', $f->id)->get(['id', 'path']);
            if ($images->isEmpty()) {
                $out .= "    (hic gorseli yok)\n";
            }
            foreach ($images as $img) {
                $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($img->path) ? 'VAR' : 'KIRIK (diskte yok)';
                $out .= "    gorsel #{$img->id}: {$img->path} -> {$exists}\n";
            }
        }

        return $out;
    }

    // 12 Agustos 2026: kullanicinin talebi - fizyoterapi bolumunde 2
    // sahiplenilmemis kurumun gorselleri kirikti, "demo gorsellerden bu
    // kuruma yukle" dedi. Yeni dosya YUKLEMEZ - ayni kategoride, DISKTE
    // GERCEKTEN VAR olan baska bir kurumun demo gorsel dosyalarini
    // ODUNC alip (path'i PAYLASARAK, kopyalamadan) hedef kurumun kirik
    // kayitlarinin YERINE yeni facility_images satirlari olusturur. Sadece
    // is_claimed=false (sahiplenilmemis) kurumlarda calisir - sahiplenilmis
    // hicbir kurumun galerisine dokunmaz.
    private function facilityBorrowDemoImages(Request $request): string
    {
        $facilityId = (int) $request->query('facility_id', 0);
        $count = (int) $request->query('count', 3);
        if ($facilityId <= 0) {
            return 'facility_id parametresi gerekli.';
        }

        $facility = DB::table('facilities')->where('id', $facilityId)->whereNull('deleted_at')->first();
        if (! $facility) {
            return "Kurum #{$facilityId} bulunamadi.";
        }
        if ($facility->is_claimed) {
            return "Kurum #{$facilityId} ({$facility->name}) SAHIPLENILMIS - guvenlik icin bu islem sadece sahiplenilmemis kurumlarda calisir.";
        }

        return $this->borrowDemoImagesForFacility($facility, $count);
    }

    private function borrowDemoImagesForFacility(object $facility, int $count): string
    {
        $donorImages = DB::table('facility_images')
            ->join('facilities', 'facilities.id', '=', 'facility_images.facility_id')
            ->where('facilities.facility_category_id', $facility->facility_category_id)
            ->where('facility_images.facility_id', '!=', $facility->id)
            ->where('facility_images.path', 'like', 'facilities/demo/%')
            ->select('facility_images.path')
            ->distinct()
            ->limit(50)
            ->get()
            ->filter(fn ($row) => \Illuminate\Support\Facades\Storage::disk('public')->exists($row->path))
            ->values();

        if ($donorImages->isEmpty()) {
            return "#{$facility->id} {$facility->name}: ayni kategoride diskte gercekten var olan baska bir demo gorsel bulunamadi, odunc alinacak kaynak yok.";
        }

        $brokenDeleted = DB::table('facility_images')
            ->where('facility_id', $facility->id)
            ->get(['id', 'path'])
            ->filter(fn ($img) => ! \Illuminate\Support\Facades\Storage::disk('public')->exists($img->path));
        foreach ($brokenDeleted as $img) {
            DB::table('facility_images')->where('id', $img->id)->delete();
        }

        $toAdd = $donorImages->shuffle()->take(max(1, $count));
        $now = now();
        foreach ($toAdd as $donor) {
            DB::table('facility_images')->insert([
                'facility_id' => $facility->id,
                'path' => $donor->path,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return "#{$facility->id} {$facility->name}: {$brokenDeleted->count()} kirik kayit silindi, {$toAdd->count()} calisan demo gorsel odunc alindi.";
    }

    // 12 Agustos 2026: kullanicinin talebi - "sahipleninler haric olmak
    // sartiyla benzer durumda olan BUTUN kurumlari bul ve bolumune uygun
    // demo gorseller ekle". Sahiplenilmemis + 'facilities/demo/%' yolu
    // KIRIK olan TUM kurumlari tek tek borrowDemoImagesForFacility() ile
    // duzeltir (ayni kategoriden calisan bir demo gorsel odunc alarak -
    // "bolumune uygun" sarti boylece saglanir). Paylasilan hosting'te tek
    // istekte tum tabloyu taramak zaman asimina yol acabilir, bu yuzden
    // facility_id'ye gore sayfali (limit/offset) calisir - birden fazla
    // cagriyla tum tabloyu kapsayabilirsiniz. dry_run=1 (varsayilan) hicbir
    // sey degistirmez, sadece neyin yapilacagini raporlar.
    private function facilityBorrowDemoImagesBulk(Request $request): string
    {
        $limit = (int) $request->query('limit', 3000);
        $offset = (int) $request->query('offset', 0);
        $dryRun = $request->query('dry_run', '1') !== '0';

        $rows = DB::table('facility_images')
            ->join('facilities', 'facilities.id', '=', 'facility_images.facility_id')
            ->where('facilities.is_claimed', false)
            ->where('facility_images.path', 'like', 'facilities/demo/%')
            ->whereNull('facilities.deleted_at')
            ->select('facilities.id as facility_id', 'facilities.name', 'facilities.facility_category_id', 'facility_images.path')
            ->orderBy('facilities.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $broken = $rows->filter(fn ($r) => ! \Illuminate\Support\Facades\Storage::disk('public')->exists($r->path));
        $affected = $broken->groupBy('facility_id');

        $out = "Bu pencerede taranan satir: {$rows->count()} (offset={$offset}, limit={$limit})\n";
        $out .= "Kirik kayit: {$broken->count()}\n";
        $out .= "Etkilenen kurum sayisi: {$affected->count()}\n";
        $out .= $dryRun ? "MOD: dry_run (hicbir sey degistirilmedi)\n\n" : "MOD: UYGULANDI\n\n";

        foreach ($affected as $facilityId => $imgs) {
            $facilityRow = (object) [
                'id' => $facilityId,
                'name' => $imgs->first()->name,
                'facility_category_id' => $imgs->first()->facility_category_id,
            ];
            if ($dryRun) {
                $out .= "#{$facilityId} {$facilityRow->name}: {$imgs->count()} kirik gorsel bulundu, dry_run oldugu icin dokunulmadi.\n";
            } else {
                $out .= $this->borrowDemoImagesForFacility($facilityRow, $imgs->count()) . "\n";
            }
        }

        if ($rows->count() >= $limit) {
            $out .= "\nNOT: bu pencere limite ulasti, tum tabloyu kapsamak icin offset=" . ($offset + $limit) . " ile tekrar cagirin.";
        }

        return $out;
    }

    // 10 Agustos 2026: "sahiplenilen kurumda demo gorsel kaldirilmasi
    // gerekiyor" talebi - veri cekici/on-kayit surecinden kalma
    // 'facilities/demo/...' sablon path'leri (gercek dosyasi hic olmayan
    // placeholder'lar) SADECE artik sahiplenilmis (is_claimed=true)
    // kurumlarda temizler. GERCEK kullanici yuklemesi olan hicbir
    // 'facilities/RANDOM.webp' kaydina KESINLIKLE dokunmaz - path prefix'i
    // ile kesin ayrim yapar. dry_run=1 (varsayilan) sadece sayar, siler
    // sadece dry_run=0 ile.
    private function demoImagesCleanup(Request $request): string
    {
        $dryRun = $request->query('dry_run', '1') !== '0';

        $rows = DB::table('facility_images')
            ->join('facilities', 'facilities.id', '=', 'facility_images.facility_id')
            ->where('facilities.is_claimed', true)
            ->where('facility_images.path', 'like', 'facilities/demo/%')
            ->get(['facility_images.id', 'facility_images.facility_id', 'facility_images.path', 'facilities.name']);

        $affectedFacilities = $rows->pluck('facility_id')->unique()->count();

        if ($dryRun) {
            $out = "[ON IZLEME - hicbir sey silinmedi] Sahiplenilmis kurumlarda 'facilities/demo/' path'li kayit: {$rows->count()} ({$affectedFacilities} farkli kurum)\n\n";
            foreach ($rows->take(20) as $r) {
                $out .= "  #{$r->id} facility #{$r->facility_id} ({$r->name}) path={$r->path}\n";
            }
            $out .= "\nGercekten silmek icin: dry_run=0 parametresiyle tekrar cagirin.";

            return $out;
        }

        DB::table('facility_images')->whereIn('id', $rows->pluck('id'))->delete();

        return "SILINDI: {$rows->count()} demo gorsel kaydi, {$affectedFacilities} sahiplenilmis kurumdan.";
    }

    // 19 Agustos 2026: kullanicinin acik talebi uzerine geri alma - bir onceki
    // demoImagesCleanup() cagrisi kullanicinin "test verilerini sil" talebini
    // YANLIS genisletip 2 GERCEK sahiplenilmis kuruma dokunmustu (kullanici:
    // "sahiplenen kurumlara karisma"). Silinen 6 satir BIREBIR ayni id/facility_id/
    // path ile geri eklenir - kalici, tek seferlik bir duzeltme, tekrar
    // kullanilmasi beklenmez.
    private function restoreAccidentallyDeletedClaimedFacilityDemoImages(): string
    {
        $rows = [
            ['id' => 3206, 'facility_id' => 642, 'path' => 'facilities/demo/6/1.webp', 'sort_order' => 0],
            ['id' => 3208, 'facility_id' => 642, 'path' => 'facilities/demo/6/3.webp', 'sort_order' => 1],
            ['id' => 3209, 'facility_id' => 642, 'path' => 'facilities/demo/6/4.webp', 'sort_order' => 2],
            ['id' => 6868, 'facility_id' => 1374, 'path' => 'facilities/demo/7/3.webp', 'sort_order' => 0],
            ['id' => 6869, 'facility_id' => 1374, 'path' => 'facilities/demo/7/4.webp', 'sort_order' => 1],
            ['id' => 6870, 'facility_id' => 1374, 'path' => 'facilities/demo/7/5.webp', 'sort_order' => 2],
        ];

        $restored = 0;
        foreach ($rows as $row) {
            if (DB::table('facility_images')->where('id', $row['id'])->exists()) {
                continue;
            }
            DB::table('facility_images')->insert([
                'id' => $row['id'],
                'facility_id' => $row['facility_id'],
                'path' => $row['path'],
                'sort_order' => $row['sort_order'],
                'is_primary' => false,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $restored++;
        }

        return "Geri yuklenen kayit: {$restored}/6";
    }

    // 20 Agustos 2026: storage/framework/sessions'ta Laravel'in lottery-tabanli
    // otomatik GC'si (config/session.php 'lottery' => [2,100]) beklendigi gibi
    // calismamis, dosyalar en az 13 Temmuz'dan beri hic silinmemis. Bu tek
    // klasor, hesabin 500.000 dosya (inode) sinirinin doldugu asil kaynak.
    // FTP ile tek tek silmek yuz binlerce round-trip gerektirip saatler
    // surecegi icin, sunucunun kendi PHP'si yerel diskte dogrudan siler
    // (network round-trip yok, saniyeler/dakikalar surer). Zaman butcesi
    // dahilinde calisir, tek cagrida bitirmezse ayni action tekrar
    // cagrilarak devam edilir (idempotent, silinen dosya sayisini rapor eder).
    private function sessionsGc(Request $request): string
    {
        $dryRun = $request->query('dry_run', '1') !== '0';
        $maxAgeMinutes = (int) $request->query('max_age_minutes', 180);
        $budgetSeconds = (float) $request->query('budget_seconds', 25);

        $dir = storage_path('framework/sessions');
        if (! is_dir($dir)) {
            return "Klasor bulunamadi: {$dir}";
        }

        $cutoff = time() - ($maxAgeMinutes * 60);
        $start = microtime(true);

        $scanned = 0;
        $eligible = 0;
        $deleted = 0;
        $newest = null;
        $oldest = null;

        $handle = opendir($dir);
        while (($name = readdir($handle)) !== false) {
            if ($name === '.' || $name === '..' || $name === '.gitkeep') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (! is_file($path)) {
                continue;
            }
            $scanned++;
            $mtime = @filemtime($path);
            if ($mtime === false) {
                continue;
            }
            if ($oldest === null || $mtime < $oldest) {
                $oldest = $mtime;
            }
            if ($newest === null || $mtime > $newest) {
                $newest = $mtime;
            }
            if ($mtime < $cutoff) {
                $eligible++;
                if (! $dryRun) {
                    if (@unlink($path)) {
                        $deleted++;
                    }
                }
            }
            if ((microtime(true) - $start) > $budgetSeconds) {
                break;
            }
        }
        closedir($handle);

        $elapsed = round(microtime(true) - $start, 1);
        $oldestStr = $oldest ? date('Y-m-d H:i', $oldest) : '-';
        $newestStr = $newest ? date('Y-m-d H:i', $newest) : '-';

        if ($dryRun) {
            return "[ON IZLEME] {$scanned} dosya tarandi ({$elapsed}s icinde, butce doldugunda durdu), ".
                "{$eligible} tanesi {$maxAgeMinutes} dakikadan eski (silinebilir). ".
                "Taranan araliktaki en eski: {$oldestStr}, en yeni: {$newestStr}. ".
                'Gercekten silmek icin dry_run=0 ile tekrar cagirin, tek cagri butceyi doldurursa ayni parametrelerle tekrar tekrar cagirin.';
        }

        return "SILINDI: {$deleted}/{$eligible} dosya ({$scanned} tarandi, {$elapsed}s). ".
            "Taranan araliktaki en eski: {$oldestStr}, en yeni: {$newestStr}. ".
            'Hala eski dosya kalmis olabilir, klasor kucuk gorunene kadar ayni cagriyi tekrarlayin.';
    }

    private function documentDiagnostic(Request $request): string
    {
        $type = (string) $request->query('type', 'claim');
        $id = (int) $request->query('id', 0);

        if ($type === 'gallery') {
            $name = (string) $request->query('name', '');
            $userName = (string) $request->query('user_name', '');
            if ($userName !== '') {
                $fu = DB::table('facility_users')->where('name', 'like', "%{$userName}%")->first();
                if (! $fu) {
                    return "HATA: '{$userName}' adinda kurum yetkilisi bulunamadi";
                }
                $id = $fu->facility_id;
            } elseif ($name !== '') {
                $matches = DB::table('facilities')->where('name', 'like', "%{$name}%")->get(['id', 'name', 'is_claimed', 'source']);
                if ($matches->isEmpty()) {
                    return "HATA: '{$name}' adinda kurum bulunamadi";
                }
                if ($matches->count() > 1) {
                    $out = "BIRDEN FAZLA eslesme bulundu, hangisini istediginizi 'id=' ile belirtin:\n";
                    foreach ($matches as $m) {
                        $out .= "  #{$m->id} {$m->name} sahiplenilmis=" . ($m->is_claimed ? 'evet' : 'hayir') . " source={$m->source}\n";
                    }

                    return $out;
                }
                $id = $matches->first()->id;
            }
            $images = DB::table('facility_images')->where('facility_id', $id)->orderByDesc('id')->limit(10)->get();
            $out = "facility #{$id} - facility_images son 10:\n";
            $publicRoot = \Illuminate\Support\Facades\Storage::disk('public')->path('');
            $out .= "public disk root: {$publicRoot}\n";
            $storageLinkPath = public_path('storage');
            $out .= "public/storage symlink var mi: " . (is_link($storageLinkPath) || is_dir($storageLinkPath) ? 'EVET' : 'HAYIR - storage-link calistirilmamis olabilir!') . "\n";
            $out .= "public/storage gercekten linkli mi (readlink): " . (is_link($storageLinkPath) ? readlink($storageLinkPath) : '(sembolik link degil)') . "\n\n";
            foreach ($images as $img) {
                $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($img->path);
                $size = $exists ? \Illuminate\Support\Facades\Storage::disk('public')->size($img->path) : 0;
                $out .= "  #{$img->id} olusturulma={$img->created_at} path={$img->path} diskte_var=" . ($exists ? "EVET ({$size} byte)" : 'HAYIR') . "\n";
            }

            return $out;
        }

        $column = $type === 'topup' ? 'receipt_path' : 'document_path';
        $modelClass = $type === 'topup' ? \App\Models\WalletTopup::class : \App\Models\FacilityClaim::class;

        // id=0 (veya verilmemis) ise en son kaydi kullan - gercek admin
        // panelinden bir claim/topup ID'si aramak gerekmesin diye.
        $record = $id > 0 ? $modelClass::find($id) : $modelClass::orderByDesc('id')->first();
        if (! $record) {
            return "HATA: {$type} #{$id} bulunamadi";
        }
        $id = $record->id;

        $path = $record->{$column};
        $out = "{$type} #{$id}\n";
        $out .= "kayit olusturma tarihi: {$record->created_at}\n";
        $out .= "path (DB'de kayitli): " . ($path ?: '(BOS/NULL)') . "\n";

        if (! $path) {
            return $out . "-> DB'de dosya yolu hic kaydedilmemis.";
        }

        $localExists = \Illuminate\Support\Facades\Storage::disk('local')->exists($path);
        $out .= "storage/app/private diskinde var mi: " . ($localExists ? 'EVET' : 'HAYIR') . "\n";

        if ($localExists) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
            $out .= "boyut: " . \Illuminate\Support\Facades\Storage::disk('local')->size($path) . " byte\n";
            $out .= "gercek dosya yolu: {$fullPath}\n";
            $out .= "okunabilir mi (is_readable): " . (is_readable($fullPath) ? 'EVET' : 'HAYIR') . "\n";
        }

        $publicExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
        $out .= "(eski/yanlis 'public' diskinde var mi: " . ($publicExists ? 'EVET - dosya yanlis yerde!' : 'hayir') . ")\n";

        $dir = storage_path('app/private/claims');
        $freeBytes = disk_free_space($dir);
        $totalBytes = disk_total_space($dir);
        $out .= "\nDisk durumu ({$dir}):\n";
        $out .= "bos alan: " . ($freeBytes !== false ? number_format($freeBytes / 1024 / 1024, 1) . ' MB' : 'okunamadi') . "\n";
        $out .= "toplam alan: " . ($totalBytes !== false ? number_format($totalBytes / 1024 / 1024, 1) . ' MB' : 'okunamadi') . "\n";
        $out .= "dizin yazilabilir mi (is_writable): " . (is_writable($dir) ? 'EVET' : 'HAYIR') . "\n";

        return $out;
    }

    private function queueWork(): string
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 3,
            '--no-interaction' => true,
        ]);

        return Artisan::output();
    }

    // Gercek bir e-posta gondermeden, kuyruk hattinin (dispatch -> jobs
    // tablosu -> queue:work islemesi) production'da gercekten calistigini
    // dogrulamak icin - 12 Temmuz 2026'da QUEUE_CONNECTION=database'e
    // gecis sonrasi ilk canli test icin eklendi.
    private function queueTest(): string
    {
        dispatch(function () {
            \Illuminate\Support\Facades\Log::info('QUEUE-TEST: arka plan isci basariyla calisti, zaman=' . now());
        });

        return 'Kuyruga eklendi, bekleyen=' . DB::table('jobs')->count();
    }

    // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - Admin\FacilityController::update()'deki
    // "adres degismis olmali" sarti yuzunden, ilk otomatik geocode denemesi
    // basarisiz olan (ör. Nominatim o an yanit vermedi) bir kurumun adresi
    // bir daha degistirilmezse lat/lng SONSUZA KADAR bos kaliyordu (o kosul
    // ayni tarihte gevsetildi). Bu uc, GeocodingService'i kullanarak HALEN
    // adresi dolu ama lat/lng'i bos kalan kurumlari (bu yazida sadece 4
    // tane) tek seferlik geriye donuk doldurur - kod duzeltmesi SADECE
    // yeni kayitlari kapsadigi icin gerekliydi.
    private function geocodeMissingNow(Request $request): string
    {
        $limit = (int) $request->query('limit', 20);
        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - GeocodingService
        // artik once adresin KENDISINI (ilce/il eklemeden) dener (bkz. o
        // servisin ayni tarihli yorumu). retry_imprecise=1 ile, sadece
        // eksik (NULL) degil, daha once SADECE il-merkezi yedegiyle
        // doldurulmus (hasPreciseLocation()=false) kurumlar da yeniden
        // denenir - HepBahar gibi kurumlarin gercek adresi bu yeni
        // sorguyla cozulebilir.
        $retryImprecise = $request->boolean('retry_imprecise');
        $geocodingService = app(\App\Services\GeocodingService::class);

        $query = Facility::whereNull('deleted_at')->whereNotNull('address')->with('city');
        if ($retryImprecise) {
            // GUVENLIK/PERFORMANS: hasPreciseLocation() il-merkezi tablosuyla
            // PHP tarafinda karsilastirma yaptigi icin SQL'e tasinamaz - bu
            // yuzden filtre SONRA, tum satirlar cekildikten sonra uygulanir,
            // limit ise SQL'de DEGIL, filtrelenmis sonuc uzerinde alinir
            // (aksi halde ID sirasindaki ilk N kurum hic hedef kurumu
            // icermeyebilirdi).
            $facilities = $query->get()->filter(fn (Facility $f) => ! $f->hasPreciseLocation())->take($limit);
        } else {
            $facilities = $query->where(function ($q) {
                $q->whereNull('lat')->orWhereNull('lng');
            })->limit($limit)->get();
        }

        if ($facilities->isEmpty()) {
            return 'Adresi dolu, konumu eksik/hassas-olmayan kurum kalmadi.';
        }

        $out = '';
        foreach ($facilities as $f) {
            $coords = $geocodingService->geocodeAddress($f->address, $f->district, $f->city?->name);
            if ($coords) {
                $f->update(['lat' => $coords['lat'], 'lng' => $coords['lng']]);
                $out .= "  OK: #{$f->id} {$f->name} -> {$coords['lat']}, {$coords['lng']}\n";
            } else {
                $out .= "  BASARISIZ (adres cozumlenemedi): #{$f->id} {$f->name} - {$f->address}\n";
            }
            usleep(1100000);
        }

        return $out;
    }

    // 28 Temmuz 2026: "Yakinimdaki Kurumlar" icin lat/lng'i bos olan kurum
    // sayisini raporlayan salt-okunur teshis - gercek bir GPS/adres bazli
    // geocoding servisi (Google Geocoding API vb.) .env'de tanimli degil,
    // bu yuzden toplu bir otomatik doldurma once bu sayilarla gozden
    // gecirilmeli (bkz. docs/PRODUCTION.md bolum 13).
    private function geoStatus(): string
    {
        $total = DB::table('facilities')->whereNull('deleted_at')->count();
        $missing = DB::table('facilities')->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('lat')->orWhereNull('lng');
            })->count();

        $byCity = DB::table('facilities')
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->whereNull('facilities.deleted_at')
            ->where(function ($q) {
                $q->whereNull('facilities.lat')->orWhereNull('facilities.lng');
            })
            ->selectRaw('cities.name as city, count(*) as adet')
            ->groupBy('cities.name')
            ->orderByDesc('adet')
            ->limit(15)
            ->get();

        $out = "Toplam kurum: {$total}\nKonumu (lat/lng) eksik: {$missing}\n\nEn cok eksik olan iller:\n";
        foreach ($byCity as $row) {
            $out .= "  {$row->city}: {$row->adet}\n";
        }

        return $out;
    }

    // geo-status'un devami: lat/lng'i bos kurumlarin id/isim/adres/ilce/il
    // bilgisini JSON olarak dondurur - disaridaki (bu sunucunun disinda
    // calisan) bir geocoding is'inin kaynak listesi olarak kullanilir.
    // Adres verisi disari cikiyor ama bu uc zaten Bearer token korumali,
    // herkese acik degil.
    private function geoMissingList(Request $request): string
    {
        $rows = DB::table('facilities')
            ->leftJoin('cities', 'cities.id', '=', 'facilities.city_id')
            ->leftJoin('districts', 'districts.id', '=', 'facilities.district_id')
            ->whereNull('facilities.deleted_at')
            ->where(function ($q) {
                $q->whereNull('facilities.lat')->orWhereNull('facilities.lng');
            })
            ->select('facilities.id', 'facilities.name', 'facilities.address', 'facilities.district as district_text', 'districts.name as district_name', 'cities.name as city')
            ->orderBy('facilities.id')
            ->limit((int) $request->query('limit', 2000))
            ->offset((int) $request->query('offset', 0))
            ->get();

        return $rows->toJson();
    }

    // geo-missing-list ile disarida (Nominatim/OpenStreetMap gibi ucretsiz
    // bir servisle) geocode edilmis {id: [lat, lng]} ciftlerini uygular.
    // GUVENLIK: sadece su an lat veya lng'i BOS olan satirlari gunceller -
    // daha once elle girilmis veya veri cekiciyle gelmis gercek bir
    // koordinati asla ezmez.
    private function geoApply(Request $request): string
    {
        $pairs = (array) $request->json('coords', []);
        $updated = 0;
        $skipped = 0;

        foreach ($pairs as $id => $latLng) {
            if (! is_array($latLng) || count($latLng) !== 2) {
                continue;
            }
            [$lat, $lng] = $latLng;

            $affected = DB::table('facilities')
                ->where('id', (int) $id)
                ->where(function ($q) {
                    $q->whereNull('lat')->orWhereNull('lng');
                })
                ->update(['lat' => $lat, 'lng' => $lng, 'updated_at' => now()]);

            if ($affected > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        return "Guncellenen: {$updated}\nAtlanan (zaten doluydu veya id yok): {$skipped}";
    }

    // 28 Temmuz 2026: KVKK/Gizlilik/Cerez sayfa metinlerini admin panelinde
    // tek tek acmadan guncellemek icin. GUVENLIK: sadece LEGAL_SLUGS
    // listesindeki 3 sabit slug'a, sadece VAR OLAN satira (update, create
    // degil) yazar; slug alanina asla dokunmaz (admin formu title'dan
    // slug uretiyor - burada slug sabit tutulup URL'nin degismemesi
    // saglanir). Icerik admin formundaki sanitize_admin_html() ile ayni
    // fonksiyondan gecirilir ki script/onclick gibi seyler asla kaydedilmez.
    private function legalPageSet(Request $request): string
    {
        $brand = (string) $request->input('brand', '');
        $slug = (string) $request->input('slug', '');
        $body = (string) $request->input('body', '');
        $title = $request->input('title');

        if (! array_key_exists($brand, config('brands.brands', []))) {
            return "HATA: gecersiz brand '{$brand}'";
        }
        if (! in_array($slug, self::LEGAL_SLUGS, true)) {
            return "HATA: gecersiz slug '{$slug}' (sadece: " . implode(', ', self::LEGAL_SLUGS) . ')';
        }
        if (trim($body) === '') {
            return 'HATA: body bos olamaz';
        }

        $clean = function_exists('sanitize_admin_html') ? sanitize_admin_html($body) : $body;

        $update = ['body' => $clean, 'updated_at' => now()];
        if ($title) {
            $update['title'] = (string) $title;
        }

        $affected = DB::table('content_pages')
            ->where('brand', $brand)
            ->where('slug', $slug)
            ->update($update);

        return $affected > 0
            ? "OK: {$brand}/{$slug} guncellendi ({$affected} satir)"
            : "UYARI: {$brand}/{$slug} icin var olan satir bulunamadi, hicbir sey guncellenmedi";
    }

    // 28 Temmuz 2026: geo-apply ile gercek adresten geocode edilemeyen
    // (ör. ucretsiz Nominatim servisi bir sure sonra istekleri reddetmeye
    // basladigi icin) kalan kurumlar icin son care olarak il merkezi
    // koordinati atar - config/turkiye_centroids.php zaten "Yakinimdaki
    // Kurumlar" ozelliginin eski/yedek yontemi, ayni kaynagi kullaniyoruz.
    // GUVENLIK: sadece lat/lng'i hala BOS olan satirlari doldurur.
    // NOT: bu koordinatlar kurumun GERCEK adresi degil, il merkezidir -
    // aynı ildeki tum bu sekilde doldurulmus kurumlar ayni noktada
    // gorunecek, mesafe siralamasi il-ici hassasiyette olmayacaktir.
    private function geoFillCityCentroid(): string
    {
        $centroids = config('turkiye_centroids', []);
        $rows = DB::table('facilities')
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->whereNull('facilities.deleted_at')
            ->where(function ($q) {
                $q->whereNull('facilities.lat')->orWhereNull('facilities.lng');
            })
            ->select('facilities.id', 'cities.name as city')
            ->get();

        $updated = 0;
        $noCentroid = [];

        foreach ($rows as $row) {
            $centroid = $centroids[$row->city] ?? null;
            if (! $centroid) {
                $noCentroid[$row->city] = ($noCentroid[$row->city] ?? 0) + 1;
                continue;
            }

            $affected = DB::table('facilities')
                ->where('id', $row->id)
                ->where(function ($q) {
                    $q->whereNull('lat')->orWhereNull('lng');
                })
                ->update(['lat' => $centroid[0], 'lng' => $centroid[1], 'updated_at' => now()]);

            $updated += $affected;
        }

        $out = "Il merkezine gore dolduruldu: {$updated}\n";
        if ($noCentroid) {
            $out .= "Eslesen il merkezi bulunamadi:\n";
            foreach ($noCentroid as $city => $count) {
                $out .= "  {$city}: {$count}\n";
            }
        }

        return $out;
    }

    // 28 Temmuz 2026: "Canli API" ozelligini aktif etmeden once sunucuda
    // gercekten neyin mumkun oldugunu tespit eder - proc_open kapaliysa
    // Python kurulu olsa bile GoogleMapsDataExtractorService hicbir zaman
    // calisamaz, bu yuzden once bunu ayirt etmek gerekiyor.
    // 27 Agustos 2026: kullanicinin talebi - anlasmali kurumlara video
    // yukleme ozelligi eklemeden once sunucuda FFmpeg olup olmadigini
    // (goruntu sikistirmada kullanilan Imagick/GD'nin video karsiligi)
    // dogrulamak icin - bkz. OpsController::pythonCheck() ayni desen.
    // Sadece surum raporuyla yetinmez, KUCUK bir test videosunu GERCEKTEN
    // sikistirmayi dener (diagnostics-image'in gercek sikistirma testi
    // ile ayni titizlik).
    // 27 Agustos 2026: kullanicinin talebi - sunucuda sistem paketi olarak
    // FFmpeg kurulamiyor (paylasimli hosting, root yetkisi yok), ama
    // FFmpeg'in resmi wiki'sinde de onerilen, BAGIMSIZ (static, hicbir
    // sistem kutuphanesine ihtiyac duymayan) bir derlemesi indirilip
    // uygulamanin KENDI (web'den erisilemeyen) depolama klasorune
    // kurulabilir - bu root gerektirmez. Bu yol hem ffmpegCheck() hem
    // ffmpegInstall() tarafindan aday olarak taranir.
    // 27 Agustos 2026: aday listesi VideoCompressionService ile de
    // paylasilan tek kaynaga (FfmpegLocator) tasindi.
    private function ffmpegLocalInstallPath(): string
    {
        return \App\Services\FfmpegLocator::localInstallPath();
    }

    private function ffmpegCandidates(): array
    {
        return \App\Services\FfmpegLocator::candidates();
    }

    /**
     * Kucuk bir test videosu (renkli hareketli desen) uretip webp'ye degil
     * webm'e cevirmeyi dener - sadece "surum yazdirdi" degil, GERCEKTEN
     * sikistirma yapabildigini kanitlar (diagnostics-image'in gercek
     * sikistirma testiyle ayni titizlik).
     */
    private function ffmpegRealCompressionTest(string $binary): string
    {
        $tmpIn = tempnam(sys_get_temp_dir(), 'ffin_').'.mp4';
        $tmpOut = tempnam(sys_get_temp_dir(), 'ffout_').'.webm';
        @unlink($tmpOut);

        try {
            $gen = new Process([$binary, '-y', '-f', 'lavfi', '-i', 'testsrc=duration=1:size=320x240:rate=15', $tmpIn]);
            $gen->setTimeout(30);
            $gen->run();
            if (! $gen->isSuccessful() || ! is_file($tmpIn)) {
                return "Test videosu uretilemedi: " . trim($gen->getErrorOutput()) . "\n";
            }

            $convert = new Process([$binary, '-y', '-i', $tmpIn, '-vf', 'scale=320:240', '-b:v', '300k', $tmpOut]);
            $convert->setTimeout(30);
            $convert->run();

            if ($convert->isSuccessful() && is_file($tmpOut)) {
                return "Gercek sikistirma testi: BASARILI (" . filesize($tmpIn) . " bayt -> " . filesize($tmpOut) . " bayt, webm)\n";
            }

            return "Gercek sikistirma testi: BASARISIZ - " . trim($convert->getErrorOutput()) . "\n";
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
        }
    }

    /**
     * 3 Eylul 2026: kullanicinin bildirdigi canli hata - "Error while
     * opening encoder" - libx264 spesifik. ffmpegRealCompressionTest()
     * BILEREK/YANLISLIKLA webm/vp8'e cevirir, libx264'u HIC TEST ETMEZ -
     * bu yuzden bu hata daha once hic yakalanmamis. Bu tanı, GERCEK
     * VideoCompressionService komutunun (portre 720x1280, asm=0) yani
     * sirada birkac degisken (thread sayisi, olcekleme filtresiz) ile
     * dogrudan sunucuda test edip HANGISININ calistigini gosterir - tahmin
     * degil, kesin kanit.
     */
    private function ffmpegX264Diagnose(): string
    {
        $ffmpeg = \App\Services\FfmpegLocator::resolve();
        if (! $ffmpeg) {
            return "HATA: hicbir ffmpeg ikili dosyasi bulunamadi.\n";
        }

        $out = "ffmpeg: {$ffmpeg}\n\n";

        $nproc = new Process(['nproc']);
        $nproc->run();
        $out .= "nproc: " . trim($nproc->getOutput()) . "\n";

        $ulimit = new Process(['sh', '-c', 'ulimit -a']);
        $ulimit->run();
        $out .= "ulimit -a:\n" . trim($ulimit->getOutput() . $ulimit->getErrorOutput()) . "\n\n";

        // Gercek prodüksiyon senaryosuyla birebir ayni: 720x1280 portre.
        $tmpIn = tempnam(sys_get_temp_dir(), 'x264in_').'.mp4';
        // 3 Eylul 2026: kok neden bulundu (bkz. bu metodun asagisindaki
        // varyant listesi) - x264 nproc=40'a gore otomatik thread acmaya
        // calisiyor, ulimit -v sinirini asip "malloc failed" veriyordu.
        // Test girdisi uretimi de ayni hataya dusmesin diye threads=1.
        $gen = new Process([$ffmpeg, '-y', '-threads', '1', '-f', 'lavfi', '-i', 'testsrc=duration=3:size=720x1280:rate=30', '-f', 'lavfi', '-i', 'sine=frequency=1000:duration=3', '-c:v', 'libx264', '-x264-params', 'threads=1', '-c:a', 'aac', '-shortest', $tmpIn]);
        $gen->setTimeout(30);
        $gen->run();
        if (! $gen->isSuccessful() || ! is_file($tmpIn)) {
            return $out . "Test girdi videosu uretilemedi (ham libx264 encode bile basarisiz): " . trim($gen->getErrorOutput()) . "\n";
        }
        $out .= "Test girdi videosu (720x1280, libx264 ile) BASARIYLA uretildi - demek ki libx264 temelde CALISIYOR.\n\n";

        $variants = [
            'A) VideoCompressionService ile BIREBIR AYNI (scale + asm=0)' => ['-vf', 'scale=720:-2:force_original_aspect_ratio=decrease', '-c:v', 'libx264', '-preset', 'medium', '-crf', '30', '-x264-params', 'asm=0', '-c:a', 'aac', '-b:a', '96k', '-ac', '2', '-movflags', '+faststart'],
            'B) scale filtresi OLMADAN (asm=0 korunuyor)' => ['-c:v', 'libx264', '-preset', 'medium', '-crf', '30', '-x264-params', 'asm=0', '-c:a', 'aac', '-b:a', '96k', '-ac', '2', '-movflags', '+faststart'],
            'C) threads=1 (hem ffmpeg hem x264) EKLENEREK' => ['-threads', '1', '-vf', 'scale=720:-2:force_original_aspect_ratio=decrease', '-c:v', 'libx264', '-preset', 'medium', '-crf', '30', '-x264-params', 'asm=0:threads=1', '-c:a', 'aac', '-b:a', '96k', '-ac', '2', '-movflags', '+faststart'],
            'D) asm/threads HIC belirtilmeden (varsayilan)' => ['-vf', 'scale=720:-2:force_original_aspect_ratio=decrease', '-c:v', 'libx264', '-preset', 'medium', '-crf', '30', '-c:a', 'aac', '-b:a', '96k', '-ac', '2', '-movflags', '+faststart'],
            'E) preset ultrafast + threads=1' => ['-threads', '1', '-vf', 'scale=720:-2:force_original_aspect_ratio=decrease', '-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '30', '-x264-params', 'threads=1', '-c:a', 'aac', '-b:a', '96k', '-ac', '2', '-movflags', '+faststart'],
        ];

        foreach ($variants as $label => $args) {
            $tmpOut = tempnam(sys_get_temp_dir(), 'x264out_').'.mp4';
            @unlink($tmpOut);
            $cmd = array_merge([$ffmpeg, '-y', '-i', $tmpIn], $args, [$tmpOut]);
            $convert = new Process($cmd);
            $convert->setTimeout(60);
            $convert->run();

            if ($convert->isSuccessful() && is_file($tmpOut) && filesize($tmpOut) > 500) {
                $out .= "{$label}: BASARILI (" . filesize($tmpOut) . " bayt)\n";
            } else {
                $errTail = mb_substr(trim($convert->getErrorOutput()), -600);
                $out .= "{$label}: BASARISIZ\n    ...{$errTail}\n";
            }
            @unlink($tmpOut);
        }

        @unlink($tmpIn);

        return $out;
    }

    private function ffmpegCheck(): string
    {
        $out = '';
        $out .= "proc_open mevcut mu: " . (function_exists('proc_open') ? 'EVET' : 'HAYIR') . "\n\n";

        $found = null;
        foreach ($this->ffmpegCandidates() as $binary) {
            try {
                $probe = new Process([$binary, '-version']);
                $probe->run();
                if ($probe->isSuccessful()) {
                    $out .= "{$binary}: BULUNDU - " . trim(strtok($probe->getOutput(), "\n")) . "\n";
                    $found ??= $binary;
                }
            } catch (\Throwable $e) {
                // sessizce atla, sadece bulunanlari raporla
            }
        }

        if (! $found) {
            $out .= "(hicbir ffmpeg ikili dosyasi bulunamadi - /_ops/ffmpeg-install ile bagimsiz bir surum kurulabilir)\n";

            return $out;
        }

        return $out . "\n" . $this->ffmpegRealCompressionTest($found);
    }

    /**
     * FFmpeg'in resmi wiki'de de onerilen, guvenilir bir kaynaktan
     * (johnvansickle.com static builds) BAGIMSIZ derlemesini indirip
     * uygulamanin kendi (web'den erisilemeyen) depolama klasorune kurar.
     * Idempotent - zaten kuruluysa tekrar indirmez, sadece dogrular.
     */
    private function ffmpegInstall(): string
    {
        $localPath = $this->ffmpegLocalInstallPath();

        if (is_file($localPath) && is_executable($localPath)) {
            return "Zaten kurulu: {$localPath}\n\n" . $this->ffmpegRealCompressionTest($localPath);
        }

        @ini_set('memory_limit', '512M');

        $dir = dirname($localPath);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return "HATA: {$dir} klasoru olusturulamadi (yazma izni olmayabilir).\n";
        }

        $tarProbe = new Process(['tar', '--version']);
        $tarProbe->run();
        if (! $tarProbe->isSuccessful()) {
            return "HATA: 'tar' komutu bulunamadi, indirilen arsiv acilamaz.\n";
        }

        $url = 'https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz';
        $tmpArchive = tempnam(sys_get_temp_dir(), 'ffdl_').'.tar.xz';
        $extractDir = null;

        try {
            $out = "Indiriliyor: {$url}\n";

            // Buyuk dosyayi (~80MB) tek seferde bellege almadan, akis
            // halinde diske yazar - dusuk memory_limit'li paylasimli
            // hosting'te bellek tasmasini onlemek icin.
            $source = @fopen($url, 'r');
            if ($source === false) {
                return $out . "HATA: dosya indirilemedi (allow_url_fopen kapali olabilir ya da baglanti engellendi).\n";
            }
            $dest = fopen($tmpArchive, 'w');
            stream_copy_to_stream($source, $dest);
            fclose($source);
            fclose($dest);

            if (! is_file($tmpArchive) || filesize($tmpArchive) < 1000) {
                return $out . "HATA: indirilen dosya gecersiz/cok kucuk (" . (is_file($tmpArchive) ? filesize($tmpArchive) : 0) . " bayt).\n";
            }
            $out .= "Indirildi: " . filesize($tmpArchive) . " bayt\n";

            $extractDir = sys_get_temp_dir().'/ffext_'.uniqid();
            mkdir($extractDir, 0755, true);

            $extract = new Process(['tar', '-xf', $tmpArchive, '-C', $extractDir]);
            $extract->setTimeout(90);
            $extract->run();

            if (! $extract->isSuccessful()) {
                // 27 Agustos 2026: kullanicinin bildirdigi gercek durum -
                // canli sunucuda 'tar' var ama '.xz' katmanini acacak 'xz'
                // ikili dosyasi yok ("xz: Cannot exec: No such file or
                // directory"). Python'un standart kutuphanesindeki lzma
                // modulu (3.3+, sunucuda dogrulanan TUM surumlerde var) ile
                // once .xz katmanini duz .tar'a cevirip, SONRA xz'siz tar
                // ile acmayi dener - ikinci bir sistem paketine ihtiyac
                // duymadan ayni sonuca ulasir.
                $tarPath = sys_get_temp_dir().'/ffconv_'.uniqid().'.tar';
                $decompressed = false;
                foreach (['python3', '/opt/alt/python312/bin/python3', '/opt/alt/python311/bin/python3', '/opt/alt/python310/bin/python3', '/opt/alt/python39/bin/python3', '/opt/alt/python38/bin/python3', 'python'] as $py) {
                    $script = 'import lzma,shutil,sys; shutil.copyfileobj(lzma.open(sys.argv[1],"rb"), open(sys.argv[2],"wb"))';
                    $lzma = new Process([$py, '-c', $script, $tmpArchive, $tarPath]);
                    $lzma->setTimeout(60);
                    $lzma->run();
                    if ($lzma->isSuccessful() && is_file($tarPath) && filesize($tarPath) > 1000) {
                        $out .= "'xz' ikili dosyasi yoktu, '{$py}' (lzma modulu) ile acildi.\n";
                        $decompressed = true;
                        break;
                    }
                }

                if (! $decompressed) {
                    return $out . "HATA: arsiv acilamadi (tar: " . trim($extract->getErrorOutput()) . ") ve Python lzma ile de acilamadi.\n";
                }

                $extract = new Process(['tar', '-xf', $tarPath, '-C', $extractDir]);
                $extract->setTimeout(90);
                $extract->run();
                @unlink($tarPath);

                if (! $extract->isSuccessful()) {
                    return $out . "HATA: Python ile acilan .tar dosyasi da tar ile cikartilamadi - " . trim($extract->getErrorOutput()) . "\n";
                }
            }

            $matches = glob($extractDir.'/*/ffmpeg');
            if (empty($matches)) {
                return $out . "HATA: acilan arsivde ffmpeg ikili dosyasi bulunamadi.\n";
            }

            if (! copy($matches[0], $localPath)) {
                return $out . "HATA: {$localPath} konumuna kopyalanamadi (yazma izni olmayabilir).\n";
            }
            chmod($localPath, 0755);
            $out .= "Kuruldu: {$localPath}\n\n";

            return $out . $this->ffmpegRealCompressionTest($localPath);
        } finally {
            @unlink($tmpArchive);
            if ($extractDir && is_dir($extractDir)) {
                (new Process(['rm', '-rf', $extractDir]))->run();
            }
        }
    }

    // 28 Agustos 2026: kullanicinin canli aldigi "No space left on device"
    // MySQL kritik hata maili uzerine acilen eklendi. tmp_table diskini
    // dolduran seyin MySQL'in kendi sunucusu mu yoksa BU hesabin paylasimli
    // diski mi oldugunu ayirt eder, ayrica bu oturumda eklenen video
    // ozelliginin (VideoCompressionService, qa-video-upload-test vb.) temp
    // dosya biriktirip biriktirmedigini GERCEK boyutlarla gosterir.
    private function diskUsage(): string
    {
        $out = '';

        $paths = [
            'storage_path() (uygulama yazma alani)' => storage_path(),
            'sys_get_temp_dir() (PHP/OS gecici alan)' => sys_get_temp_dir(),
            '/tmp (MySQL tmpdir ile ayni mi kontrolu)' => '/tmp',
        ];

        foreach ($paths as $label => $dir) {
            if (! is_dir($dir)) {
                $out .= "{$label} [{$dir}]: dizin yok/erisilemiyor\n";

                continue;
            }
            $free = disk_free_space($dir);
            $total = disk_total_space($dir);
            $usedPct = ($free !== false && $total !== false && $total > 0) ? round((1 - $free / $total) * 100, 1) : null;
            $out .= "{$label} [{$dir}]:\n";
            $out .= '  bos: '.($free !== false ? number_format($free / 1024 / 1024 / 1024, 2).' GB' : 'okunamadi');
            $out .= ' / toplam: '.($total !== false ? number_format($total / 1024 / 1024 / 1024, 2).' GB' : 'okunamadi');
            $out .= $usedPct !== null ? " (%{$usedPct} dolu)\n" : "\n";
        }

        $out .= "\n--- Bu oturumda eklenen video ozelliginin biriktirdigi dosyalar ---\n";
        $scanDirs = [
            'storage/framework/testing/files (test video/gorsel dosyalari)' => storage_path('framework/testing/files'),
            'storage/app/private/bin (ffmpeg statik binary)' => storage_path('app/private/bin'),
            'storage/app/public/facilities/videos (sikistirilmis kurum videolari)' => storage_path('app/public/facilities/videos'),
        ];

        foreach ($scanDirs as $label => $dir) {
            if (! is_dir($dir)) {
                $out .= "{$label}: yok\n";

                continue;
            }
            $files = glob(rtrim($dir, '/').'/*') ?: [];
            $totalSize = 0;
            foreach ($files as $f) {
                if (is_file($f)) {
                    $totalSize += filesize($f);
                }
            }
            $out .= "{$label}: ".count($files)." dosya, toplam ".number_format($totalSize / 1024 / 1024, 2)." MB\n";
        }

        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            $out .= "\nstorage/logs/laravel.log boyutu: ".number_format(File::size($logPath) / 1024 / 1024, 2)." MB\n";
        }

        return $out;
    }

    private function pythonCheck(): string
    {
        $out = '';
        $disabled = (string) ini_get('disable_functions');
        $out .= "disable_functions: " . ($disabled !== '' ? $disabled : '(bos)') . "\n";
        $out .= "proc_open mevcut mu: " . (function_exists('proc_open') ? 'EVET' : 'HAYIR') . "\n\n";

        $candidates = [
            'python3', 'python', 'python3.8', 'python3.9', 'python3.10', 'python3.11', 'python3.12',
            '/opt/alt/python38/bin/python3', '/opt/alt/python39/bin/python3', '/opt/alt/python310/bin/python3',
            '/opt/alt/python311/bin/python3', '/opt/alt/python312/bin/python3',
            '/usr/local/bin/python3.8', '/usr/local/bin/python3.11',
        ];
        foreach ($candidates as $binary) {
            try {
                $probe = new Process([$binary, '--version']);
                $probe->run();
                if ($probe->isSuccessful()) {
                    $out .= "{$binary}: BULUNDU - " . trim($probe->getOutput() . $probe->getErrorOutput()) . "\n";
                }
            } catch (\Throwable $e) {
                // sessizce atla, sadece bulunanlari raporla
            }
        }
        $out .= "(yukarida listelenmeyenler bulunamadi)\n\n";

        foreach (['pip3', 'pip'] as $binary) {
            try {
                $probe = new Process([$binary, '--version']);
                $probe->run();
                $out .= "{$binary}: " . ($probe->isSuccessful() ? 'BULUNDU - ' . trim($probe->getOutput()) : 'calistirilamadi/bulunamadi') . "\n";
            } catch (\Throwable $e) {
                $out .= "{$binary}: HATA - " . $e->getMessage() . "\n";
            }
        }

        return $out;
    }

    // 28 Temmuz 2026: Kurum Davetleri ekraninda bir kategoriye ait kurumlarin
    // eksik/hic gorunmemesi sikayeti uzerine - tum kategorileri, ulke
    // genelinde kac kuruma bagli olduklarini ve bunlarin kacinin ozel/vakif
    // (davet edilebilir) oldugunu listeler. Amac: ayni gercek kategoriye
    // (ör. "Huzurevi" ve "Yasli Bakim Evi") karsilik gelen birden fazla
    // FacilityCategory satiri olup olmadigini gormek.
    private function categoryAudit(): string
    {
        $rows = DB::table('facility_categories as fc')
            ->leftJoin('facilities as f', function ($j) {
                $j->on('f.facility_category_id', '=', 'fc.id')->whereNull('f.deleted_at');
            })
            ->selectRaw('fc.id, fc.name, fc.slug, fc.brand_scope,
                count(f.id) as toplam,
                sum(case when f.ownership_type in (\'ozel\',\'vakif\') then 1 else 0 end) as ozel_vakif,
                sum(case when f.ownership_type in (\'ozel\',\'vakif\') and f.invitation_status = \'not_started\' then 1 else 0 end) as davet_bekleyen')
            ->groupBy('fc.id', 'fc.name', 'fc.slug', 'fc.brand_scope')
            ->orderBy('fc.name')
            ->get();

        $out = sprintf("%-4s %-45s %-30s %-10s %8s %10s %10s\n", 'ID', 'Ad', 'Slug', 'Marka', 'Toplam', 'OzelVakif', 'DavetBekl');
        foreach ($rows as $r) {
            $out .= sprintf("%-4d %-45s %-30s %-10s %8d %10d %10d\n", $r->id, mb_substr($r->name, 0, 45), $r->slug, $r->brand_scope, $r->toplam, $r->ozel_vakif, $r->davet_bekleyen);
        }

        return $out;
    }

    // Belirli bir kategori icin (query: category_slug) sehir bazinda kurum
    // sayisi doker - bir kategori/sehir kombinasyonunda beklenenden az kurum
    // cikip cikmadigini (ör. baska bir "kardes" kategoriye dagilmis olabilir)
    // gormek icin.
    private function categoryAuditCity(Request $request): string
    {
        $slug = (string) $request->query('category_slug', '');
        if ($slug === '') {
            return 'HATA: category_slug parametresi gerekli';
        }

        $rows = DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->join('facility_categories as fc', 'fc.id', '=', 'f.facility_category_id')
            ->where('fc.slug', $slug)
            ->whereNull('f.deleted_at')
            ->selectRaw('c.name as sehir, count(*) as toplam,
                sum(case when f.ownership_type in (\'ozel\',\'vakif\') then 1 else 0 end) as ozel_vakif,
                sum(case when f.ownership_type in (\'ozel\',\'vakif\') and f.invitation_status = \'not_started\' then 1 else 0 end) as davet_bekleyen')
            ->groupBy('c.name')
            ->orderByDesc('toplam')
            ->get();

        $out = "Kategori: {$slug}\n";
        $out .= sprintf("%-25s %8s %10s %10s\n", 'Sehir', 'Toplam', 'OzelVakif', 'DavetBekl');
        foreach ($rows as $r) {
            $out .= sprintf("%-25s %8d %10d %10d\n", $r->sehir, $r->toplam, $r->ozel_vakif, $r->davet_bekleyen);
        }

        return $out;
    }

    // 28 Temmuz 2026: bazi kurumlarin Kurum Davetleri ekraninin HICBIR
    // sekmesinde gorunmedigi sikayeti - FacilityInvitationController
    // GROUPS listesindeki durumlardan HICBIRINE uymayan (ör. NULL veya
    // GROUPS'ta olmayan garip bir deger) invitation_status'lu kurumlar
    // sessizce her sekmeden kaybolur. Bunu ulke genelinde tespit eder.
    private function invitationStatusAudit(): string
    {
        $knownStatuses = ['not_started', 'opened', 'sent', 'claimed', 'approved', 'unreachable', 'wrong_number', 'landline_only', 'contact_missing', 'do_not_contact', 'excluded'];

        $rows = DB::table('facilities')
            ->whereIn('ownership_type', ['ozel', 'vakif'])
            ->whereNull('deleted_at')
            ->selectRaw('invitation_status, count(*) as adet')
            ->groupBy('invitation_status')
            ->orderByDesc('adet')
            ->get();

        $out = "Ozel/vakif kurumlarda invitation_status dagilimi:\n";
        $ghostTotal = 0;
        foreach ($rows as $r) {
            $isGhost = ! in_array($r->invitation_status, $knownStatuses, true);
            $flag = $isGhost ? '  <-- HICBIR SEKMEDE GORUNMEZ' : '';
            $out .= sprintf("  %-20s %6d%s\n", $r->invitation_status ?? '(NULL)', $r->adet, $flag);
            if ($isGhost) {
                $ghostTotal += $r->adet;
            }
        }
        $out .= "\nToplam 'hayalet' (hicbir sekmede gorunmeyen) kurum: {$ghostTotal}\n";

        if ($ghostTotal > 0) {
            $sample = DB::table('facilities')
                ->whereIn('ownership_type', ['ozel', 'vakif'])
                ->whereNull('deleted_at')
                ->where(function ($q) use ($knownStatuses) {
                    $q->whereNotIn('invitation_status', $knownStatuses)->orWhereNull('invitation_status');
                })
                ->limit(10)
                ->pluck('name');
            $out .= "Ornekler: " . $sample->implode(', ') . "\n";
        }

        return $out;
    }

    // GUVENLIK: sadece invitation_status'u GROUPS'ta hic tanimli olmayan
    // (NULL dahil) ozel/vakif kurumlari 'not_started'a ceker - baska hicbir
    // alani degistirmez, zaten dogru/bilinen bir durumu olanlara dokunmaz.
    private function invitationStatusFix(): string
    {
        $knownStatuses = ['not_started', 'opened', 'sent', 'claimed', 'approved', 'unreachable', 'wrong_number', 'landline_only', 'contact_missing', 'do_not_contact', 'excluded'];

        $affected = DB::table('facilities')
            ->whereIn('ownership_type', ['ozel', 'vakif'])
            ->whereNull('deleted_at')
            ->where(function ($q) use ($knownStatuses) {
                $q->whereNotIn('invitation_status', $knownStatuses)->orWhereNull('invitation_status');
            })
            ->update(['invitation_status' => 'not_started', 'updated_at' => now()]);

        return "Duzeltilen (invitation_status = 'not_started' yapilan) kurum: {$affected}";
    }

    private function invitationDetail(Request $request): string
    {
        $citySlug = (string) $request->query('city_slug', '');
        $categorySlug = (string) $request->query('category_slug', '');

        $rows = DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->join('facility_categories as fc', 'fc.id', '=', 'f.facility_category_id')
            ->when($citySlug, fn ($q) => $q->where('c.slug', $citySlug))
            ->when($categorySlug, fn ($q) => $q->where('fc.slug', $categorySlug))
            ->whereNull('f.deleted_at')
            ->select('f.id', 'f.name', 'f.ownership_type', 'f.phone', 'f.phone_type', 'f.invitation_status', 'f.is_claimed', 'f.is_published')
            ->get();

        $out = '';
        foreach ($rows as $r) {
            $out .= sprintf(
                "#%d %s | sahiplik=%s | tel=%s (%s) | davet_durumu=%s | sahiplenildi=%s | yayinda=%s\n",
                $r->id, $r->name, $r->ownership_type, $r->phone ?: '(yok)', $r->phone_type ?: '(yok)',
                $r->invitation_status ?? '(NULL)', $r->is_claimed ? 'evet' : 'hayir', $r->is_published ? 'evet' : 'hayir'
            );
        }

        return $out ?: 'Kayit bulunamadi.';
    }

    // 28 Temmuz 2026: kullanici "gercekte cep telefonu olan kurumlar sabit
    // hatli/telefonsuz gorunuyor" dedi. Ihracat aninda phone_type BIR KERE
    // hesaplanip DB'ye yaziliyor (bkz. DataExtractorImportService::import,
    // DataImportRowApprovalService) - classify_phone_type() fonksiyonu o
    // tarihten beri degistiyse veya kaynak numara formati (bosluk/parantez/
    // ulke kodu ikilemesi vb.) beklenmedik bir sekilde geldiyse, o zamanki
    // hesaplama yanlis olabilir ve bir daha kendiliginden duzelmez. Bu uc
    // GUNCEL classify_phone_type() mantigini her ozel/vakif kuruma yeniden
    // uygulayip DB'deki mevcut phone_type ile karsilastirir.
    private function phoneTypeAudit(Request $request): string
    {
        $citySlug = (string) $request->query('city_slug', '') ?: null;
        $result = app(\App\Services\DataQualityService::class)->phoneTypeAudit($citySlug);

        $lines = array_map(
            fn ($m) => "#{$m['facility']->id} {$m['facility']->name} | tel={$m['facility']->phone} | kayitli={$m['stored']} -> olmasi_gereken={$m['computed']} | davet_durumu={$m['facility']->invitation_status}",
            $result['mismatches']
        );

        $out = "Kontrol edilen kurum: {$result['checked']}\n";
        $out .= "Uyumsuz (yanlis siniflandirilmis) kurum: " . count($lines) . "\n\n";
        $out .= implode("\n", array_slice($lines, 0, 50));
        if (count($lines) > 50) {
            $out .= "\n... ve " . (count($lines) - 50) . " tane daha";
        }

        return $out;
    }

    // GUVENLIK: sadece phone_type gercekten yanlis hesaplanmis olanlari
    // duzeltir. invitation_status'u SADECE hala ilk (otomatik) durumundaysa
    // (not_started/landline_only/contact_missing) yeni phone_type'a gore
    // gunceller - davet sureci ilerlemis (opened/sent/claimed/onaylanmis/
    // istemiyor/ulasilamadi/yanlis-numara/haric-tutulmus) kurumlara asla
    // dokunmaz, o gecmisi kaybetmez.
    private function phoneTypeFix(Request $request): string
    {
        $citySlug = (string) $request->query('city_slug', '') ?: null;
        $result = app(\App\Services\DataQualityService::class)->phoneTypeFix($citySlug);

        return "Duzeltilen phone_type: {$result['fixedPhoneType']}\nBirlikte duzeltilen davet_durumu: {$result['fixedStatus']} (kalanlarda davet sureci zaten ilerlemisti, dokunulmadi)";
    }

    // 28 Temmuz 2026: "BURSA SEFKAT HUZUREVI..." aslinda ozel isletme ama
    // ownership_type='kamu' kayitliydi (muhtemelen elle admin formundan
    // yanlislikla secilmis, classify_facility_ownership_type() bu ismi
    // 'ozel' donduruyor - kod hatasi degil, veri girisi hatasi). Ayni
    // yanlislik baska kurumlarda da olabilir mi diye 'kamu' isaretli ama
    // isminde 'ozel' gecen kurumlari listeler - "ozel egitim" gibi
    // KAMU'ya ait gercek meslek okullarini da yakalayabilir (Turkce'de
    // "ozel" hem "private" hem "special" anlamina gelir), bu yuzden
    // OTOMATIK duzeltme yapmaz, sadece insan gozden gecirsin diye listeler.
    private function ownershipAudit(): string
    {
        $rows = app(\App\Services\DataQualityService::class)->ownershipAudit();

        $out = "'kamu' isaretli ama isminde 'ozel' gecen kurumlar (insan gozuyle kontrol edilmeli): " . count($rows) . "\n\n";
        foreach ($rows as $r) {
            $out .= "#{$r->id} {$r->name} ({$r->sehir})\n";
        }

        return $out;
    }

    // Tek bir kurumun ownership_type'ini elle duzeltmek icin (id ve type
    // query parametreleri). Toplu/otomatik degil - her kayit ozel bulunmus
    // (bkz. ownership-audit veya kullanicidan gelen bilgi) ve tek tek
    // onaylanarak burdan duzeltilir.
    private function ownershipFix(Request $request): string
    {
        $id = (int) $request->query('id', 0);
        $type = (string) $request->query('type', '');

        $result = app(\App\Services\DataQualityService::class)->ownershipFix($id, $type);

        return ($result['ok'] ? 'OK: ' : 'HATA: ') . $result['message'];
    }

    // 28 Temmuz 2026: kullanicinin ekran goruntusunde gosterdigi gibi -
    // "Yasli Bakim Evi"/"Huzurevi" kategorisinde cocuk/ozel egitim/
    // rehabilitasyon/psikolojik danismanlik isletmeleri var. Bunlar
    // muhtemelen bir toplu Excel ice aktarmada (Veri Cekici tum dosyaya
    // TEK bir kategori atiyor, satir satir degil) yanlis kategori secilerek
    // eklenmis (ozellikle Sakarya bolgesinde yogun). Isim uzerinden
    // ORDER'LI anahtar kelime eslesmesiyle gercek kategoriyi tahmin eder;
    // "ozel guvenlik egitimi", "psikolog", "saglik kabini" gibi hicbir
    // bakim kategorisine net oturmayanlari OTOMATIK TASIMAZ, sadece
    // "gozden gecir/muhtemelen bakim kurumu degil" diye isaretler.
    // 14 Agustos 2026: bu uctaki kategori-tahmin/isim-temizleme mantigi
    // App\Services\DataQualityService::class icine tasindi - admin
    // panelindeki "Veri Denetimi" ekrani da AYNI servisi kullanir, iki
    // yerde ayri ayri (ve zamanla birbirinden sapabilecek) kopya durmaz.
    private function miscategoryScan(Request $request): string
    {
        $result = app(\App\Services\DataQualityService::class)->miscategoryScan();

        $out = "Taranan (Huzurevi/Yasli Bakim Evi kategorisindeki) kurum: {$result['scanned']}\n";
        $out .= "Gercek kategorisi baska oldugu net olan (otomatik tasinabilir): " . count($result['reassignable']) . "\n";
        $out .= "Bakim kurumu olmadigi neredeyse kesin (elle karar verilmeli): " . count($result['nonCare']) . "\n\n";

        $byTarget = [];
        foreach ($result['reassignable'] as $row) {
            $byTarget[$row['target']][] = "#{$row['facility']->id} {$row['facility']->name} ({$row['facility']->sehir})";
        }
        foreach ($byTarget as $target => $items) {
            $out .= "--- -> {$target} (" . count($items) . " kurum) ---\n";
            foreach (array_slice($items, 0, 15) as $line) {
                $out .= "  {$line}\n";
            }
            if (count($items) > 15) {
                $out .= '  ... ve ' . (count($items) - 15) . " tane daha\n";
            }
        }

        if ($result['nonCare']) {
            $out .= "\n--- Muhtemelen bakim kurumu DEGIL (elle incelenmeli) ---\n";
            foreach (array_slice($result['nonCare'], 0, 20) as $r) {
                $out .= "  #{$r->id} {$r->name} ({$r->sehir})\n";
            }
            if (count($result['nonCare']) > 20) {
                $out .= '  ... ve ' . (count($result['nonCare']) - 20) . " tane daha\n";
            }
        }

        return $out;
    }

    // GUVENLIK: sadece guessRealCategory() ile NET bir hedef kategori
    // bulunan kurumlarin facility_category_id'sini degistirir - belirsiz/
    // "bakim kurumu degil" olarak isaretlenenlere DOKUNMAZ (onlar admin
    // panelinden elle karar verilip silinmeli/duzenlenmeli).
    private function miscategoryFix(Request $request): string
    {
        $result = app(\App\Services\DataQualityService::class)->miscategoryFix();

        $out = "Kategorisi duzeltilen toplam kurum: {$result['fixed']}\n";
        foreach ($result['byTarget'] as $slug => $n) {
            $out .= "  -> {$slug}: {$n}\n";
        }

        return $out;
    }

    // 28 Temmuz 2026: miscategory-scan'in "muhtemelen bakim kurumu DEGIL"
    // diye isaretledigi (psikolog/danismanlik/akademi gibi 7 kategoriden
    // hicbirine oturmayan) kurumlari - admin panelindeki "Sil" ile AYNI
    // yolu (FacilityArchiveService::archiveBeforeDelete + soft delete)
    // kullanarak kaldirir. Geri donusumlu: Cop Kutusu'ndan tek tikla geri
    // yuklenebilir. SADECE query'de verilen id listesini siler - toplu/
    // otomatik degil, her id elle secilip onaylanmis olmali.
    private function facilityRemove(Request $request): string
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids', ''))));
        if (! $ids) {
            return 'HATA: ids parametresi gerekli (virgulle ayrilmis id listesi)';
        }

        $archiveService = app(FacilityArchiveService::class);
        $out = '';

        foreach ($ids as $id) {
            $facility = Facility::find($id);
            if (! $facility) {
                $out .= "#{$id}: bulunamadi\n";
                continue;
            }

            $archivePath = $archiveService->archiveBeforeDelete($facility);
            \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);
            $name = $facility->name;
            $facility->delete();
            $out .= "#{$id} {$name}: silindi ve arsivlendi ({$archivePath})\n";
        }

        return $out;
    }

    // 28 Temmuz 2026: Kurum Davetleri ekranindaki ilce filtresi
    // ($query->where('district', $request->district)) facilities'in
    // DUZ METIN 'district' kolonuna gore filtreliyor - ama kurumun
    // gercek ilcesi asil 'district_id' (districts tablosuna FK) ile
    // taniniyor ve admin kurum formunda da o gosteriliyor. Bu iki alan
    // her zaman senkron degilse (ör. veri cekici/import district_id'yi
    // doldurup metin kolonunu bos birakmissa), ilce filtresi o kurumlari
    // SESSIZCE atlar - "kategori dogru ama ilce filtrelenince kurum
    // kayboluyor" sikayetinin bir baska olasi kaynagi. Bu uc iki alanin
    // ne kadar tutarsiz oldugunu olcer.
    private function districtAudit(Request $request): string
    {
        $citySlug = (string) $request->query('city_slug', '') ?: null;
        $result = app(\App\Services\DataQualityService::class)->districtAudit($citySlug);

        $examples = array_map(function ($row) {
            $f = $row['facility'];
            $text = trim((string) $f->district_text);
            $fk = trim((string) $f->district_fk);
            $desc = match ($row['reason']) {
                'sadece_fk' => "ilce sadece FK'de: '{$fk}', metin kolonu bos",
                default => "metin='{$text}' FK='{$fk}' UYUSMUYOR",
            };

            return "#{$f->id} {$f->name} ({$f->sehir}) -> {$desc}";
        }, $result['examples']);

        $out = "Kontrol edilen kurum: {$result['checked']}\n";
        $out .= "Tutarli (ikisi de ayni veya ikisi de bos, sorun yok): {$result['consistent']}\n";
        $out .= "Ikisi de bos (ilce hic girilmemis): {$result['bothEmpty']}\n";
        $out .= "SADECE FK'de ilce var, metin kolonu bos -> filtre bu kurumlari KACIRIR: {$result['onlyFkFilled']}\n";
        $out .= "Metin ve FK BIRBIRINDEN FARKLI -> filtre yanlis/eksik sonuc verir: {$result['mismatch']}\n\n";
        $out .= implode("\n", $examples);

        return $out;
    }

    // GUVENLIK: sadece 'district' metin kolonu BOS olup district_id (FK)
    // dolu olan kurumlarin metin kolonunu FK'deki gercek ilce adiyla
    // doldurur - var olan (dolu) bir metin degerini asla EZMEZ, o yuzden
    // yanlislikla dogru bir veriyi degistirme riski yok.
    private function districtFix(Request $request): string
    {
        $result = app(\App\Services\DataQualityService::class)->districtFix();

        return "Ilce metin kolonu FK'den dolduruldu: {$result['fixed']}";
    }

    // 12 Agustos 2026: platform denetiminde kurum isminin sonunda/basinda
    // gereksiz virgul veya bosluk, ya da kelimeler arasinda cift bosluk
    // birakan kayitlar goruldu (muhtemelen elle kayit/import sirasinda).
    // Sadece BICIMSEL temizlik yapar - ismin gercek metnini degistirmez.
    private function nameCleanupAudit(): string
    {
        $result = app(\App\Services\DataQualityService::class)->nameCleanupAudit();

        if (! $result['count']) {
            return 'Temizlik gerektiren kurum ismi bulunamadi.';
        }

        $examples = array_map(fn ($e) => "#{$e['id']} '{$e['old']}' -> '{$e['new']}'", $result['examples']);

        return 'Duzeltilecek kurum sayisi: '.$result['count']."\n\n".implode("\n", $examples);
    }

    private function nameCleanupFix(): string
    {
        $result = app(\App\Services\DataQualityService::class)->nameCleanupFix();

        return "Kurum ismi temizlendi: {$result['fixed']}";
    }

    // 28 Temmuz 2026: kamu/belediye kurumlarini TOPLU silmeden once
    // kullanicinin istegi uzerine %100 dogrulama - her kamu/belediye
    // isaretli kurumun ISMINE classify_facility_ownership_type() GUNCEL
    // mantigini yeniden uygular (ŞEFKAT ornegindeki gibi yanlislikla
    // kamu isaretlenmis olabilir). Sadece uyusmayanlari raporlar, hicbir
    // sey degistirmez/silmez.
    private function ownershipVerify(Request $request): string
    {
        $rows = DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->whereIn('f.ownership_type', ['kamu', 'belediye'])
            ->whereNull('f.deleted_at')
            ->select('f.id', 'f.name', 'f.ownership_type', 'c.name as sehir')
            ->get();

        $mismatches = [];
        foreach ($rows as $r) {
            $computed = classify_facility_ownership_type($r->name);
            if ($computed !== $r->ownership_type) {
                $mismatches[] = "#{$r->id} {$r->name} ({$r->sehir}) | kayitli={$r->ownership_type} -> isimden hesaplanan={$computed}";
            }
        }

        $out = "Kontrol edilen kamu/belediye kurum: {$rows->count()}\n";
        $out .= "Isimle uyusmayan (yanlislikla kamu/belediye isaretlenmis olabilir): " . count($mismatches) . "\n\n";
        $out .= implode("\n", $mismatches);

        return $out;
    }

    // 28 Temmuz 2026: ownership-verify'in bulgusu uzerine - 402 kamu/
    // belediye kurumun 400'u isme gore aslinda ozel/vakif cikti (buyuk
    // toplu import sirasinda sahiplik turu guvenilir hesaplanmamis).
    // Kullanicinin "hepsini sil" talebi bu yuzden DURDURULDU - bunun
    // yerine once etiketi guncel classify_facility_ownership_type()
    // mantigiyla dogru degere cekiyoruz. Gercekten kamu/belediye kalanlar
    // (isimden hesaplanan da kamu/belediye cikanlar) DEGISTIRILMEZ.
    private function ownershipFixBulk(Request $request): string
    {
        $rows = DB::table('facilities')
            ->whereIn('ownership_type', ['kamu', 'belediye'])
            ->whereNull('deleted_at')
            ->select('id', 'name', 'ownership_type', 'invitation_status')
            ->get();

        $fixed = 0;
        $remaining = [];

        foreach ($rows as $r) {
            $computed = classify_facility_ownership_type($r->name);
            if ($computed === $r->ownership_type) {
                $remaining[] = "#{$r->id} {$r->name} ({$r->ownership_type})";
                continue;
            }

            $update = ['ownership_type' => $computed, 'updated_at' => now()];
            // kamu/belediyeden ozel/vakif'a geciyorsa ve durumu hala
            // bos/tanimsizsa, artik davet edilebilir kategoriye giriyor -
            // telefon tipine gore ilk durumunu ata (invitation-status-fix
            // ile ayni mantik).
            if (in_array($computed, ['ozel', 'vakif'], true)) {
                $phone = DB::table('facilities')->where('id', $r->id)->value('phone');
                $phoneType = classify_phone_type($phone);
                $update['invitation_status'] = match ($phoneType) {
                    'mobile' => 'not_started',
                    'landline' => 'landline_only',
                    default => 'contact_missing',
                };
                $update['invitation_status_at'] = now();
            }

            DB::table('facilities')->where('id', $r->id)->update($update);
            $fixed++;
        }

        $out = "Sahiplik turu duzeltilen kurum: {$fixed}\n";
        $out .= 'Gercekten kamu/belediye kalan (isimden de dogrulanan): ' . count($remaining) . "\n\n";
        $out .= implode("\n", $remaining);

        return $out;
    }

    // GUVENLIK: sadece query'de belirtilen ownership_type degerlerine
    // (kamu/belediye) sahip kurumlari, FacilityArchiveService ile ayni
    // arsivleme+soft-delete akisini kullanarak kaldirir - Cop Kutusu'ndan
    // geri yuklenebilir. types=kamu,belediye seklinde virgullu liste alir.
    private function facilityRemoveByOwnership(Request $request): string
    {
        $types = array_filter(explode(',', (string) $request->query('types', '')));
        $allowed = ['kamu', 'belediye'];
        foreach ($types as $t) {
            if (! in_array($t, $allowed, true)) {
                return "HATA: gecersiz type '{$t}' (sadece kamu/belediye)";
            }
        }
        if (! $types) {
            return 'HATA: types parametresi gerekli (ör. types=kamu,belediye)';
        }

        $facilities = Facility::whereIn('ownership_type', $types)->get();
        $archiveService = app(FacilityArchiveService::class);
        $count = 0;

        foreach ($facilities as $facility) {
            $archiveService->archiveBeforeDelete($facility);
            \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);
            $facility->delete();
            $count++;
        }

        return "Silinen ve arsivlenen kurum: {$count} (types: " . implode(',', $types) . ')';
    }

    // 28 Temmuz 2026: kullanicinin istedigi uctan uca dogrulamanin bir
    // parcasi - sadece ->queue() ile mail gonderilmeye calisilirsa
    // Blade sablonundaki bir hata (ör. yanlis degisken adi) sadece
    // KUYRUK ISLENIRKEN patlar ve try/catch'e sessizce yutulup
    // Log::warning'e duser, hicbir yerde gorulmez. Bu uc TUM mail
    // siniflarini GERCEK veriyle ->render() eder (gondermez, sadece
    // HTML'e cevirir) - Blade hatasi varsa burada hemen patlar.
    private function mailRenderTest(): string
    {
        $out = '';
        $facility = Facility::whereNotNull('facility_category_id')->first();
        $claim = DB::table('facility_claims')->orderByDesc('id')->first();
        $registration = DB::table('facility_registrations')->orderByDesc('id')->first();
        $facilityUser = \App\Models\FacilityUser::first();
        $familyUser = \App\Models\FamilyUser::first();

        $cases = [];

        if ($facility) {
            $cases['FacilityClaimApprovedMail'] = fn () => new \App\Mail\FacilityClaimApprovedMail($facility, 'test@example.com', 'gecici-sifre-123', 'https://bakimevleri.com/kurum-panel/giris');
            $cases['FacilityRegistrationApprovedMail'] = fn () => new \App\Mail\FacilityRegistrationApprovedMail($facility, 'test@example.com', 'gecici-sifre-123', 'https://bakimevleri.com/kurum-panel/giris');
            $cases['FacilityWelcomeMail'] = fn () => new \App\Mail\FacilityWelcomeMail($facility, 'test@example.com', 'bakimevleri.com', 'https://bakimevleri.com/kurum-panel/giris');
        }
        if ($claim) {
            $claimModel = \App\Models\FacilityClaim::find($claim->id);
            if ($claimModel && $claimModel->facility) {
                $cases['FacilityClaimRejectedMail'] = fn () => new \App\Mail\FacilityClaimRejectedMail($claimModel);
            }
        }
        if ($registration) {
            $regModel = \App\Models\FacilityRegistration::find($registration->id);
            if ($regModel) {
                $cases['FacilityRegistrationRevisionRequestedMail'] = fn () => new \App\Mail\FacilityRegistrationRevisionRequestedMail($regModel, 'Test admin notu.', 'https://bakimevleri.com/kurum-kaydi/duzenle/xyz');
                $cases['FacilityRegistrationRejectedMail'] = fn () => new \App\Mail\FacilityRegistrationRejectedMail($regModel);
            }
        }
        if ($facilityUser) {
            $cases['FacilityEmailVerificationMail'] = fn () => new \App\Mail\FacilityEmailVerificationMail($facilityUser, 'https://bakimevleri.com/dogrula/xyz', 'bakimevleri.com');
            $cases['FacilityPasswordResetMail'] = fn () => new \App\Mail\FacilityPasswordResetMail($facilityUser, 'https://bakimevleri.com/sifre-sifirla/xyz', 'bakimevleri.com');
        }
        if ($familyUser) {
            $cases['FamilyEmailVerificationMail'] = fn () => new \App\Mail\FamilyEmailVerificationMail($familyUser, 'https://bakimevleri.com/dogrula/xyz', 'bakimevleri.com');
            $cases['FamilyPasswordResetMail'] = fn () => new \App\Mail\FamilyPasswordResetMail($familyUser, 'https://bakimevleri.com/sifre-sifirla/xyz', 'bakimevleri.com');
            $cases['FamilyWelcomeMail'] = fn () => new \App\Mail\FamilyWelcomeMail($familyUser, 'bakimevleri.com', 'https://bakimevleri.com/aile-paneli');
        }
        $visitRequest = \App\Models\VisitRequest::first();
        if ($visitRequest) {
            $cases['VisitRequestCancelledMail'] = fn () => new \App\Mail\VisitRequestCancelledMail($visitRequest);
        }
        $offerRequest = \App\Models\OfferRequest::first();
        if ($offerRequest) {
            $cases['OfferRequestClosedMail'] = fn () => new \App\Mail\OfferRequestClosedMail($offerRequest);
        }
        $contactMessage = \App\Models\ContactMessage::first();
        if ($contactMessage) {
            $cases['ContactMessageClosedMail'] = fn () => new \App\Mail\ContactMessageClosedMail($contactMessage);
        }

        $cases['NotificationMail'] = fn () => new \App\Mail\NotificationMail('Test başlık', 'Test gövde metni', 'https://bakimevleri.com/');
        $cases['AdminLoginCodeMail'] = fn () => new \App\Mail\AdminLoginCodeMail('123456');
        $cases['BackupCreatedMail'] = fn () => new \App\Mail\BackupCreatedMail('yedek-test.sql.gz', '1.2 MB');

        foreach ($cases as $name => $factory) {
            try {
                $html = $factory()->render();
                $out .= "{$name}: OK (" . strlen($html) . " bayt HTML)\n";
            } catch (\Throwable $e) {
                $out .= "{$name}: HATA - " . get_class($e) . ': ' . $e->getMessage() . "\n";
            }
        }

        return $out;
    }

    // 28 Temmuz 2026: kullanicinin istedigi uctan uca canli test icin -
    // gercek HTTP akisinda kullanilacak, mevcut, uygun kurumlari secer.
    private function qaPickFacilities(Request $request): string
    {
        $city = DB::table('cities')->where('slug', 'bursa')->first();
        $category = DB::table('facility_categories')->where('slug', 'huzurevi')->first();
        $prefix = "bursa city_id={$city->id}, huzurevi category_id={$category->id}\n\n";

        $claimed = DB::table('facilities')
            ->where('is_published', true)
            ->where('is_claimed', true)
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->limit(6)
            ->pluck('slug', 'id');

        $unclaimed = DB::table('facilities')
            ->where('is_published', true)
            ->where('is_claimed', false)
            ->whereIn('ownership_type', ['ozel', 'vakif'])
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->limit(1)
            ->first();

        $out = $prefix . "Sahiplenilmis+yayinda (teklif talebi testi icin):\n";
        foreach ($claimed as $id => $slug) {
            $out .= "  id={$id} slug={$slug}\n";
        }
        $out .= "\nSahiplenilmemis+yayinda (sahiplenme basvurusu testi icin):\n";
        if ($unclaimed) {
            $out .= "  id={$unclaimed->id} slug={$unclaimed->slug}\n";
        } else {
            $out .= "  (bulunamadi)\n";
        }

        return $out;
    }

    // Test verisini bulmak icin isim/eposta on eki (ör. "QATEST") kabul
    // eder, ilgili tum tablolardaki kayitlari ve kuyruk/hata durumunu
    // ozetler - hicbir sey silmez/degistirmez, sadece raporlar.
    private function qaCheck(Request $request): string
    {
        $prefix = (string) $request->query('prefix', 'QATEST');
        $out = "Onek: {$prefix}\n\n";

        $family = DB::table('family_users')->where('name', 'like', "{$prefix}%")->orWhere('email', 'like', "%{$prefix}%")->get();
        $out .= "family_users: {$family->count()}\n";
        foreach ($family as $f) {
            $out .= "  #{$f->id} {$f->name} <{$f->email}> dogrulanmis=" . ($f->email_verified_at ? 'evet' : 'hayir') . "\n";
        }

        $offerRequests = DB::table('offer_requests')->where('full_name', 'like', "{$prefix}%")->get();
        $out .= "\noffer_requests: {$offerRequests->count()}\n";
        foreach ($offerRequests as $o) {
            $out .= "  #{$o->id} facility_id={$o->facility_id} batch={$o->batch_id} status={$o->status}\n";
        }

        $offerRequestIds = $offerRequests->pluck('id');
        $quotes = DB::table('quotes')->whereIn('offer_request_id', $offerRequestIds)->get();
        $out .= "\nquotes: {$quotes->count()}\n";
        foreach ($quotes as $q) {
            $out .= "  quote_id={$q->id} offer_request_id={$q->offer_request_id} facility_id={$q->facility_id} status={$q->status} price={$q->price}\n";
        }

        $claims = DB::table('facility_claims')->where('applicant_name', 'like', "{$prefix}%")->get();
        $out .= "\nfacility_claims: {$claims->count()}\n";
        foreach ($claims as $c) {
            $out .= "  #{$c->id} facility_id={$c->facility_id} status={$c->status}\n";
        }

        $registrations = DB::table('facility_registrations')->where('applicant_name', 'like', "{$prefix}%")->orWhere('name', 'like', "{$prefix}%")->get();
        $out .= "\nfacility_registrations: {$registrations->count()}\n";
        foreach ($registrations as $r) {
            $out .= "  #{$r->id} {$r->name} <{$r->applicant_email}> status={$r->status}\n";
        }

        $topups = DB::table('wallet_topups')->whereIn('facility_id', [$request->query('fac1'), $request->query('fac2'), $request->query('fac3')])->get();
        if ($topups->isNotEmpty()) {
            $out .= "\nwallet_topups: {$topups->count()}\n";
            foreach ($topups as $t) {
                $out .= "  #{$t->id} facility_id={$t->facility_id} amount={$t->amount} status={$t->status}\n";
            }
        }

        $notifCount = DB::table('platform_notifications')->where('created_at', '>=', now()->subMinutes(30))->count();
        $out .= "\nSon 30 dakikada olusan platform_notifications: {$notifCount}\n";

        $jobs = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subMinutes(30))->count();
        $out .= "Kuyrukta bekleyen is: {$jobs}\n";
        $out .= "Son 30 dakikada basarisiz is: {$failed}\n";
        if ($failed > 0) {
            $out .= "--- basarisiz is ornekleri ---\n";
            foreach (DB::table('failed_jobs')->where('failed_at', '>=', now()->subMinutes(30))->limit(3)->get() as $fj) {
                $out .= mb_substr($fj->exception, 0, 400) . "\n---\n";
            }
        }

        return $out;
    }

    // 28 Temmuz 2026: uctan uca canli akis testi icin - GERCEK kurum
    // sahiplerini rahatsiz etmemek adina kendi "QATEST" kurumlarimizi
    // olusturur (yayinda + sahiplenilmis + bilinen sifreli facility_user).
    // Boylece teklif talebi / mesajlasma HTTP akislari gercek isletme
    // sahiplerine deger, sadece bu test kayitlarina gider. qa-teardown ile
    // tamamen temizlenir.
    private function qaSetup(Request $request): string
    {
        $city = DB::table('cities')->where('slug', 'bursa')->first();
        $category = DB::table('facility_categories')->where('slug', 'huzurevi')->first();
        abort_if(! $city || ! $category, 500, 'Test icin sehir/kategori bulunamadi');

        $password = \Illuminate\Support\Facades\Hash::make('QaTest12345!');
        $out = '';

        for ($i = 1; $i <= 3; $i++) {
            $slug = "qatest-kurum-{$i}";
            $existing = DB::table('facilities')->where('slug', $slug)->first();
            if ($existing) {
                $facilityId = $existing->id;
            } else {
                $facilityId = DB::table('facilities')->insertGetId([
                    'name' => "QATEST Kurum {$i}",
                    'slug' => $slug,
                    'city_id' => $city->id,
                    'facility_category_id' => $category->id,
                    'ownership_type' => 'ozel',
                    'address' => 'Test adresi',
                    'phone' => '0532 000 00 0'.$i,
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
            }

            $email = "qatest.facility{$i}@example.com";
            $userExisting = DB::table('facility_users')->where('email', $email)->first();
            if (! $userExisting) {
                DB::table('facility_users')->insert([
                    'facility_id' => $facilityId,
                    'name' => "QATEST Yetkili {$i}",
                    'email' => $email,
                    'password' => $password,
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'must_change_password' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $out .= "QATEST Kurum {$i}: facility_id={$facilityId} slug={$slug} facility_user_email={$email}\n";
        }

        $out .= "\nTum test kurum kullanicilarinin sifresi: QaTest12345!\n";
        $out .= "Sehir: bursa, Kategori: huzurevi\n";

        return $out;
    }

    // 28 Temmuz 2026: gercek admin oturumuyla (2FA'li) sahiplenme basvurusu
    // onay/red akisini uctan uca test edebilmek icin - qaSetup'taki 3 kurumun
    // aksine bilerek SAHIPLENILMEMIS (is_claimed=false) tek bir QATEST kurumu
    // olusturur, boylece gercek bir isletmeye dokunmadan /kurumlar/{slug}/sahiplen
    // formu + admin onay/red route'lari denenebilir. qaTeardown zaten slug'i
    // 'qatest-' ile baslayan her kurumu temizledigi icin ayrica bir teardown
    // kodu gerekmez.
    private function qaSetupUnclaimed(): string
    {
        $city = DB::table('cities')->where('slug', 'bursa')->first();
        $category = DB::table('facility_categories')->where('slug', 'huzurevi')->first();
        abort_if(! $city || ! $category, 500, 'Test icin sehir/kategori bulunamadi');

        $slug = 'qatest-kurum-sahipsiz';
        $existing = DB::table('facilities')->where('slug', $slug)->first();

        if ($existing) {
            DB::table('facilities')->where('id', $existing->id)->update([
                'is_claimed' => false,
                'claimed_at' => null,
                'invitation_status' => 'pending',
                'invitation_status_at' => null,
                'updated_at' => now(),
            ]);
            $facilityId = $existing->id;
        } else {
            $facilityId = DB::table('facilities')->insertGetId([
                'name' => 'QATEST Kurum Sahipsiz',
                'slug' => $slug,
                'city_id' => $city->id,
                'facility_category_id' => $category->id,
                'ownership_type' => 'ozel',
                'address' => 'Test adresi',
                'phone' => '0532 000 00 09',
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
        }

        DB::table('facility_users')->where('facility_id', $facilityId)->delete();

        return "QATEST Kurum Sahipsiz: facility_id={$facilityId} slug={$slug} (sahiplenilmemis, sahiplenme formu icin hazir)\n";
    }

    // 25 Agustos 2026: "Yerinde Sahiplendirme" duzeltmesini (giris bilgileri
    // gorunmuyordu, bkz. Admin\FacilityController::instantClaim() yorumu)
    // GERCEK controller kodunu canli sunucuda calistirarak dogrulamak icin -
    // qa-approve-claim ile ayni desen (guvenlik: sadece @example.com).
    // QATEST kurumunu kullanir, sonunda hem FacilityUser'i hem sahiplenme
    // durumunu geri temizler ki tekrar tekrar calistirilabilsin.
    private function qaInstantClaimTest(Request $request): string
    {
        $this->qaSetupUnclaimed();
        $facility = Facility::where('slug', 'qatest-kurum-sahipsiz')->firstOrFail();

        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok';
        }
        session(['admin_id' => $admin->id]);

        $testEmail = 'qa-instant-claim-test@example.com';
        $request->merge([
            'applicant_name' => 'QA Test Yetkili',
            'applicant_email' => $testEmail,
            'applicant_phone' => '0532 000 00 09',
        ]);

        $controller = app(\App\Http\Controllers\Admin\FacilityController::class);
        $response = $controller->instantClaim($request, $facility);

        $facility->refresh();
        $facilityUser = \App\Models\FacilityUser::where('email', $testEmail)->first();
        $creds = session('instant_claim_credentials');
        $success = session('success');

        $out = 'facility.is_claimed=' . ($facility->is_claimed ? 'evet' : 'hayir') . "\n";
        $out .= $facilityUser
            ? "FacilityUser olusturuldu: #{$facilityUser->id} email={$facilityUser->email} must_change_password=" . ($facilityUser->must_change_password ? 'evet' : 'hayir') . "\n"
            : "FacilityUser OLUSTURULAMADI\n";
        $out .= 'redirect target: ' . $response->getTargetUrl() . "\n";
        $out .= 'session[instant_claim_credentials]: ' . json_encode($creds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $out .= 'session[success]: ' . $success . "\n";

        if ($facilityUser) {
            $facilityUser->delete();
        }
        DB::table('facilities')->where('id', $facility->id)->update([
            'is_claimed' => false,
            'claimed_at' => null,
            'invitation_status' => 'pending',
            'invitation_status_at' => null,
            'updated_at' => now(),
        ]);

        return $out;
    }

    // 27 Agustos 2026: video tanitim yukleme ozelligini (bkz. VideoCompressionService,
    // FfmpegLocator, Admin\FacilityController::storeUploadedVideo/deleteVideo)
    // production'da GERCEK kodla, GERCEK ffmpeg ile dogrulayan tek seferlik QA
    // araci - qaInstantClaimTest ile ayni desen (qatest- veri, sonunda temizlik).
    private function qaVideoUploadTest(Request $request): string
    {
        $out = '';

        $city = \App\Models\City::first();
        $category = \App\Models\FacilityCategory::where('brand_scope', 'yasli-bakim')->first();
        if (! $city || ! $category) {
            return 'HATA: yasli-bakim kategorisi veya sehir bulunamadi';
        }

        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok';
        }
        session(['admin_id' => $admin->id]);

        $facility = Facility::create([
            'name' => 'QATEST Video Upload Live',
            'slug' => 'qatest-video-upload-live-'.time(),
            'city_id' => $city->id,
            'facility_category_id' => $category->id,
            'district' => 'Merkez',
            'address' => 'Adres',
            'phone' => '02120000000',
            'description' => 'Aciklama',
            'capacity' => 20,
            'price_min' => 1000,
            'price_max' => 2000,
            'services' => ['bakim'],
            'is_published' => true,
            'is_broker_managed' => true,
        ]);

        $dir = storage_path('framework/testing/files');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $videoPath = $dir.'/'.uniqid('qa_video_', true).'.mp4';
        file_put_contents($videoPath, base64_decode('AAAAIGZ0eXBpc29tAAACAGlzb21pc28yYXZjMW1wNDEAAAAIZnJlZQAABw9tZGF0AAACrQYF//+p3EXpvebZSLeWLNgg2SPu73gyNjQgLSBjb3JlIDE2NSByMzIyMyAwNDgwY2IwIC0gSC4yNjQvTVBFRy00IEFWQyBjb2RlYyAtIENvcHlsZWZ0IDIwMDMtMjAyNSAtIGh0dHA6Ly93d3cudmlkZW9sYW4ub3JnL3gyNjQuaHRtbCAtIG9wdGlvbnM6IGNhYmFjPTEgcmVmPTMgZGVibG9jaz0xOjA6MCBhbmFseXNlPTB4MzoweDExMyBtZT1oZXggc3VibWU9NyBwc3k9MSBwc3lfcmQ9MS4wMDowLjAwIG1peGVkX3JlZj0xIG1lX3JhbmdlPTE2IGNocm9tYV9tZT0xIHRyZWxsaXM9MSA4eDhkY3Q9MSBjcW09MCBkZWFkem9uZT0yMSwxMSBmYXN0X3Bza2lwPTEgY2hyb21hX3FwX29mZnNldD0tMiB0aHJlYWRzPTIgbG9va2FoZWFkX3RocmVhZHM9MSBzbGljZWRfdGhyZWFkcz0wIG5yPTAgZGVjaW1hdGU9MSBpbnRlcmxhY2VkPTAgYmx1cmF5X2NvbXBhdD0wIGNvbnN0cmFpbmVkX2ludHJhPTAgYmZyYW1lcz0zIGJfcHlyYW1pZD0yIGJfYWRhcHQ9MSBiX2JpYXM9MCBkaXJlY3Q9MSB3ZWlnaHRiPTEgb3Blbl9nb3A9MCB3ZWlnaHRwPTIga2V5aW50PTI1MCBrZXlpbnRfbWluPTUgc2NlbmVjdXQ9NDAgaW50cmFfcmVmcmVzaD0wIHJjX2xvb2thaGVhZD00MCByYz1jcmYgbWJ0cmVlPTEgY3JmPTIzLjAgcWNvbXA9MC42MCBxcG1pbj0wIHFwbWF4PTY5IHFwc3RlcD00IGlwX3JhdGlvPTEuNDAgYXE9MToxLjAwAIAAAANDZYiEAJ/tc8NKcf4LWdj+Ao+CdzckNdvpzvRdYcUuCKL0wPPVziAHr3eUwOvaGaTUkF5qeN6lLC8eSex52ANeaiaFGTgB463TpNAs2bedCir6BzWyC548/mw0m8T/EblEyQJvbNeh3VNYhDrUel/FhJRWIB/MuNRFUMomrbmHH/n5MwebJabc9JTXBnjEEGmRz2e9nE4Q6cy87ZLD/++RmzZdK2bzJZ2PlL5NNfYsE3oiNuT8W6n7cS+zgZOI54OaXd37ANqukEDudDk2hM51LI17hLDMAGDdE98oOE6vjHsX5aVprQ23XYnlIcHd8b8X4jOtIzqszrGKTmV+L1soSsEwRHs4dFw1ru2nphIQLC9a5tCRNQdmeFqxreZdgGlXm00BrCVdXQgjqk/EWSBNcBWhjs9yzKxtn1iWUsRn9Of8DOBg4fWCDlxJKVEYnxc3NgV0MmD5qSXQB/a6PQLsOMz7vgNRsG5/4wAMJQdjeokX2kUGtHeXVfwBwjydwt2laF4dCe+964cE/oGMAWGA1d1zhFtNf/kMdjpaDOtrKsyFhSZQVUxcCrrV+KWDfRIbe1tfrvg0feFV63M0tdvKr5W5o35zWPegWNHfikzS4gp0eHZ3qoc3F/jPY/m71jbwnQngPCCF1k7wnQD0MH1FpJCULcgGvpr2HETPf3gJKj32OBy4imVqW2poP29F7nDpmQr3cp3MoJPJ9kaH5UkZ+aVCnaRDcg3F+51vdot6U7A0WPclGoob/YPp//bKcZ+yusCm7Se6mGzn8RjmwBePzKHZ6pPvAPibo0yGAQwWyIIl8VeGAi8O6E4GcptXVX9FMUEZqFPlNOBtnF1a1fflqQWeo4Jjg8PwurgrZiG/bDufISmf2/JkkeehA/D9JrYY4Upw1f4gK5qv3mO7SNwiQIo5eo37M2bL3zxvb+B32go3o6ju2nzS439oGxTFk0qH19bsh/CWM6pcflchS2X+VH0P4BC38kg9AYO1RAh4zyVn3YreaS8JC6+NJiHOSlThuB5mDSXzehc20UTY9SbIacZouWCX1YCddqtM71r8JSE4uDBLdvCZ6FdSJWlZawrLZd46X+Y37IMPrOvuOdb7wIa+5wAAAJFBmiRsRn8aMv3/DiSEiaL4b+wi4c+HBgcUEt9/4nktD0+bv+kl3rY5EGS4lPj/pcSSioTYSzfVf/v/Q1zo6V9vW3HGf0Q51njccWpvXIxj2MSeY/EvlDenEBl756k95DNDhqb7DcorPgQrZhmof7rilkQGNfA6MkbxASKZIYA3kZxjq73TJXBS5zzCb/10XNaIAAAAQEGeQniI/3ZhUOrxaVecv2ifOBZoRrGGalOHaN4Tt7TYqKJfyjtwy5e5EFYRIiKF+ycWnfRGQTY5jAoiUSx1l0MAAAAVAZ5hdEZ/fqg9YkAqj+SxCErpdeygAAAAGQGeY2pGf2aQwmIijNtRDoG9JeLbqsm8rQMAAAN2bW9vdgAAAGxtdmhkAAAAAAAAAAAAAAAAAAAD6AAAA+gAAQAAAQAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAqB0cmFrAAAAXHRraGQAAAADAAAAAAAAAAAAAAABAAAAAAAAA+gAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAEAAAABAAAAAAAAkZWR0cwAAABxlbHN0AAAAAAAAAAEAAAPoAAAQAAABAAAAAAIYbWRpYQAAACBtZGhkAAAAAAAAAAAAAAAAAAAoAAAAKABVxAAAAAAALWhkbHIAAAAAAAAAAHZpZGUAAAAAAAAAAAAAAABWaWRlb0hhbmRsZXIAAAABw21pbmYAAAAUdm1oZAAAAAEAAAAAAAAAAAAAACRkaW5mAAAAHGRyZWYAAAAAAAAAAQAAAAx1cmwgAAAAAQAAAYNzdGJsAAAAv3N0c2QAAAAAAAAAAQAAAK9hdmMxAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAEAAQABIAAAASAAAAAAAAAABFUxhdmM2Mi4yOC4xMDEgbGlieDI2NAAAAAAAAAAAAAAAGP//AAAANWF2Y0MBZAAK/+EAGGdkAAqs2UQmwEQAAAMABAAAAwAoPEiWWAEABmjr48siwP34+AAAAAAQcGFzcAAAAAEAAAABAAAAFGJ0cnQAAAAAAAA4OAAAAAAAAAAYc3R0cwAAAAAAAAABAAAABQAACAAAAAAUc3RzcwAAAAAAAAABAAAAAQAAADhjdHRzAAAAAAAAAAUAAAABAAAQAAAAAAEAACgAAAAAAQAAEAAAAAABAAAAAAAAAAEAAAgAAAAAHHN0c2MAAAAAAAAAAQAAAAEAAAAFAAAAAQAAAChzdHN6AAAAAAAAAAAAAAAFAAAF+AAAAJUAAABEAAAAGQAAAB0AAAAUc3RjbwAAAAAAAAABAAAAMAAAAGJ1ZHRhAAAAWm1ldGEAAAAAAAAAIWhkbHIAAAAAAAAAAG1kaXJhcHBsAAAAAAAAAAAAAAAALWlsc3QAAAAlqXRvbwAAAB1kYXRhAAAAAQAAAABMYXZmNjIuMTIuMTAx'));

        $file = new \Illuminate\Http\UploadedFile($videoPath, 'qa-video.mp4', 'video/mp4', null, true);
        $uploadRequest = Request::create('/admin/kurumlar/'.$facility->id, 'POST');
        $uploadRequest->files->set('video', $file);

        $controller = app(\App\Http\Controllers\Admin\FacilityController::class);
        $method = new \ReflectionMethod($controller, 'storeUploadedVideo');
        $method->setAccessible(true);
        $method->invoke($controller, $uploadRequest, $facility);

        $facility->refresh();
        $out .= 'video_path: '.($facility->video_path ?? 'NULL')."\n";
        $out .= 'flash warning: '.(session('image_warning') ?? '(yok)')."\n";

        if (! $facility->video_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($facility->video_path)) {
            $facility->forceDelete();

            return $out."SONUC: BASARISIZ - video kaydedilmedi\n";
        }

        [$publicStatus, $publicResponse] = $this->renderPublicFacilityPage($facility, $request);
        $out .= 'genel sayfa HTTP durumu: '.$publicStatus."\n";
        $out .= 'genel sayfa "Tanitim Videosu" iceriyor mu: '.(str_contains($publicResponse, 'Tanıtım Videosu') ? 'EVET' : 'HAYIR')."\n";
        $out .= 'genel sayfa video yolunu iceriyor mu: '.(str_contains($publicResponse, $facility->video_path) ? 'EVET' : 'HAYIR')."\n";
        if ($publicStatus !== 200) {
            $out .= 'yanit ilk 300 karakter: '.substr($publicResponse, 0, 300)."\n";
        }

        $storedPath = $facility->video_path;
        $deleteMethod = new \ReflectionMethod($controller, 'deleteVideo');
        $deleteMethod->invoke($controller, $facility);
        $facility->refresh();

        $out .= 'silme sonrasi video_path: '.($facility->video_path ?? 'NULL')."\n";
        $out .= 'silme sonrasi dosya diskte mevcut mu: '.(\Illuminate\Support\Facades\Storage::disk('public')->exists($storedPath) ? 'EVET (HATA)' : 'HAYIR (dogru)')."\n";

        $facility->forceDelete();

        $out .= "\nSONUC: BASARILI\n";

        return $out;
    }

    private function renderPublicFacilityPage(Facility $facility, Request $incomingRequest): array
    {
        // 27 Agustos 2026: kok neden - once array_key_first(config('brands.brands'))
        // kullanilmisti, bu production'da HER ZAMAN 'bakimevibul'e denk geliyordu
        // (config dizisindeki ilk anahtar) ve /site/{brand}/... yolu production'da
        // KASITLI bir SEO 301 yonlendirmesi (bkz. routes/web.php 352-373 satirlari,
        // 25 Agustos'ta eklendi) - bu yuzden gercek sayfa hic render edilmiyordu,
        // sadece yonlendirme govdesi donuyordu. Dogru path: mevcut /_ops istegini
        // alan GERCEK domain'in kok (prefix'siz) kurum adresi.
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        if (app()->environment(['local', 'testing'])) {
            $brand = array_key_first(config('brands.brands', []));
            $showRequest = Request::create('/site/'.$brand.'/kurumlar/'.$facility->slug, 'GET');
        } else {
            $showRequest = Request::create('https://'.$incomingRequest->getHttpHost().'/kurumlar/'.$facility->slug, 'GET');
        }

        $response = $kernel->handle($showRequest);
        $kernel->terminate($showRequest, $response);

        return [$response->getStatusCode(), $response->getContent()];
    }

    // 27 Agustos 2026: kurum panelindeki (Facility\ProfileController::
    // uploadVideo/deleteVideo) yeni "anlasmali kurum kendi videosunu
    // yonetebilsin, anlasmasiz kurum pasif/tesvik gorunumu gorsun" ozelligini
    // production'da GERCEK kodla dogrulayan tek seferlik QA araci -
    // qaVideoUploadTest (admin tarafi) ile ayni desen, kontrolculer DOGRUDAN
    // cagrilir (Kernel::handle degil) - facility_user_id oturumu session()
    // helper'i ile ayni PHP surecinde paylasilir, ekstra karmasiklik gerekmez.
    private function qaFacilityPanelVideoTest(): string
    {
        $out = '';

        $city = \App\Models\City::first();
        $category = \App\Models\FacilityCategory::where('brand_scope', 'yasli-bakim')->first();
        if (! $city || ! $category) {
            return 'HATA: yasli-bakim kategorisi veya sehir bulunamadi';
        }

        $facility = Facility::create([
            'name' => 'QATEST Facility Panel Video',
            'slug' => 'qatest-facility-panel-video-'.time(),
            'city_id' => $city->id,
            'facility_category_id' => $category->id,
            'district' => 'Merkez',
            'address' => 'Adres',
            'phone' => '02120000000',
            'description' => 'Aciklama',
            'capacity' => 20,
            'price_min' => 1000,
            'price_max' => 2000,
            'services' => ['bakim'],
            'is_published' => true,
            'is_claimed' => true,
            'claimed_at' => now(),
            'is_broker_managed' => false,
        ]);

        $facilityUser = \App\Models\FacilityUser::create([
            'facility_id' => $facility->id,
            'name' => 'QA Video Test',
            'email' => 'qa-facility-panel-video-test-'.time().'@example.com',
            'phone' => '05320000000',
            'password' => bcrypt('QaTest12345!'),
            'must_change_password' => false,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        session(['facility_user_id' => $facilityUser->id]);
        // 'errors' view degiskeni normalde ShareErrorsFromSession middleware'i
        // tarafindan enjekte edilir - kontrolcuyu dogrudan cagirdigimiz icin
        // (route/middleware zincirinden gecmeden) Blade'deki @error direktifi
        // bu olmadan patlar, elle saglanmasi gerekiyor.
        app('view')->share('errors', session()->get('errors') ?? new \Illuminate\Support\ViewErrorBag);
        $controller = app(\App\Http\Controllers\Facility\ProfileController::class);

        $editView = (string) $controller->edit();
        $out .= '1) Anlasmasiz profilde pasif uyarisi var mi: '.(str_contains($editView, 'Bu alan şu anda') ? 'EVET (dogru)' : 'HAYIR (HATA)')."\n";

        $dir = storage_path('framework/testing/files');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $videoPath = $dir.'/'.uniqid('qa_fp_video_', true).'.mp4';
        file_put_contents($videoPath, base64_decode('AAAAIGZ0eXBpc29tAAACAGlzb21pc28yYXZjMW1wNDEAAAAIZnJlZQAABw9tZGF0AAACrQYF//+p3EXpvebZSLeWLNgg2SPu73gyNjQgLSBjb3JlIDE2NSByMzIyMyAwNDgwY2IwIC0gSC4yNjQvTVBFRy00IEFWQyBjb2RlYyAtIENvcHlsZWZ0IDIwMDMtMjAyNSAtIGh0dHA6Ly93d3cudmlkZW9sYW4ub3JnL3gyNjQuaHRtbCAtIG9wdGlvbnM6IGNhYmFjPTEgcmVmPTMgZGVibG9jaz0xOjA6MCBhbmFseXNlPTB4MzoweDExMyBtZT1oZXggc3VibWU9NyBwc3k9MSBwc3lfcmQ9MS4wMDowLjAwIG1peGVkX3JlZj0xIG1lX3JhbmdlPTE2IGNocm9tYV9tZT0xIHRyZWxsaXM9MSA4eDhkY3Q9MSBjcW09MCBkZWFkem9uZT0yMSwxMSBmYXN0X3Bza2lwPTEgY2hyb21hX3FwX29mZnNldD0tMiB0aHJlYWRzPTIgbG9va2FoZWFkX3RocmVhZHM9MSBzbGljZWRfdGhyZWFkcz0wIG5yPTAgZGVjaW1hdGU9MSBpbnRlcmxhY2VkPTAgYmx1cmF5X2NvbXBhdD0wIGNvbnN0cmFpbmVkX2ludHJhPTAgYmZyYW1lcz0zIGJfcHlyYW1pZD0yIGJfYWRhcHQ9MSBiX2JpYXM9MCBkaXJlY3Q9MSB3ZWlnaHRiPTEgb3Blbl9nb3A9MCB3ZWlnaHRwPTIga2V5aW50PTI1MCBrZXlpbnRfbWluPTUgc2NlbmVjdXQ9NDAgaW50cmFfcmVmcmVzaD0wIHJjX2xvb2thaGVhZD00MCByYz1jcmYgbWJ0cmVlPTEgY3JmPTIzLjAgcWNvbXA9MC42MCBxcG1pbj0wIHFwbWF4PTY5IHFwc3RlcD00IGlwX3JhdGlvPTEuNDAgYXE9MToxLjAwAIAAAANDZYiEAJ/tc8NKcf4LWdj+Ao+CdzckNdvpzvRdYcUuCKL0wPPVziAHr3eUwOvaGaTUkF5qeN6lLC8eSex52ANeaiaFGTgB463TpNAs2bedCir6BzWyC548/mw0m8T/EblEyQJvbNeh3VNYhDrUel/FhJRWIB/MuNRFUMomrbmHH/n5MwebJabc9JTXBnjEEGmRz2e9nE4Q6cy87ZLD/++RmzZdK2bzJZ2PlL5NNfYsE3oiNuT8W6n7cS+zgZOI54OaXd37ANqukEDudDk2hM51LI17hLDMAGDdE98oOE6vjHsX5aVprQ23XYnlIcHd8b8X4jOtIzqszrGKTmV+L1soSsEwRHs4dFw1ru2nphIQLC9a5tCRNQdmeFqxreZdgGlXm00BrCVdXQgjqk/EWSBNcBWhjs9yzKxtn1iWUsRn9Of8DOBg4fWCDlxJKVEYnxc3NgV0MmD5qSXQB/a6PQLsOMz7vgNRsG5/4wAMJQdjeokX2kUGtHeXVfwBwjydwt2laF4dCe+964cE/oGMAWGA1d1zhFtNf/kMdjpaDOtrKsyFhSZQVUxcCrrV+KWDfRIbe1tfrvg0feFV63M0tdvKr5W5o35zWPegWNHfikzS4gp0eHZ3qoc3F/jPY/m71jbwnQngPCCF1k7wnQD0MH1FpJCULcgGvpr2HETPf3gJKj32OBy4imVqW2poP29F7nDpmQr3cp3MoJPJ9kaH5UkZ+aVCnaRDcg3F+51vdot6U7A0WPclGoob/YPp//bKcZ+yusCm7Se6mGzn8RjmwBePzKHZ6pPvAPibo0yGAQwWyIIl8VeGAi8O6E4GcptXVX9FMUEZqFPlNOBtnF1a1fflqQWeo4Jjg8PwurgrZiG/bDufISmf2/JkkeehA/D9JrYY4Upw1f4gK5qv3mO7SNwiQIo5eo37M2bL3zxvb+B32go3o6ju2nzS439oGxTFk0qH19bsh/CWM6pcflchS2X+VH0P4BC38kg9AYO1RAh4zyVn3YreaS8JC6+NJiHOSlThuB5mDSXzehc20UTY9SbIacZouWCX1YCddqtM71r8JSE4uDBLdvCZ6FdSJWlZawrLZd46X+Y37IMPrOvuOdb7wIa+5wAAAJFBmiRsRn8aMv3/DiSEiaL4b+wi4c+HBgcUEt9/4nktD0+bv+kl3rY5EGS4lPj/pcSSioTYSzfVf/v/Q1zo6V9vW3HGf0Q51njccWpvXIxj2MSeY/EvlDenEBl756k95DNDhqb7DcorPgQrZhmof7rilkQGNfA6MkbxASKZIYA3kZxjq73TJXBS5zzCb/10XNaIAAAAQEGeQniI/3ZhUOrxaVecv2ifOBZoRrGGalOHaN4Tt7TYqKJfyjtwy5e5EFYRIiKF+ycWnfRGQTY5jAoiUSx1l0MAAAAVAZ5hdEZ/fqg9YkAqj+SxCErpdeygAAAAGQGeY2pGf2aQwmIijNtRDoG9JeLbqsm8rQMAAAN2bW9vdgAAAGxtdmhkAAAAAAAAAAAAAAAAAAAD6AAAA+gAAQAAAQAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAqB0cmFrAAAAXHRraGQAAAADAAAAAAAAAAAAAAABAAAAAAAAA+gAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAEAAAABAAAAAAAAkZWR0cwAAABxlbHN0AAAAAAAAAAEAAAPoAAAQAAABAAAAAAIYbWRpYQAAACBtZGhkAAAAAAAAAAAAAAAAAAAoAAAAKABVxAAAAAAALWhkbHIAAAAAAAAAAHZpZGUAAAAAAAAAAAAAAABWaWRlb0hhbmRsZXIAAAABw21pbmYAAAAUdm1oZAAAAAEAAAAAAAAAAAAAACRkaW5mAAAAHGRyZWYAAAAAAAAAAQAAAAx1cmwgAAAAAQAAAYNzdGJsAAAAv3N0c2QAAAAAAAAAAQAAAK9hdmMxAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAEAAQABIAAAASAAAAAAAAAABFUxhdmM2Mi4yOC4xMDEgbGlieDI2NAAAAAAAAAAAAAAAGP//AAAANWF2Y0MBZAAK/+EAGGdkAAqs2UQmwEQAAAMABAAAAwAoPEiWWAEABmjr48siwP34+AAAAAAQcGFzcAAAAAEAAAABAAAAFGJ0cnQAAAAAAAA4OAAAAAAAAAAYc3R0cwAAAAAAAAABAAAABQAACAAAAAAUc3RzcwAAAAAAAAABAAAAAQAAADhjdHRzAAAAAAAAAAUAAAABAAAQAAAAAAEAACgAAAAAAQAAEAAAAAABAAAAAAAAAAEAAAgAAAAAHHN0c2MAAAAAAAAAAQAAAAEAAAAFAAAAAQAAAChzdHN6AAAAAAAAAAAAAAAFAAAF+AAAAJUAAABEAAAAGQAAAB0AAAAUc3RjbwAAAAAAAAABAAAAMAAAAGJ1ZHRhAAAAWm1ldGEAAAAAAAAAIWhkbHIAAAAAAAAAAG1kaXJhcHBsAAAAAAAAAAAAAAAALWlsc3QAAAAlqXRvbwAAAB1kYXRhAAAAAQAAAABMYXZmNjIuMTIuMTAx'));

        $rejectRequest = Request::create('/kurum-panel/profil/video', 'POST');
        $rejectRequest->files->set('video', new \Illuminate\Http\UploadedFile($videoPath, 'qa.mp4', 'video/mp4', null, true));
        $controller->uploadVideo($rejectRequest);
        $rejectError = session('errors')?->first('video');
        $out .= '2) Anlasmasiz durumda yukleme reddi mesaji: '.($rejectError ?? '(HATA: mesaj yok)')."\n";
        $out .= '   Kurum video_path hala bos mu: '.($facility->fresh()->video_path === null ? 'EVET (dogru)' : 'HAYIR (HATA)')."\n";

        $facility->update(['is_broker_managed' => true]);
        $editView2 = (string) $controller->edit();
        $out .= '3) Anlasmali profilde pasif uyarisi kalkti mi: '.(! str_contains($editView2, 'Bu alan şu anda') ? 'EVET (dogru)' : 'HAYIR (HATA)')."\n";

        session()->forget('errors');
        $acceptRequest = Request::create('/kurum-panel/profil/video', 'POST');
        $acceptRequest->files->set('video', new \Illuminate\Http\UploadedFile($videoPath, 'qa.mp4', 'video/mp4', null, true));
        $controller->uploadVideo($acceptRequest);
        $facility->refresh();
        $out .= '4) Anlasmali durumda video_path: '.($facility->video_path ?? 'NULL (HATA)')."\n";
        $out .= '   Dosya diskte var mi: '.($facility->video_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($facility->video_path) ? 'EVET (dogru)' : 'HAYIR (HATA)')."\n";

        $storedPath = $facility->video_path;
        $controller->deleteVideo(Request::create('/kurum-panel/profil/video', 'DELETE'));
        $facility->refresh();
        $out .= '5) Silme sonrasi video_path: '.($facility->video_path ?? 'NULL (dogru)')."\n";
        $out .= '   Silme sonrasi dosya diskte mi: '.($storedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($storedPath) ? 'EVET (HATA)' : 'HAYIR (dogru)')."\n";

        $facilityUser->delete();
        $facility->forceDelete();

        $out .= "\nSONUC: BASARILI\n";

        return $out;
    }

    // 26 Agustos 2026: 25 Agustos'ta canliya alinan 4 duzeltmeyi (bonus
    // hakkinin "ön kayıtlı"ya donuste sifirlanmasi, Bakiye/Hak Gecmisi
    // kayitlarinin duzenlenebilir/silinebilir olmasi, dogru marka
    // yonlendirmesi, sembolsuz gecici sifre) GERCEK production kodunu
    // canli sunucuda calistirarak tek seferde dogrulayan kapsamli QA
    // araci - kullanicinin "test etmeden soyleme" talebinin bir sonraki
    // adimi, sadece kod incelemesine degil calisan kanitlara dayanmak icin.
    private function qaVerifyBalanceBrandFixes(): string
    {
        $out = '';
        $this->qaSetupUnclaimed();
        $facility = Facility::where('slug', 'qatest-kurum-sahipsiz')->firstOrFail();

        $admin = DB::table('admins')->first();
        abort_if(! $admin, 500, 'HATA: hic admin yok');
        session(['admin_id' => $admin->id]);

        $testEmail = 'qa-verify-fixes@example.com';
        $claimRequest = Request::create('/', 'POST', [
            'applicant_name' => 'QA Verify',
            'applicant_email' => $testEmail,
            'applicant_phone' => '0532 000 00 09',
        ]);

        $facilityController = app(\App\Http\Controllers\Admin\FacilityController::class);
        $facilityController->instantClaim($claimRequest, $facility);
        $facility->refresh();

        // (4) sifre sembolsuz mu?
        $creds = session('instant_claim_credentials');
        $password = $creds['password'] ?? '';
        $out .= "(4) Sifre formati: \"{$password}\" -> " . (preg_match('/^[A-Za-z0-9]+$/', $password) ? 'OK (sadece harf+rakam)' : 'HATA (sembol iceriyor)') . "\n";

        $creditsAfterClaim = (int) $facility->free_quote_credits;
        $balanceBeforeRevert = (float) $facility->balance;
        $out .= "Sahiplendirme sonrasi: bakiye={$balanceBeforeRevert} hak={$creditsAfterClaim}\n";

        // (1) revertToPreRegistered GERCEK kodu bonusu sifirliyor mu?
        $facilityController->revertToPreRegistered($facility);
        $facility->refresh();
        $revertLog = \App\Models\BalanceLog::where('facility_id', $facility->id)->where('type', 'claim_reverted')->latest('id')->first();
        $out .= '(1) Geri alma sonrasi: bakiye=' . $facility->balance . ' hak=' . $facility->free_quote_credits
            . ' -> ' . ($facility->balance == 0 && $facility->free_quote_credits == 0 ? 'OK (sifirlandi)' : 'HATA (sifirlanmadi)') . "\n";
        $out .= '    claim_reverted log kaydi: ' . ($revertLog ? "VAR (credits_amount={$revertLog->credits_amount}, amount={$revertLog->amount})" : 'YOK - HATA') . "\n";

        // (2) balance-log duzenle/sil GERCEK kodu calisiyor mu?
        $balanceController = app(\App\Http\Controllers\Admin\BalanceController::class);
        $manualLog = \App\Models\BalanceLog::create([
            'facility_id' => $facility->id, 'type' => 'admin_adjust_credits', 'amount' => 0,
            'credits_amount' => 7, 'balance_after' => $facility->balance, 'credits_after' => $facility->free_quote_credits + 7,
            'admin_id' => $admin->id, 'note' => 'QA verify - duzenle/sil testi',
        ]);
        DB::table('facilities')->where('id', $facility->id)->update(['free_quote_credits' => $facility->free_quote_credits + 7]);
        $facility->refresh();
        $creditsBeforeEdit = $facility->free_quote_credits;

        $updateRequest = Request::create('/', 'PUT', ['amount' => 0, 'credits_amount' => 3, 'note' => 'QA verify - duzenlendi']);
        $balanceController->updateLog($updateRequest, $facility, $manualLog);
        $facility->refresh();
        $out .= "(2a) updateLog: hak {$creditsBeforeEdit} -> {$facility->free_quote_credits} -> " . ($facility->free_quote_credits == $creditsBeforeEdit - 4 ? 'OK (delta dogru uygulandi)' : 'HATA') . "\n";

        $creditsBeforeDelete = $facility->free_quote_credits;
        $balanceController->destroyLog($facility, $manualLog->fresh());
        $facility->refresh();
        $logStillExists = \App\Models\BalanceLog::find($manualLog->id);
        $out .= "(2b) destroyLog: hak {$creditsBeforeDelete} -> {$facility->free_quote_credits}, kayit " . ($logStillExists ? 'HALA VAR - HATA' : 'silindi') . ' -> ' . ($facility->free_quote_credits == $creditsBeforeDelete - 3 ? 'OK' : 'HATA') . "\n";

        // (3) marka tespiti: gercek bir sahiplenme basvurusu (facility_claims.brand)
        // kaydi varsa KATEGORI TAHMININE degil ONA guveniyor mu?
        $testClaim = \App\Models\FacilityClaim::create([
            'facility_id' => $facility->id, 'brand' => 'bakimevleri', 'applicant_name' => 'QA Verify',
            'applicant_email' => $testEmail, 'applicant_phone' => '0532 000 00 09', 'document_path' => 'qa-verify.jpg',
            'status' => 'approved', 'reviewed_at' => now(),
        ]);
        $resolvedBrand = facility_login_brand_slug($facility->fresh());
        $resolvedUrl = facility_brand_login_url($resolvedBrand);
        $out .= "(3) facility_claims.brand=bakimevleri iken tespit edilen marka: {$resolvedBrand} -> " . ($resolvedBrand === 'bakimevleri' ? 'OK' : 'HATA (kategori tahminine dusmus olabilir)') . "\n";
        $out .= "    Uretilen login URL: {$resolvedUrl}\n";
        $testClaim->delete();

        // temizlik
        \App\Models\FacilityUser::where('email', $testEmail)->delete();
        DB::table('facilities')->where('id', $facility->id)->update([
            'is_claimed' => false, 'claimed_at' => null, 'invitation_status' => 'pending',
            'invitation_status_at' => null, 'balance' => 0, 'free_quote_credits' => 0, 'updated_at' => now(),
        ]);
        \App\Models\BalanceLog::where('facility_id', $facility->id)->delete();

        return $out;
    }

    // 28 Temmuz 2026: gercek SMTP kutusuna erisim olmadan sifre sifirlama
    // akisini "linke tiklamaya kadar" test edebilmek icin - mail gonderme
    // kodunun AYNISINI (URL::temporarySignedRoute + hashFor mantigi)
    // calistirip imzali URL'yi dogrudan dondurur. SADECE @example.com
    // (QATEST) hesaplariyla sinirli - gercek bir kullanicinin linkini
    // asla uretmez/sizdirmaz.
    private function qaPasswordResetLink(Request $request): string
    {
        $type = (string) $request->query('type', 'family');
        $email = (string) $request->query('email', '');

        if (! str_ends_with($email, '@example.com')) {
            return 'HATA: sadece @example.com test hesaplari icin link uretilebilir';
        }

        if ($type === 'facility') {
            $user = \App\Models\FacilityUser::where('email', $email)->first();
            abort_if(! $user, 404, 'Kurum kullanicisi bulunamadi');
            $hash = sha1($user->email.$user->password);

            return \Illuminate\Support\Facades\URL::temporarySignedRoute('facility.password.reset', now()->addMinutes(60), ['id' => $user->id, 'hash' => $hash]);
        }

        $user = \App\Models\FamilyUser::where('email', $email)->first();
        abort_if(! $user, 404, 'Aile kullanicisi bulunamadi');
        $hash = sha1($user->email.$user->password);

        return \Illuminate\Support\Facades\URL::temporarySignedRoute('family.password.reset', now()->addMinutes(60), ['id' => $user->id, 'hash' => $hash]);
    }

    // 28 Temmuz 2026: qa-password-reset-link ile ayni gerekce - "revize
    // istendi" mailindeki imzali duzenleme linkine gercek posta kutusu
    // olmadan ulasabilmek icin. SADECE @example.com (QATEST) basvurulari.
    private function qaRegistrationEditLink(Request $request): string
    {
        $email = (string) $request->query('email', '');
        if (! str_ends_with($email, '@example.com')) {
            return 'HATA: sadece @example.com test basvurulari icin link uretilebilir';
        }

        $registration = \App\Models\FacilityRegistration::where('applicant_email', $email)->latest()->first();
        abort_if(! $registration, 404, 'Basvuru bulunamadi');

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'facility-registration.edit',
            now()->addDays(14),
            ['registration' => $registration->id, 'hash' => sha1($registration->applicant_email)]
        );
    }

    // 28 Temmuz 2026: gercek bir tarayici/cihaz olmadan Web Push'un 404/410
    // "gecersiz abonelik otomatik silinir" hijyen mantigini (WebPushService)
    // gercekci bicimde test edebilmek icin - rastgele string'ler web-push
    // kutuphanesi tarafindan format hatasiyla (gercek P-256 anahtar degil diye)
    // reddedildigi icin, burada VAPID::createVapidKeys() ile KRIPTOGRAFIK
    // OLARAK GECERLI ama hicbir yerde kayitli olmayan bir p256dh/auth ureterek
    // mevcut bir push_subscriptions satirini gunceller. SADECE @example.com
    // (QATEST) hesaplarina ait aboneliklerde calisir.
    private function qaPushFixSubscription(Request $request): string
    {
        $email = (string) $request->query('email', '');
        if (! str_ends_with($email, '@example.com')) {
            return 'HATA: sadece @example.com test hesaplari icin calisir';
        }

        $family = \App\Models\FamilyUser::where('email', $email)->first();
        abort_if(! $family, 404, 'Aile kullanicisi bulunamadi');

        $subscription = DB::table('push_subscriptions')
            ->where('subscribable_type', \App\Models\FamilyUser::class)
            ->where('subscribable_id', $family->id)
            ->first();
        abort_if(! $subscription, 404, 'Bu hesaba ait push abonelik kaydi yok');

        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        DB::table('push_subscriptions')->where('id', $subscription->id)->update([
            'public_key' => $keys['publicKey'],
            'auth_token' => substr(str_replace(['+', '/'], ['-', '_'], base64_encode(random_bytes(16))), 0, 22),
        ]);

        return "OK: subscription #{$subscription->id} gecerli formatta (ama sahte) anahtarlarla guncellendi";
    }

    // 30 Temmuz 2026: admin gercek cihazinda hic push bildirimi almadigini
    // bildirdi - salt-okunur teshis: bu admin (ve genel olarak tum
    // Admin'ler) icin gercekten bir push_subscriptions kaydi var mi,
    // VAPID anahtarlari sunucuda tanimli mi gosterir. Hicbir veri degistirmez.
    private function qaAdminPushDiagnostic(): string
    {
        $out = '';
        $out .= 'VAPID public key tanimli mi: ' . (config('services.vapid.public_key') ? 'EVET' : 'HAYIR - .env eksik!') . "\n";
        $out .= 'VAPID private key tanimli mi: ' . (config('services.vapid.private_key') ? 'EVET' : 'HAYIR - .env eksik!') . "\n\n";

        $admins = DB::table('admins')->get();
        $out .= "Toplam admin sayisi: {$admins->count()}\n\n";

        foreach ($admins as $admin) {
            $subs = DB::table('push_subscriptions')
                ->where('subscribable_type', 'App\\Models\\Admin')
                ->where('subscribable_id', $admin->id)
                ->get();
            $out .= "Admin #{$admin->id} ({$admin->email}): {$subs->count()} push abonelik kaydi\n";
            foreach ($subs as $s) {
                $out .= "  - id={$s->id} endpoint=" . mb_substr($s->endpoint, 0, 60) . "... user_agent=" . mb_substr($s->user_agent ?? '-', 0, 80) . " olusturulma={$s->created_at}\n";
            }
        }

        return $out;
    }

    // 30 Temmuz 2026: admin "hic push bildirimi almadim" dedi ama abonelik
    // kayitlari mevcut ve log dosyasi bos (Log::warning hic tetiklenmemis) -
    // WebPushService'in normal akisi basari/hata detayini hicbir yere
    // yazmiyor. Bu eylem AYNI gonderim mantigini calistirir ama FCM'den
    // donen GERCEK yaniti (basarili mi, hangi HTTP kodu, hangi sebep)
    // doğrudan ekranda gosterir - salt teshis, veritabanini SADECE
    // gecersiz (404/410) abonelikleri silerek degistirir (WebPushService
    // ile birebir ayni hijyen davranisi).
    private function qaAdminPushTest(): string
    {
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject = config('services.vapid.subject');
        if (! $publicKey || ! $privateKey) {
            return 'HATA: VAPID anahtarlari tanimli degil';
        }

        $subscriptions = DB::table('push_subscriptions')
            ->where('subscribable_type', 'App\\Models\\Admin')
            ->get();

        if ($subscriptions->isEmpty()) {
            return 'HATA: hicbir admin icin push_subscriptions kaydi yok';
        }

        $webPush = new \Minishlink\WebPush\WebPush([
            'VAPID' => ['subject' => $subject, 'publicKey' => $publicKey, 'privateKey' => $privateKey],
        ]);

        $payload = json_encode([
            'title' => 'QATEST teshis bildirimi',
            'body' => 'Bu bildirimi goruyorsaniz push calisiyor demektir - ' . now()->format('H:i:s'),
            'url' => '/admin',
            'urgent' => true,
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]),
                $payload
            );
        }

        $out = "VAPID subject: {$subject}\n\n";
        try {
            foreach ($webPush->flush() as $report) {
                $endpointShort = mb_substr($report->getEndpoint(), 0, 70);
                try {
                    if ($report->isSuccess()) {
                        $out .= "BASARILI -> {$endpointShort}...\n";
                    } else {
                        $statusCode = $report->getResponse()?->getStatusCode();
                        $reason = $report->getReason();
                        $out .= "BASARISIZ -> {$endpointShort}... | HTTP {$statusCode} | Sebep: {$reason}\n";
                        if (in_array($statusCode, [404, 410], true)) {
                            DB::table('push_subscriptions')->where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
                            $out .= "  (bu abonelik gecersiz oldugu icin silindi - cihaz/tarayicida yeniden 'Bildirimleri Ac' butonuna basilmasi gerekiyor)\n";
                        }
                    }
                } catch (\Throwable $e) {
                    $out .= "HATA (bu abonelik islenirken) -> {$endpointShort}... | {$e->getMessage()}\n";
                }
            }
        } catch (\Throwable $e) {
            $out .= "GENEL HATA (flush sirasinda): {$e->getMessage()}\n";
        }

        return $out;
    }

    // 30 Temmuz 2026: PushSubscriptionController::store() eskiden TUM yeni
    // aboneliklere sabit olarak eski "aesgcm" sifreleme semasini yaziyordu
    // (bkz. o dosyadaki ayni tarihli yorum) - modern tarayicilar bunu artik
    // cozemiyor, bildirimler hicbir hata birakmadan sessizce dusuyordu. Bu
    // tek seferlik islem, veritabanindaki TUM push_subscriptions kayitlarini
    // (p256dh/auth anahtarlari AYNI kalir, sadece paketleme semasi degisir)
    // guncel "aes128gcm" semasina gecirir - kullanicilarin yeniden "Bildirimleri
    // Ac" butonuna basmasina GEREK KALMADAN mevcut abonelikler duzelir.
    private function fixPushEncoding(): string
    {
        $updated = DB::table('push_subscriptions')
            ->where('content_encoding', '!=', 'aes128gcm')
            ->update(['content_encoding' => 'aes128gcm']);

        return "OK: {$updated} abonelik kaydi 'aes128gcm' semasina guncellendi";
    }

    // 30 Temmuz 2026: onaylanmis sahiplenme/kayit basvurusunda kuruma maille
    // giden GECICI SIFREYI gercek posta kutusu olmadan test edebilmek icin -
    // must_change_password/email_verified_at durumunu BOZMADAN sadece sifreyi
    // bilinen bir degere set eder (password reset akisindan farkli olarak
    // must_change_password'u false YAPMAZ - asil test edilen senaryo zaten bu
    // alanin true kalmasi). SADECE @example.com (QATEST) hesaplarinda calisir.
    private function qaFacilitySetKnownPassword(Request $request): string
    {
        $email = (string) $request->query('email', '');
        if (! str_ends_with($email, '@example.com')) {
            return 'HATA: sadece @example.com test hesaplari icin calisir';
        }

        $user = \App\Models\FacilityUser::where('email', $email)->first();
        abort_if(! $user, 404, 'Kurum kullanicisi bulunamadi');

        DB::table('facility_users')->where('id', $user->id)->update([
            'password' => \Illuminate\Support\Facades\Hash::make('QaTestGecici123!'),
        ]);

        return "OK: {$email} sifresi 'QaTestGecici123!' olarak ayarlandi (must_change_password/email_verified_at DOKUNULMADI)";
    }

    // 28 Temmuz 2026: staging.bakimevleri.com'da agir bir yuk testi yapabilmek
    // icin - .htaccess'teki Basic Auth (.htpasswd) SADECE gercek ziyaretciyi/
    // arama motorunu engellemek icindi, /_ops zaten muaf. Bu eylem SADECE
    // "staging" ortaminda calisir (app()->environment() kontrolu) - production'da
    // yanlislikla cagrilirsa hicbir sey yapmadan hata doner. Test bitince
    // qa-staging-htpasswd-remove ile satir geri cikarilir, .htpasswd dosyasi
    // tamamen eski haline doner.
    private function qaStagingHtpasswdAdd(): string
    {
        abort_unless(app()->environment('staging'), 404, 'Bu eylem sadece staging ortaminda calisir');

        $path = base_path('.htpasswd');
        if (! File::exists($path)) {
            $path = '/home/bakimevl/public_html_staging/.htpasswd';
        }
        abort_unless(File::exists($path), 500, ".htpasswd bulunamadi: {$path}");

        $marker = 'qatest-loadtest';
        $existing = File::get($path);
        if (str_contains($existing, $marker)) {
            return "OK: {$marker} zaten ekli, tekrar eklenmedi";
        }

        $hash = password_hash('QaTestLoadTest2026!', PASSWORD_BCRYPT);
        File::append($path, "{$marker}:{$hash}\n");

        return "OK: gecici htpasswd kullanicisi eklendi ({$marker})";
    }

    private function qaStagingHtpasswdRemove(): string
    {
        abort_unless(app()->environment('staging'), 404, 'Bu eylem sadece staging ortaminda calisir');

        $path = base_path('.htpasswd');
        if (! File::exists($path)) {
            $path = '/home/bakimevl/public_html_staging/.htpasswd';
        }
        abort_unless(File::exists($path), 500, ".htpasswd bulunamadi: {$path}");

        $marker = 'qatest-loadtest';
        $lines = collect(explode("\n", File::get($path)))
            ->reject(fn ($line) => str_starts_with($line, "{$marker}:"))
            ->implode("\n");

        File::put($path, $lines);

        return "OK: gecici htpasswd kullanicisi kaldirildi";
    }

    // qa-setup ile olusturulan HER SEYI (facility_users, facilities, ve
    // bunlara bagli offer_requests/quotes/messages/platform_notifications)
    // kalici olarak siler - test bitince canli veritabaninda hicbir iz
    // birakmamak icin. SADECE slug'i 'qatest-' ile baslayan veya e-postasi
    // '@example.com' ile bitenlere dokunur.
    private function qaTeardown(Request $request): string
    {
        $facilityIds = DB::table('facilities')->where('slug', 'like', 'qatest-%')->pluck('id');
        $out = "Silinecek QATEST kurum: {$facilityIds->count()}\n";

        foreach ($facilityIds as $fid) {
            DB::table('quotes')->where('facility_id', $fid)->delete();
            $offerRequestIds = DB::table('offer_requests')->where('facility_id', $fid)->pluck('id');
            foreach ($offerRequestIds as $oid) {
                DB::table('messages')->where('offer_request_id', $oid)->delete();
                DB::table('quotes')->where('offer_request_id', $oid)->delete();
            }
            DB::table('offer_requests')->whereIn('id', $offerRequestIds)->delete();
            // NOT: bildirim temizligi facility_users SILINMEDEN once yapilmali,
            // yoksa notifiable_id sorgusu bos doner (28 Temmuz 2026'da fark edildi).
            DB::table('platform_notifications')->where('notifiable_type', 'App\\Models\\FacilityUser')
                ->whereIn('notifiable_id', DB::table('facility_users')->where('facility_id', $fid)->pluck('id'))
                ->delete();
            DB::table('facility_users')->where('facility_id', $fid)->delete();
            DB::table('wallet_topups')->where('facility_id', $fid)->delete();
            DB::table('balance_logs')->where('facility_id', $fid)->delete();
            DB::table('facility_questions')->where('facility_id', $fid)->delete();
        }
        // Yayin (broadcast) talepler facility_id=NULL tasir, yukaridaki
        // dongu bunlari yakalamaz - isim deseniyle ayrica temizlenir.
        $broadcastIds = DB::table('offer_requests')->whereNull('facility_id')->where('full_name', 'like', 'QATEST%')->pluck('id');
        foreach ($broadcastIds as $oid) {
            DB::table('messages')->where('offer_request_id', $oid)->delete();
            DB::table('quotes')->where('offer_request_id', $oid)->delete();
        }
        DB::table('offer_requests')->whereIn('id', $broadcastIds)->delete();
        $out .= "Silinen yayin (broadcast) teklif talebi: {$broadcastIds->count()}\n";

        DB::table('facilities')->whereIn('id', $facilityIds)->delete();

        // qatest aile hesaplarini ve onlara bagli her seyi de temizle
        $familyIds = DB::table('family_users')->where('email', 'like', '%@example.com')->pluck('id');
        foreach ($familyIds as $famId) {
            $orIds = DB::table('offer_requests')->where('family_user_id', $famId)->pluck('id');
            foreach ($orIds as $oid) {
                DB::table('messages')->where('offer_request_id', $oid)->delete();
                DB::table('quotes')->where('offer_request_id', $oid)->delete();
            }
            DB::table('offer_requests')->whereIn('id', $orIds)->delete();
            DB::table('platform_notifications')->where('notifiable_type', 'App\\Models\\FamilyUser')->where('notifiable_id', $famId)->delete();
        }
        DB::table('family_users')->whereIn('id', $familyIds)->delete();
        $out .= "Silinen QATEST aile hesabi: {$familyIds->count()}\n";

        $claimIds = DB::table('facility_claims')->where('applicant_email', 'like', '%@example.com')->pluck('id');
        DB::table('facility_claims')->whereIn('id', $claimIds)->delete();
        $out .= "Silinen QATEST sahiplenme basvurusu: {$claimIds->count()}\n";

        // 28 Temmuz 2026: kurum kayit basvurusu (registration), aile sorusu,
        // iletisim mesaji ve canli destek sohbeti akislari da test edildi -
        // bunlarin QATEST/@example.com izleri de temizlenir.
        $registrationIds = DB::table('facility_registrations')->where('applicant_email', 'like', '%@example.com')->pluck('id');
        DB::table('facility_registrations')->whereIn('id', $registrationIds)->delete();
        $out .= "Silinen QATEST kurum kayit basvurusu: {$registrationIds->count()}\n";

        $questionIds = DB::table('facility_questions')->where('asker_name', 'like', 'QATEST%')->pluck('id');
        DB::table('facility_questions')->whereIn('id', $questionIds)->delete();
        $out .= "Silinen QATEST aile sorusu: {$questionIds->count()}\n";

        $contactIds = DB::table('contact_messages')->where('email', 'like', '%@example.com')->pluck('id');
        DB::table('contact_messages')->whereIn('id', $contactIds)->delete();
        $out .= "Silinen QATEST iletisim mesaji: {$contactIds->count()}\n";

        $chatThreadIds = DB::table('chat_threads')->where('guest_name', 'like', 'QATEST%')->pluck('id');
        DB::table('chat_messages')->whereIn('chat_thread_id', $chatThreadIds)->delete();
        DB::table('chat_threads')->whereIn('id', $chatThreadIds)->delete();
        $out .= "Silinen QATEST canli sohbet thread: {$chatThreadIds->count()}\n";

        return $out;
    }

    // Sadece '@example.com' ile bitenlerde (QATEST hesaplari) - gercek
    // mail kutusuna erisim olmadan e-posta dogrulama gerektiren akislari
    // test edebilmek icin dogrulanmis sayar.
    private function qaVerifyFamilyEmail(Request $request): string
    {
        $email = (string) $request->query('email', '');
        if (! str_ends_with($email, '@example.com')) {
            return 'HATA: sadece @example.com test hesaplari dogrulanabilir';
        }

        $affected = DB::table('family_users')->where('email', $email)->update(['email_verified_at' => now()]);

        return $affected > 0 ? "OK: {$email} dogrulandi" : 'Kayit bulunamadi';
    }

    // Teklif verme 403 hatasini teshis etmek icin - QuoteController::store()
    // icindeki her kontrolun tek tek hangisinde basarisiz oldugunu gosterir.
    private function qaDebugQuote(Request $request): string
    {
        $offerRequestId = (int) $request->query('offer_request_id', 0);
        $facilityUserEmail = (string) $request->query('facility_user_email', '');

        $or = DB::table('offer_requests')->find($offerRequestId);
        $fu = DB::table('facility_users')->where('email', $facilityUserEmail)->first();
        if (! $or || ! $fu) {
            return 'HATA: offer_request veya facility_user bulunamadi';
        }
        $facility = DB::table('facilities')->find($fu->facility_id);
        $category = DB::table('facility_categories')->find($facility->facility_category_id);

        $brandSlug = 'bakimevleri';
        $categoryScope = config('brands.brands')[$brandSlug]['category_scope'] ?? [];

        $out = "offer_request.brand={$or->brand} (beklenen={$brandSlug}) uyumlu=" . ($or->brand === $brandSlug ? 'EVET' : 'HAYIR') . "\n";
        $out .= "facility.category.brand_scope={$category->brand_scope}\n";
        $out .= "brand category_scope icerigi: " . implode(',', $categoryScope) . "\n";
        $out .= "isInBrandScope uyumlu=" . (in_array($category->brand_scope, $categoryScope, true) ? 'EVET' : 'HAYIR') . "\n";
        $out .= "offer_request.facility_id={$or->facility_id} facility_user.facility_id={$fu->facility_id} uyumlu=" . ((int) $or->facility_id === (int) $fu->facility_id ? 'EVET' : 'HAYIR') . "\n";
        $out .= "[YAYIN YOLU] or.city_id={$or->city_id} facility.city_id={$facility->city_id} uyumlu=" . ((int) $or->city_id === (int) $facility->city_id ? 'EVET' : 'HAYIR') . "\n";
        $out .= "[YAYIN YOLU] or.facility_category_id={$or->facility_category_id} facility.facility_category_id={$facility->facility_category_id} uyumlu=" . ((int) $or->facility_category_id === (int) $facility->facility_category_id ? 'EVET' : 'HAYIR') . "\n";
        $out .= "offer_request.accepted_quote_id=" . ($or->accepted_quote_id ?? 'null') . "\n";
        $out .= "offer_request.status={$or->status}\n";
        $existingQuote = DB::table('quotes')->where('offer_request_id', $offerRequestId)->where('facility_id', $fu->facility_id)->exists();
        $out .= "zaten teklif verilmis mi=" . ($existingQuote ? 'EVET' : 'HAYIR') . "\n";
        $out .= "facility.free_quote_credits={$facility->free_quote_credits} balance={$facility->balance}\n";

        return $out;
    }

    // 28 Temmuz 2026: sahiplenme onay akisinin GERCEK kodunu (Admin\
    // FacilityClaimController::approve()) canli olarak calistirir - admin
    // oturumu olmadan bu HTTP akisini test edebilmek icin session'a gecici
    // bir admin_id koyup controller'i dogrudan cagirir. Ayni dosyadaki
    // reject() zaten mail-render-test ile ayrica dogrulandi.
    private function qaApproveClaim(Request $request): string
    {
        $claimId = (int) $request->query('claim_id', 0);
        $claim = \App\Models\FacilityClaim::find($claimId);
        if (! $claim) {
            return 'HATA: claim bulunamadi';
        }
        if (! str_ends_with($claim->applicant_email, '@example.com')) {
            return 'HATA: sadece @example.com test basvurulari onaylanabilir (guvenlik)';
        }

        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok';
        }
        session(['admin_id' => $admin->id]);

        $controller = app(\App\Http\Controllers\Admin\FacilityClaimController::class);
        $response = $controller->approve($request, $claim);

        $claim->refresh();
        $facilityUser = \App\Models\FacilityUser::where('email', $claim->applicant_email)->first();

        $out = "Claim durumu: {$claim->status}\n";
        $out .= $facilityUser
            ? "FacilityUser olusturuldu: #{$facilityUser->id} email={$facilityUser->email} must_change_password=" . ($facilityUser->must_change_password ? 'evet' : 'hayir') . "\n"
            : "FacilityUser OLUSTURULAMADI\n";
        $facility = DB::table('facilities')->find($claim->facility_id);
        $out .= "facility.is_claimed=" . ($facility->is_claimed ? 'evet' : 'hayir') . "\n";

        return $out;
    }

    // 3 Agustos 2026: qa-approve-claim ile ayni desen ama "yeni kurum kaydi"
    // (Public\FacilityRegistrationController) akisi icin - kullanicinin bu
    // oturumdaki ilk sikayeti tam bu akistaki mail/link sorunuyla ilgiliydi.
    // GERCEK Admin\FacilityRegistrationController::approve() kodunu calistirir.
    private function qaApproveRegistration(Request $request): string
    {
        $registrationId = (int) $request->query('registration_id', 0);
        $registration = \App\Models\FacilityRegistration::find($registrationId);
        if (! $registration) {
            return 'HATA: registration bulunamadi';
        }
        if (! str_ends_with($registration->applicant_email, '@example.com')) {
            return 'HATA: sadece @example.com test basvurulari onaylanabilir (guvenlik)';
        }

        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok';
        }
        session(['admin_id' => $admin->id]);

        $controller = app(\App\Http\Controllers\Admin\FacilityRegistrationController::class);
        $controller->approve($request, $registration);

        $registration->refresh();
        $facilityUser = \App\Models\FacilityUser::where('email', $registration->applicant_email)->first();

        $out = "Registration durumu: {$registration->status}\n";
        $out .= $facilityUser
            ? "FacilityUser olusturuldu: #{$facilityUser->id} email={$facilityUser->email} must_change_password=" . ($facilityUser->must_change_password ? 'evet' : 'hayir') . "\n"
            : "FacilityUser OLUSTURULAMADI\n";
        $facility = $facilityUser ? DB::table('facilities')->find($facilityUser->facility_id) : null;
        $out .= $facility ? "facility olusturuldu: #{$facility->id} {$facility->name} is_published=" . ($facility->is_published ? 'evet' : 'hayir') . "\n" : "facility OLUSTURULAMADI\n";

        return $out;
    }

    // qa-approve-claim testinin biraktigi izi temizler: facility'i tekrar
    // on-kayitli yapar (revertToPreRegistered ile ayni islem), test
    // FacilityUser'i ve FacilityClaim kaydini siler.
    private function qaCleanupClaim(Request $request): string
    {
        $claimId = (int) $request->query('claim_id', 0);
        $claim = \App\Models\FacilityClaim::find($claimId);
        if (! $claim) {
            return 'HATA: claim bulunamadi';
        }
        if (! str_ends_with($claim->applicant_email, '@example.com')) {
            return 'HATA: sadece @example.com test basvurulari temizlenebilir (guvenlik)';
        }

        $facility = \App\Models\Facility::find($claim->facility_id);
        if ($facility) {
            $facility->update(['is_claimed' => false, 'claimed_at' => null, 'invitation_status' => 'not_started', 'free_quote_credits' => 0]);
        }

        $facilityUser = \App\Models\FacilityUser::where('email', $claim->applicant_email)->first();
        if ($facilityUser) {
            DB::table('platform_notifications')->where('notifiable_type', 'App\\Models\\FacilityUser')->where('notifiable_id', $facilityUser->id)->delete();
            $facilityUser->delete();
        }
        DB::table('balance_logs')->where('facility_id', $claim->facility_id)->where('note', 'Sahiplenme onayi bonus hakki.')->delete();
        $claim->forceDelete();

        return "Temizlendi: kurum #{$claim->facility_id} tekrar on-kayitli yapildi, test facility_user ve claim silindi.";
    }

    private function qaRejectClaim(Request $request): string
    {
        $claimId = (int) $request->query('claim_id', 0);
        $claim = \App\Models\FacilityClaim::find($claimId);
        if (! $claim || ! str_ends_with($claim->applicant_email, '@example.com')) {
            return 'HATA: gecersiz claim';
        }

        $admin = DB::table('admins')->first();
        session(['admin_id' => $admin->id]);

        $request->merge(['admin_note' => 'QATEST: test amacli reddedildi.']);
        app(\App\Http\Controllers\Admin\FacilityClaimController::class)->reject($request, $claim);

        $claim->refresh();

        return "Claim durumu: {$claim->status}, admin_note={$claim->admin_note}";
    }

    // 28 Temmuz 2026: qa-reject-claim testi sonrasi kurumun invitation_status'u
    // 'claimed' olarak kalmisti (FacilityClaimController::store() bunu her
    // basvuruda yazar, reject() geri almiyor - uygulamanin kendi davranisi,
    // hata degil) - test oncesi gercek/orjinal duruma donmesi icin.
    private function qaResetInvitationStatus(Request $request): string
    {
        $id = (int) $request->query('id', 0);
        $facility = DB::table('facilities')->find($id);
        if (! $facility) {
            return 'HATA: kurum bulunamadi';
        }
        $phoneType = classify_phone_type($facility->phone);
        $status = match ($phoneType) {
            'mobile' => 'not_started',
            'landline' => 'landline_only',
            default => 'contact_missing',
        };
        DB::table('facilities')->where('id', $id)->update(['invitation_status' => $status, 'invitation_status_at' => now()]);

        return "OK: #{$id} invitation_status={$status}";
    }

    private function qaApproveTopup(Request $request): string
    {
        $id = (int) $request->query('topup_id', 0);
        $topup = \App\Models\WalletTopup::find($id);
        if (! $topup) {
            return 'HATA: topup bulunamadi';
        }
        $facility = DB::table('facilities')->find($topup->facility_id);
        if (! $facility || $facility->source !== 'qa_test') {
            return 'HATA: sadece qa_test kurumlarinin topup talebi onaylanabilir (guvenlik)';
        }
        $admin = DB::table('admins')->first();
        session(['admin_id' => $admin->id]);

        app(\App\Http\Controllers\Admin\WalletTopupController::class)->approve($topup);
        $topup->refresh();
        $facility = DB::table('facilities')->find($topup->facility_id);

        return "Topup durumu: {$topup->status}, kurum bakiyesi: {$facility->balance}";
    }

    private function qaRejectTopup(Request $request): string
    {
        $id = (int) $request->query('topup_id', 0);
        $topup = \App\Models\WalletTopup::find($id);
        if (! $topup) {
            return 'HATA: topup bulunamadi';
        }
        $facility = DB::table('facilities')->find($topup->facility_id);
        if (! $facility || $facility->source !== 'qa_test') {
            return 'HATA: sadece qa_test kurumlarinin topup talebi reddedilebilir (guvenlik)';
        }
        $admin = DB::table('admins')->first();
        session(['admin_id' => $admin->id]);
        $request->merge(['admin_note' => 'QATEST red.']);

        app(\App\Http\Controllers\Admin\WalletTopupController::class)->reject($request, $topup);
        $topup->refresh();

        return "Topup durumu: {$topup->status}";
    }

    // 30 Temmuz 2026: kullanici fark etti - bir kurum "Ön Kayıt" butonuyla
    // (revertToPreRegistered()) tekrar sahipsiz hale getirildiginde, o
    // sahiplenmeyle acilan FacilityUser hesabi askiya alinmiyordu; kurum
    // artik "sahipsiz" gorunse bile o hesap Kurum Yetkilileri'nde "Aktif"
    // kalip kurum panelinden giris yapmaya devam edebiliyordu. Kod ileri
    // yonelik duzeltildi (bkz. FacilityController::revertToPreRegistered) -
    // bu iki uc, o duzeltmeden ONCE olusmus mevcut/eski bozuk durumu
    // (is_claimed=false ama facility_user hala 'active') tespit/duzeltir.
    private function facilityUserUnclaimedAudit(): string
    {
        $rows = DB::table('facility_users as fu')
            ->leftJoin('facilities as f', 'f.id', '=', 'fu.facility_id')
            ->where('fu.status', 'active')
            ->where(function ($q) {
                $q->whereNull('f.id')->orWhere('f.is_claimed', false);
            })
            ->select('fu.id', 'fu.name', 'fu.email', 'f.id as facility_id', 'f.name as facility_name')
            ->get();

        $out = "Sahipsiz (is_claimed=false) veya kurumu yok/silinmis ama hala 'active' olan kurum yetkilisi: {$rows->count()}\n\n";
        foreach ($rows as $r) {
            $out .= "facility_user #{$r->id} {$r->name} <{$r->email}> -> " . ($r->facility_id ? "facility #{$r->facility_id} {$r->facility_name}" : '(kurum yok/silinmis)') . "\n";
        }

        return $out;
    }

    private function facilityUserUnclaimedFix(): string
    {
        $affected = DB::table('facility_users as fu')
            ->leftJoin('facilities as f', 'f.id', '=', 'fu.facility_id')
            ->where('fu.status', 'active')
            ->where(function ($q) {
                $q->whereNull('f.id')->orWhere('f.is_claimed', false);
            })
            ->update(['fu.status' => 'suspended', 'fu.updated_at' => now()]);

        return "Askiya alinan kurum yetkilisi hesabi: {$affected}";
    }

    // 30 Temmuz 2026: /iletisim uzerinden gercek bir kullanici (Cemil Keleş)
    // #130 "Özel Mia Huzurevi ve Yaşlı Bakım Merkezi" icin sehrin yanlis
    // (Adiyaman) etiketlendigini, adresin aslinda Ankara/Gölbaşı oldugunu
    // bildirdi - kurumun kendi adres metninde "Gölbaşı/Ankara" ve 0312 alan
    // kodu acikca goruluyordu, kontrol edilip doğrulandı. Tek bir kurumun
    // sehir/ilce alanini elle duzeltmek icin - toplu/otomatik degil, id ve
    // dogru city_slug elle verilip onaylanarak calisir.
    private function facilitySetCity(Request $request): string
    {
        $id = (int) $request->query('id', 0);
        $citySlug = (string) $request->query('city_slug', '');
        $districtName = $request->query('district', null);

        $facility = DB::table('facilities')->where('id', $id)->first();
        if (! $facility) {
            return "HATA: #{$id} bulunamadi";
        }

        $city = DB::table('cities')->where('slug', $citySlug)->first();
        if (! $city) {
            return "HATA: gecersiz city_slug '{$citySlug}'";
        }

        $update = ['city_id' => $city->id, 'updated_at' => now()];
        $districtMatchInfo = '';
        if ($districtName) {
            $update['district'] = $districtName;
            $district = DB::table('districts')->where('city_id', $city->id)->where('name', $districtName)->first();
            if ($district) {
                $update['district_id'] = $district->id;
                $districtMatchInfo = ", ilce_id_eslesti (#{$district->id})";
            } else {
                $update['district_id'] = null;
                $districtMatchInfo = ", UYARI: '{$districtName}' adinda {$city->name} icin ilce bulunamadi, district_id NULL yapildi";
            }
        }

        DB::table('facilities')->where('id', $id)->update($update);

        $oldDistrictCity = null;
        if ($facility->district_id) {
            $oldDistrict = DB::table('districts')->where('id', $facility->district_id)->first();
            if ($oldDistrict) {
                $oldCity = DB::table('cities')->where('id', $oldDistrict->city_id)->first();
                $oldDistrictCity = $oldCity->name ?? null;
            }
        }

        return "OK: #{$id} {$facility->name} -> sehir={$city->name} (city_id={$city->id})" . ($districtName ? ", ilce={$districtName}" : '') . $districtMatchInfo
            . "\nOnceki durum: city_id={$facility->city_id}, district_id=" . ($facility->district_id ?? 'NULL') . ($oldDistrictCity ? " (o ilcenin gercek ili: {$oldDistrictCity})" : '')
            . "\nSlug: {$facility->slug}";
    }

    // 30 Temmuz 2026: "gorsel yukleyemiyorum" sikayeti icin - kurum panelindeki
    // yukleme her gorsel icin 5MB'a, request'te en fazla 10 gorsele izin veriyor
    // (bkz. Facility\ProfileController::uploadImage) ama bu, PHP'nin kendi
    // upload_max_filesize/post_max_size ayarlarindan BUYUK olabilir - oyle ise
    // dosya Laravel'e hic ulasmadan PHP tarafindan sessizce reddediliyor,
    // hicbir Laravel log kaydi birakmiyor. Bu salt-okunur uc gercek sunucu
    // degerlerini gosterir.
    private function phpUploadLimits(): string
    {
        return "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n"
            . "post_max_size: " . ini_get('post_max_size') . "\n"
            . "max_file_uploads: " . ini_get('max_file_uploads') . "\n"
            . "memory_limit: " . ini_get('memory_limit') . "\n"
            . "max_execution_time: " . ini_get('max_execution_time') . "s\n"
            . "PHP_SAPI: " . PHP_SAPI . "\n"
            . "php.ini yolu: " . php_ini_loaded_file() . "\n"
            . "ek taranan ini dosyalari: " . php_ini_scanned_files() . "\n"
            . "user_ini.filename: " . ini_get('user_ini.filename') . "\n"
            . "user_ini.cache_ttl: " . ini_get('user_ini.cache_ttl') . "\n"
            . "(Uygulama tarafi limiti: tek gorsel basina 5MB, istek basina en fazla 10 gorsel - bkz. Facility/ProfileController::uploadImage)";
    }

    // 25 Agustos 2026: kullanicinin bildirdigi "/tmp: No space left on device"
    // hatasi icin - hosting firmasi /tmp'yi buyutemeyecegini soyledi, bu
    // yuzden MySQL'in gecici tabloyu DISKE (/tmp) DUSMEDEN once bellekte
    // (RAM) tutabilecegi esik degerleri (tmp_table_size/max_heap_table_size)
    // ve sunucu baslangicindan beri diske dusen toplam gecici tablo sayisini
    // gosterir. Bu degerler kucukse (ör. varsayilan 16-64MB), hosting
    // firmasindan bu ikisini artirmasi istenebilir - fiziksel /tmp
    // buyutmekten cok daha kolay bir istek, cunku ek disk degil sadece
    // MySQL'in zaten ayrilmis RAM'ini daha comert kullanmasini saglar.
    private function mysqlTmpDiagnostics(): string
    {
        $vars = DB::select("SHOW VARIABLES WHERE Variable_name IN ('tmp_table_size','max_heap_table_size','tmpdir')");
        $status = DB::select("SHOW GLOBAL STATUS WHERE Variable_name IN ('Created_tmp_tables','Created_tmp_disk_tables')");

        $out = "MySQL gecici tablo ayarlari:\n";
        foreach ($vars as $v) {
            $out .= "  {$v->Variable_name} = {$v->Value}\n";
        }
        $out .= "\nSunucu baslangicindan beri (bu hesabin veritabani sunucusunda TUM baglantilar dahil):\n";
        foreach ($status as $s) {
            $out .= "  {$s->Variable_name} = {$s->Value}\n";
        }
        $out .= "\nOrnek: Created_tmp_disk_tables / Created_tmp_tables orani yuksekse, tmp_table_size/max_heap_table_size kucuk demektir - hosting firmasindan bu ikisini (ör. 64M'ye) artirmasini isteyin, bu /tmp'yi buyutmekten farkli, daha kolay bir taleptir.";

        return $out;
    }

    /**
     * 29 Agustos 2026: kullanicinin talebi - yeni eklenen kamuya acik
     * "bos yer" alani (bkz. Facility::usesGenderSplitVacancy()) TUM mevcut
     * kurumlar icin varsayilan olarak "Var" ile baslasin (bos/belirtilmedi
     * degil). Yasli bakim kategorilerinde (kogus cinsiyete gore ayrildigi
     * icin) bay+bayan ikisi de, diger kategorilerde tek genel alan true
     * yapilir. Soft-delete'li kurumlara dokunulmaz. Idempotent - tekrar
     * calistirilirsa zaten "Var" olanlari yine "Var" yapar, zarar vermez.
     */
    private function vacancySetDefaultAvailable(): string
    {
        $genderSplitCategoryIds = DB::table('facility_categories')
            ->where('brand_scope', 'yasli-bakim')
            ->pluck('id');

        $genderSplitCount = DB::table('facilities')
            ->whereNull('deleted_at')
            ->whereIn('facility_category_id', $genderSplitCategoryIds)
            ->update(['vacancy_male' => true, 'vacancy_female' => true]);

        $generalCount = DB::table('facilities')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($genderSplitCategoryIds) {
                $q->whereNotIn('facility_category_id', $genderSplitCategoryIds)
                    ->orWhereNull('facility_category_id');
            })
            ->update(['vacancy_general' => true]);

        return "OK: bay/bayan alani 'Var' yapilan yasli bakim kurumu sayisi = {$genderSplitCount}, genel 'Var' yapilan diger kurum sayisi = {$generalCount}";
    }

    /**
     * 29 Agustos 2026: kullanicinin talebi - "kullanicilar hangi kurum
     * turunu en cok ariyor" sorusuna Google Trends gibi harici bir kaynak
     * yerine, PLATFORMUN KENDI gercek kullanici verisiyle (goruntulenme +
     * teklif/ziyaret talebi) cevap verir - tahminden cok daha guvenilir,
     * cunku gercekten bu siteye gelen ziyaretcilerin davranisi.
     */
    private function categoryDemandStats(): string
    {
        $views = DB::table('facilities')
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->whereNull('facilities.deleted_at')
            ->where('facilities.is_published', true)
            ->selectRaw('facility_categories.brand_scope, count(*) as kurum_sayisi, sum(facilities.views_count) as toplam_goruntulenme, avg(facilities.views_count) as ortalama_goruntulenme')
            ->groupBy('facility_categories.brand_scope')
            ->orderByDesc('toplam_goruntulenme')
            ->get();

        $offerCounts = DB::table('offer_requests')
            ->join('facilities', 'facilities.id', '=', 'offer_requests.facility_id')
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->selectRaw('facility_categories.brand_scope, count(*) as adet')
            ->groupBy('facility_categories.brand_scope')
            ->pluck('adet', 'brand_scope');

        $visitCounts = DB::table('visit_requests')
            ->join('facilities', 'facilities.id', '=', 'visit_requests.facility_id')
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->selectRaw('facility_categories.brand_scope, count(*) as adet')
            ->groupBy('facility_categories.brand_scope')
            ->pluck('adet', 'brand_scope');

        $out = "Kategoriye gore GERCEK kullanici verisi (yayinda, silinmemis kurumlar - toplam goruntulenmeye gore siralandi):\n\n";
        foreach ($views as $row) {
            $out .= "{$row->brand_scope}:\n";
            $out .= "  Kurum sayisi: {$row->kurum_sayisi}\n";
            $out .= "  Toplam goruntulenme: ".number_format((float) $row->toplam_goruntulenme)."\n";
            $out .= "  Kurum basina ortalama goruntulenme: ".number_format((float) $row->ortalama_goruntulenme, 1)."\n";
            $out .= "  Toplam teklif talebi: ".($offerCounts[$row->brand_scope] ?? 0)."\n";
            $out .= "  Toplam ziyaret talebi: ".($visitCounts[$row->brand_scope] ?? 0)."\n\n";
        }

        return $out;
    }

    /**
     * 31 Agustos 2026: kullanicinin talebi - "bursa anaokulu/kres" icin
     * SEO degeri yuksek bir rehber makalesi. Bir rakip sitenin (bursaanaokullari.com.tr)
     * icerik YAPISINDAN (baslik sirasi, konu basliklari) ilham alindi ama
     * metin BIREBIR KOPYALANMADI - kendi cumlelerimizle, kendi ic
     * linklerimizle (kurumlar/rehber sayfalarina) yeniden yazildi. Idempotent -
     * ayni brand+slug'a tekrar cagrilirsa GuideController zaten ContentPage'i
     * gunceller (updateOrCreate benzeri), yeni satir olusturmaz.
     */
    private function seedBursaKresRehberi(): string
    {
        $body = <<<'HTML'
<p>Bursa'da çocuğunuz için kreş veya anaokulu ararken karşınıza onlarca seçenek çıkabilir — hangi ilçede, ne tür bir programda, hangi bütçeyle karar vereceğinizi netleştirmek zaman alabilir. Bu rehberde Bursa'da kreş/anaokulu seçerken dikkat etmeniz gereken noktaları ve ilçe ilçe nasıl arama yapabileceğinizi anlatıyoruz.</p>

<h2>Bursa'da Kreş ve Anaokulu Seçenekleri</h2>
<p>Bursa; Nilüfer, Osmangazi, Yıldırım gibi büyük ilçelerden Gemlik, İnegöl, Mudanya, Gürsu ve Kestel gibi ilçelere kadar geniş bir alanda yüzlerce kreş, gündüz bakımevi ve anaokulu barındırıyor. Genel olarak üç ana seçenekle karşılaşırsınız:</p>
<ul>
  <li><strong>Kreş / gündüz bakımevi:</strong> Genellikle 0-3 yaş arası çocuklara, çalışan ebeveynlerin tam gün ihtiyacına yönelik hizmet verir.</li>
  <li><strong>Anaokulu:</strong> Genellikle 3-6 yaş arası, okul öncesi eğitime daha çok ağırlık veren kurumlar.</li>
  <li><strong>Özel eğitim ve gelişim destekli kurumlar:</strong> Gelişimsel destek ihtiyacı olan çocuklar için ek programlar sunan merkezler.</li>
</ul>

<h2>Tam Gün mü, Yarım Gün mü?</h2>
<p>Bu tercih büyük ölçüde ailenin çalışma düzenine bağlıdır.</p>
<h3>Tam gün kimler için uygun?</h3>
<p>Her iki ebeveyn de tam zamanlı çalışıyorsa, ya da çocuğun düzenli bir günlük rutine (yemek, uyku, oyun, eğitim) ihtiyacı varsa tam gün program genelde daha pratik bir çözüm olur.</p>
<h3>Yarım gün kimler için uygun?</h3>
<p>Evde bakım desteği olan, ya da çocuğunu sadece belirli saatlerde sosyalleşme/eğitim amacıyla göndermek isteyen aileler için yarım gün programlar hem bütçe hem uyum açısından daha esnek olabilir.</p>

<h2>Bursa'da İlçe İlçe Kreş ve Anaokulu Arama</h2>
<p>Bursa'nın büyük ilçelerinde kayıtlı kreş ve anaokullarını, güncel iletişim bilgileri ve hizmet detaylarıyla birlikte aşağıdaki sayfalardan inceleyebilirsiniz:</p>
<ul>
  <li><a href="/rehber/cocuk/bursa/nilufer">Nilüfer kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/osmangazi">Osmangazi kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/yildirim">Yıldırım kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/gemlik">Gemlik kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/inegol">İnegöl kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/mudanya">Mudanya kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/gursu">Gürsu kreş ve anaokulları</a></li>
  <li><a href="/rehber/cocuk/bursa/kestel">Kestel kreş ve anaokulları</a></li>
</ul>
<p>Bu sayfalarda kurumları filtreleyebilir, karşılaştırabilir ve doğrudan ücret/kontenjan bilgisi talep edebilirsiniz.</p>

<h2>Kreş Seçerken Nelere Dikkat Edilmeli?</h2>
<h3>Eğitim ve gelişim programı</h3>
<p>Kurumun hangi eğitim yaklaşımını (Montessori, MEB müfredatı destekli, karma program vb.) uyguladığını, sınıf başına düşen çocuk sayısını ve rehberlik/psikolog desteği olup olmadığını sorun.</p>
<h3>Fiziki imkanlar ve güvenlik</h3>
<p>Oyun alanı, güvenlik önlemleri (giriş-çıkış kontrolü, kamera sistemi), hijyen koşulları ve bina/oda düzeni yerinde görülmeden karar vermemekte fayda var.</p>
<h3>Ulaşım ve servis</h3>
<p>Servis hizmeti olup olmadığını, hangi güzergahları kapsadığını mutlaka önceden netleştirin — özellikle iş yerine uzak bir kreş tercih ediyorsanız bu kritik bir kriter.</p>
<h3>Yemek ve uyku düzeni</h3>
<p>Günlük beslenme programını, özel diyet ihtiyaçlarına (alerji vb.) uyum sağlanıp sağlanmadığını ve uyku/dinlenme düzenini sorun.</p>
<h3>Fiyat ve ek ücretler</h3>
<p>Bursa'da kreş/anaokulu aylık ücretleri kuruma, ilçeye ve programa (tam gün/yarım gün) göre değişmekle birlikte, genel olarak aylık <strong>15.000 TL ile 38.000 TL</strong> arasında bir aralıkta seyrediyor. Yaş grubuna göre de fark oluşabiliyor — daha küçük yaş grupları (bebek/kreş dönemi) genellikle daha yüksek bakım oranı gerektirdiği için üst sınıra daha yakın fiyatlanabiliyor.</p>
<p>Aylık ücrete ek olarak servis, yemek, materyal gibi kalemlerin ayrı faturalandırılıp faturalandırılmadığını baştan netleştirmek, ileride sürpriz yaşamamak için önemlidir. Bu rakamlar genel bir fikir vermesi içindir — kesin ve güncel rakam için kurumla doğrudan iletişime geçmenizi ya da platformumuz üzerinden ücretsiz teklif talep etmenizi öneririz.</p>

<h2>Devlet mi, Özel mi?</h2>
<p>Devlet/belediye bünyesindeki anaokulları genellikle daha uygun maliyetlidir ancak kontenjanları sınırlıdır ve bekleme listesi olabilir. Özel kreş/anaokullarda ise kontenjan bulma ihtimali daha yüksek, program çeşitliliği (yabancı dil, sanat atölyeleri, yüzme vb.) daha geniş olabilir; karşılığında ücretler de değişkenlik gösterir. Hangisinin ailenize uygun olduğu; bütçe, konum ve çocuğunuzun ihtiyaçlarına göre değişir.</p>

<h2>Sıkça Sorulan Sorular</h2>
<p><strong>Kaç aylık/yaşındaki çocuklar kreşe başlayabilir?</strong><br>Çoğu kreş 0-1 yaş arası bebekleri de kabul edebiliyor, anaokulları genelde 3 yaş ve üzeri çocuklara yönelik. Kesin yaş aralığı kurumdan kuruma değişir.</p>
<p><strong>Kayıt için hangi belgeler istenir?</strong><br>Genellikle kimlik fotokopisi, sağlık raporu/aşı kartı ve fotoğraf istenir; kuruma göre ek belgeler de talep edilebilir.</p>
<p><strong>Deneme günü/ziyaret imkanı var mı?</strong><br>Çoğu kurum kayıt öncesi yerinde ziyarete ve bazen bir deneme gününe açıktır — karar vermeden önce mutlaka sormanızı öneririz.</p>

<p>Bursa'daki kreş ve anaokullarını ilçe, hizmet türü ve bütçenize göre karşılaştırmak, doğru ücret bilgisini almak için <a href="/kurumlar">kurumlar sayfamızdan</a> arama yapabilir veya ilgilendiğiniz kurumdan doğrudan ücretsiz teklif isteyebilirsiniz.</p>
HTML;

        $page = \App\Models\ContentPage::updateOrCreate(
            ['brand' => 'bakimevibul', 'slug' => 'cocuk-bursa-kres-anaokulu-rehberi'],
            [
                'type' => 'guide',
                'title' => 'Bursa Kreş ve Anaokulu Rehberi',
                'summary' => 'Bursa\'da kreş ve anaokulu seçerken dikkat edilmesi gerekenler, ilçe ilçe arama ve genel fiyat bilgisi.',
                'body' => $body,
            ]
        );

        return "OK: sayfa kaydedildi/guncellendi -> id={$page->id}, brand=bakimevibul, slug=cocuk-bursa-kres-anaokulu-rehberi";
    }

    /**
     * 31 Agustos 2026: kullanicinin talebi - seedBursaKresRehberi() ile
     * AYNI desen, yasli bakim bolumu icin. Fiyat bilgisi Bursa'ya OZEL
     * kaynaklardan alindi (resmi il muduru ucret tablosu Manisa cikti,
     * KULLANILMADI - sadece gercekten Bursa'ya ait rakamlar kullanildi).
     */
    private function seedBursaBakimeviRehberi(): string
    {
        $body = <<<'HTML'
<p>Bir aile üyesi için Bursa'da bakımevi veya huzurevi ararken; oda tipi, hizmet kapsamı, konum ve fiyat gibi pek çok kriteri aynı anda değerlendirmeniz gerekir. Bu rehberde Bursa'da bakımevi/huzurevi seçenekleri, ilçe ilçe nasıl arama yapabileceğiniz ve dikkat edilmesi gereken noktaları anlatıyoruz.</p>

<h2>Bursa'da Bakımevi ve Huzurevi Seçenekleri</h2>
<p>Bursa'da Nilüfer, Osmangazi, Yıldırım gibi merkez ilçelerden Gemlik, İnegöl, Mudanya, Gürsu ve Kestel'e kadar geniş bir alanda huzurevi ve yaşlı bakım merkezi hizmeti veren kurumlar bulunuyor. Genel olarak iki ana kategoriyle karşılaşırsınız:</p>
<ul>
  <li><strong>Huzurevi:</strong> Günlük yaşam desteğine ihtiyaç duyan, kendi ihtiyaçlarının büyük kısmını karşılayabilen yaşlılara yönelik konaklama ve bakım hizmeti.</li>
  <li><strong>Yaşlı bakım merkezi:</strong> Sağlık takibi, hemşire/doktor desteği ve günlük bakıma daha yoğun ihtiyaç duyan yaşlılar için ek sağlık hizmetleri içeren kurumlar.</li>
</ul>

<h2>Bursa'da İlçe İlçe Bakımevi Arama</h2>
<p>Bursa'nın ilçelerinde kayıtlı bakımevi ve huzurevlerini, güncel iletişim bilgileri ve hizmet detaylarıyla birlikte aşağıdaki sayfalardan inceleyebilirsiniz:</p>
<ul>
  <li><a href="/rehber/yasli-bakim/bursa/nilufer">Nilüfer bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/osmangazi">Osmangazi bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/yildirim">Yıldırım bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/gemlik">Gemlik bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/inegol">İnegöl bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/mudanya">Mudanya bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/gursu">Gürsu bakımevi ve huzurevleri</a></li>
  <li><a href="/rehber/yasli-bakim/bursa/kestel">Kestel bakımevi ve huzurevleri</a></li>
</ul>
<p>Bu sayfalarda kurumları filtreleyebilir, karşılaştırabilir ve doğrudan ücret/boş yer bilgisi talep edebilirsiniz.</p>

<h2>Bakımevi Seçerken Nelere Dikkat Edilmeli?</h2>
<h3>Sağlık personeli ve tıbbi destek</h3>
<p>7/24 hemşire desteği olup olmadığını, doktor kontrolünün sıklığını ve acil bir durumda izlenecek süreci mutlaka sorun.</p>
<h3>Güvenlik ve hijyen</h3>
<p>Bina erişilebilirliği (asansör, rampa), yangın/acil çıkış düzeni ve genel hijyen koşulları yerinde görülmeden karar vermemekte fayda var.</p>
<h3>Ziyaret saatleri ve iletişim</h3>
<p>Aile ziyaret saatlerinin ne kadar esnek olduğunu, görüntülü görüşme imkânı olup olmadığını önceden netleştirin.</p>
<h3>Beslenme ve özel bakım programları</h3>
<p>Diyet/beslenme kısıtlaması olan (şekersiz, tuzsuz vb.) sakinler için özel program uygulanıp uygulanmadığını, Alzheimer/demans hastalarına yönelik özel bir yaklaşım olup olmadığını sorun.</p>

<h2>Bursa'da Bakımevi Fiyatları Ne Kadar?</h2>
<p>Bursa'da özel bakımevi/huzurevi ücretleri oda tipine göre değişiyor. Genel olarak <strong>paylaşımlı (3-4 kişilik) odalarda aylık 40.000 TL ile 50.000 TL</strong>, <strong>iki kişilik odalarda aylık 55.000 TL ile 60.000 TL</strong>, <strong>tek kişilik odalarda ise aylık 70.000 TL ve üzeri</strong> bir aralıkta seyrediyor. Yoğun sağlık bakımı gerektiren veya suit/lüks oda seçeneklerinde ücret daha da yükselebiliyor. Belediyeye bağlı kurumlarda ücretler genellikle özel kurumlara göre daha uygun oluyor.</p>
<p>Bu rakamlar genel bir fikir vermesi içindir, KDV ve hizmet kapsamına göre değişebilir — kesin ve güncel rakam için kurumla doğrudan iletişime geçmenizi ya da platformumuz üzerinden ücretsiz teklif talep etmenizi öneririz.</p>

<h2>Devlet mi, Özel mi?</h2>
<p>Devlet/belediye huzurevlerinde ücretler daha uygundur ancak kontenjan ve bekleme listesi süreci olabilir; başvuru genellikle belirli sosyal/ekonomik kriterlere tabidir. Özel kurumlarda kontenjan bulma ihtimali daha yüksektir, oda ve hizmet seçenekleri daha geniştir; karşılığında ücretler de değişkenlik gösterir.</p>

<h2>Sıkça Sorulan Sorular</h2>
<p><strong>Bakımevine yerleşim için hangi belgeler gerekir?</strong><br>Genellikle kimlik fotokopisi, sağlık raporu ve yakın onayı istenir; kuruma göre ek belgeler talep edilebilir.</p>
<p><strong>Aile ziyarete istediği zaman gelebilir mi?</strong><br>Çoğu kurumun belirli ziyaret saatleri vardır, bazı kurumlar önceden haber verilmesini ister. Kurumla netleştirmenizi öneririz.</p>
<p><strong>Kurumu yerinde görmek mümkün mü?</strong><br>Karar vermeden önce kurumu yerinde ziyaret etmenizi, oda ve ortak alanları görmenizi öneririz.</p>

<p>Bursa'daki bakımevi ve huzurevlerini ilçe, hizmet türü ve bütçenize göre karşılaştırmak, doğru ücret bilgisini almak için <a href="/kurumlar">kurumlar sayfamızdan</a> arama yapabilir veya ilgilendiğiniz kurumdan doğrudan ücretsiz teklif isteyebilirsiniz.</p>
HTML;

        $page = \App\Models\ContentPage::updateOrCreate(
            ['brand' => 'bakimevleri', 'slug' => 'yasli-bakim-bursa-bakimevi-huzurevi-rehberi'],
            [
                'type' => 'guide',
                'title' => 'Bursa Bakımevi ve Huzurevi Rehberi',
                'summary' => 'Bursa\'da bakımevi/huzurevi seçerken dikkat edilmesi gerekenler, ilçe ilçe arama ve genel fiyat bilgisi.',
                'body' => $body,
            ]
        );

        return "OK: sayfa kaydedildi/guncellendi -> id={$page->id}, brand=bakimevleri, slug=yasli-bakim-bursa-bakimevi-huzurevi-rehberi";
    }

    /**
     * 31 Agustos 2026: kullanicinin talebi - seedBursaKresRehberi() ile
     * AYNI desen, rehabilitasyon bolumu icin. Fiyat bilgisi icin Bursa'ya
     * ozel guvenilir bir kaynak bulunamadi - bu yuzden BILEREK "Bursa'ya
     * ozel degil, ulusal ortalama" diye acikca belirtildi, yanlis sehre
     * ait veri Bursa'ya aitmis gibi sunulmadi.
     */
    private function seedBursaRehabilitasyonRehberi(): string
    {
        $body = <<<'HTML'
<p>Bursa'da fizik tedavi veya rehabilitasyon hizmeti ararken; uzmanlık alanı, cihaz/ekipman imkânları, konum ve fiyat gibi birçok kriteri bir arada değerlendirmeniz gerekir. Bu rehberde Bursa'da rehabilitasyon merkezi seçenekleri, ilçe ilçe nasıl arama yapabileceğiniz ve dikkat edilmesi gereken noktaları anlatıyoruz.</p>

<h2>Bursa'da Rehabilitasyon Hizmeti Seçenekleri</h2>
<p>Bursa'da Nilüfer, Osmangazi, Yıldırım gibi merkez ilçelerden Gemlik, İnegöl, Mudanya, Gürsu ve Kestel'e kadar geniş bir alanda rehabilitasyon hizmeti veren kurumlar bulunuyor. Genel olarak şu alanlarla karşılaşırsınız:</p>
<ul>
  <li><strong>Fizik tedavi ve rehabilitasyon:</strong> Ortopedik rahatsızlıklar, ameliyat sonrası iyileşme ve genel hareket kabiliyetini artırmaya yönelik hizmetler.</li>
  <li><strong>Nörolojik rehabilitasyon:</strong> İnme, felç sonrası veya nörolojik rahatsızlıklara bağlı fonksiyon kaybının giderilmesine yönelik özel programlar.</li>
  <li><strong>Özel eğitim ve gelişim merkezleri:</strong> Gelişimsel destek ihtiyacı olan çocuklar ve bireyler için ek terapi programları.</li>
</ul>

<h2>Bursa'da İlçe İlçe Rehabilitasyon Merkezi Arama</h2>
<p>Bursa'nın ilçelerinde kayıtlı rehabilitasyon merkezlerini, güncel iletişim bilgileri ve hizmet detaylarıyla birlikte aşağıdaki sayfalardan inceleyebilirsiniz:</p>
<ul>
  <li><a href="/rehber/rehabilitasyon/bursa/nilufer">Nilüfer rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/osmangazi">Osmangazi rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/yildirim">Yıldırım rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/gemlik">Gemlik rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/inegol">İnegöl rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/mudanya">Mudanya rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/gursu">Gürsu rehabilitasyon merkezleri</a></li>
  <li><a href="/rehber/rehabilitasyon/bursa/kestel">Kestel rehabilitasyon merkezleri</a></li>
</ul>
<p>Bu sayfalarda kurumları filtreleyebilir, karşılaştırabilir ve doğrudan ücret/randevu bilgisi talep edebilirsiniz.</p>

<h2>Rehabilitasyon Merkezi Seçerken Nelere Dikkat Edilmeli?</h2>
<h3>Uzman kadro</h3>
<p>Kadroda hangi uzmanların (fizyoterapist, doktor, ergoterapist vb.) bulunduğunu ve deneyim düzeylerini sorun.</p>
<h3>Cihaz ve ekipman</h3>
<p>İhtiyacınıza uygun cihaz/ekipmanın (hidroterapi, robotik rehabilitasyon vb.) kurumda bulunup bulunmadığını kontrol edin.</p>
<h3>Seans süresi ve programı</h3>
<p>Bir seansın ortalama süresini, haftalık önerilen seans sayısını ve tedavi sürecinin nasıl planlandığını netleştirin.</p>
<h3>Raporlama ve ev programı</h3>
<p>İlerlemenin aile/hekimle düzenli paylaşılıp paylaşılmadığını ve evde uygulanabilecek bir egzersiz programı verilip verilmediğini sorun.</p>

<h2>SGK mi, Özel mi?</h2>
<p>Fizik tedavi ve rehabilitasyon hizmetlerinin önemli bir kısmı, doktor sevkiyle SGK kapsamında devlet hastaneleri veya SGK anlaşmalı özel merkezlerde ücretsiz veya düşük katkı payıyla alınabiliyor. Özel (SGK dışı) merkezler ise genellikle daha hızlı randevu, daha esnek seans saatleri ve bazı özel terapi yöntemleri sunar; karşılığında ücret cepten ödenir.</p>

<h2>Rehabilitasyon Ücretleri Ne Kadar?</h2>
<p>Özel rehabilitasyon merkezlerinde tek seans ücreti genel olarak <strong>2.000 TL ile 2.500 TL</strong> aralığında, 10 seanslık paketler ise <strong>6.000 TL ile 18.000 TL</strong> arasında değişebiliyor. Bu rakamlar Bursa'ya özel değil, genel bir ulusal ortalamayı yansıtıyor — kurum, kullanılan yöntem ve uzmanlık düzeyine göre fiyatlar değişebilir. Kesin ve güncel rakam için kurumla doğrudan iletişime geçmenizi ya da platformumuz üzerinden ücretsiz teklif talep etmenizi öneririz.</p>

<h2>Sıkça Sorulan Sorular</h2>
<p><strong>Rehabilitasyona başlamak için doktor sevki gerekli mi?</strong><br>SGK kapsamında yararlanmak için genellikle doktor sevki/reçetesi istenir; özel ödemeli hizmetlerde bu şart aranmayabilir, kurumla teyit etmenizi öneririz.</p>
<p><strong>Evde rehabilitasyon hizmeti alınabilir mi?</strong><br>Bazı merkezler evde takip/tedavi hizmeti de sunuyor, bu hizmetin olup olmadığını doğrudan kurumdan sorabilirsiniz.</p>
<p><strong>İlk seans öncesi değerlendirme yapılıyor mu?</strong><br>Çoğu kurum tedaviye başlamadan önce uzman tarafından bir ilk değerlendirme yapar, bu görüşmede program kişiye özel planlanır.</p>

<p>Bursa'daki rehabilitasyon merkezlerini ilçe, uzmanlık alanı ve bütçenize göre karşılaştırmak, doğru bilgi almak için <a href="/kurumlar">kurumlar sayfamızdan</a> arama yapabilir veya ilgilendiğiniz kurumdan doğrudan ücretsiz teklif isteyebilirsiniz.</p>
HTML;

        $page = \App\Models\ContentPage::updateOrCreate(
            ['brand' => 'bakimeviara', 'slug' => 'rehabilitasyon-bursa-fizik-tedavi-rehberi'],
            [
                'type' => 'guide',
                'title' => 'Bursa Rehabilitasyon ve Fizik Tedavi Merkezi Rehberi',
                'summary' => 'Bursa\'da rehabilitasyon merkezi seçerken dikkat edilmesi gerekenler, ilçe ilçe arama ve genel fiyat bilgisi.',
                'body' => $body,
            ]
        );

        return "OK: sayfa kaydedildi/guncellendi -> id={$page->id}, brand=bakimeviara, slug=rehabilitasyon-bursa-fizik-tedavi-rehberi";
    }

    // 3 Agustos 2026: "her rol panelindeki butun fonksiyonlar eksiksiz
    // calisiyor mu" QA istegi icin - gercek admin sifresi/2FA gerekmeden
    // (qa-approve-claim ile ayni desen: session'a admin_id koyup GERCEK
    // controller kodunu dogrudan cagirir) admin panelindeki TUM GET
    // sayfalarini sirayla render eder, Blade/DB hatasi varsa yakalar.
    // Salt-okunur: hicbir POST/PUT/DELETE eylemi tetiklemez. Parametreler
    // ReflectionMethod ile otomatik eslestirilir (Request -> bos Request,
    // Model tipi -> ilgili fixture, aksi halde varsayilan/null).
    private function adminPanelSmokeTest(): string
    {
        $admin = DB::table('admins')->first();
        if (! $admin) {
            return 'HATA: hic admin yok';
        }
        session(['admin_id' => $admin->id, 'admin_name' => $admin->name]);

        $facility = \App\Models\Facility::orderByDesc('id')->first();
        $claim = \App\Models\FacilityClaim::orderByDesc('id')->first();
        $registration = \App\Models\FacilityRegistration::orderByDesc('id')->first();
        $offerRequest = \App\Models\OfferRequest::orderByDesc('id')->first();
        $familyUser = \App\Models\FamilyUser::orderByDesc('id')->first();

        $cases = [
            'Dashboard' => [\App\Http\Controllers\Admin\DashboardController::class, 'index'],
            'Kurumlar (liste)' => [\App\Http\Controllers\Admin\FacilityController::class, 'index'],
            'Kurum davetleri' => [\App\Http\Controllers\Admin\FacilityInvitationController::class, 'index'],
            'Kurum davetleri (hizli gonderim)' => [\App\Http\Controllers\Admin\FacilityInvitationController::class, 'quickSend'],
            'Sahiplenme basvurulari (liste)' => [\App\Http\Controllers\Admin\FacilityClaimController::class, 'index'],
            'Kurum kayit basvurulari (liste)' => [\App\Http\Controllers\Admin\FacilityRegistrationController::class, 'index'],
            'Ayarlar' => [\App\Http\Controllers\Admin\SettingController::class, 'edit'],
            'Bakiye yuklemeleri' => [\App\Http\Controllers\Admin\WalletTopupController::class, 'index'],
            'Veri cekici' => [\App\Http\Controllers\Admin\DataExtractorController::class, 'index'],
            'Teklif talepleri' => [\App\Http\Controllers\Admin\OfferRequestController::class, 'index'],
            'Yorumlar' => [\App\Http\Controllers\Admin\FacilityReviewController::class, 'index'],
            'Ziyaret talepleri' => [\App\Http\Controllers\Admin\VisitRequestController::class, 'index'],
            'Mesajlar (iletisim)' => [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'],
            'Whatsapp tiklamalari' => [\App\Http\Controllers\Admin\WhatsappClickController::class, 'index'],
            'Canli sohbet' => [\App\Http\Controllers\Admin\ChatController::class, 'index'],
            'Canli sohbet istatistik' => [\App\Http\Controllers\Admin\ChatController::class, 'stats'],
            'Canli sohbet ayarlari' => [\App\Http\Controllers\Admin\ChatSettingsController::class, 'edit'],
            'Sehirler' => [\App\Http\Controllers\Admin\CityController::class, 'index'],
            'Kullanicilar (aileler)' => [\App\Http\Controllers\Admin\UserController::class, 'families'],
            'Kullanicilar (kurum yetkilileri)' => [\App\Http\Controllers\Admin\UserController::class, 'facilityUsers'],
            'Kategoriler' => [\App\Http\Controllers\Admin\FacilityCategoryController::class, 'index'],
            'Sayfalar (icerik)' => [\App\Http\Controllers\Admin\ContentPageController::class, 'index'],
            'SSS' => [\App\Http\Controllers\Admin\FaqController::class, 'index'],
            'Paketler' => [\App\Http\Controllers\Admin\SubscriptionPackageController::class, 'index'],
            'Cop kutusu' => [\App\Http\Controllers\Admin\TrashController::class, 'index'],
            'Islem gunlugu' => [\App\Http\Controllers\Admin\AuditLogController::class, 'index'],
            'Hatalar' => [\App\Http\Controllers\Admin\PlatformErrorController::class, 'index'],
            'Aile sorulari' => [\App\Http\Controllers\Admin\FacilityQuestionController::class, 'index'],
            'Site istatistikleri' => [\App\Http\Controllers\Admin\SiteStatsController::class, 'index'],
            'Yakin arama kayitlari' => [\App\Http\Controllers\Admin\NearbySearchController::class, 'index'],
        ];

        if ($facility) {
            $cases['Kurum duzenle (edit)'] = [\App\Http\Controllers\Admin\FacilityController::class, 'edit', [\App\Models\Facility::class => $facility]];
        }
        if ($claim) {
            $cases['Sahiplenme basvurusu (detay)'] = [\App\Http\Controllers\Admin\FacilityClaimController::class, 'show', [\App\Models\FacilityClaim::class => $claim]];
        }
        if ($registration) {
            $cases['Kurum kayit basvurusu (detay)'] = [\App\Http\Controllers\Admin\FacilityRegistrationController::class, 'show', [\App\Models\FacilityRegistration::class => $registration]];
        }
        if ($offerRequest) {
            $cases['Teklif talebi mesajlari'] = [\App\Http\Controllers\Admin\OfferRequestController::class, 'showMessages', [\App\Models\OfferRequest::class => $offerRequest]];
        }
        if ($familyUser) {
            $cases['Aile detay'] = [\App\Http\Controllers\Admin\SiteStatsController::class, 'showFamily', [\App\Models\FamilyUser::class => $familyUser]];
        }

        $out = '';
        $ok = 0;
        $fail = 0;
        foreach ($cases as $name => $spec) {
            [$class, $method] = [$spec[0], $spec[1]];
            $modelArgs = $spec[2] ?? [];
            try {
                $controller = app($class);
                $ref = new \ReflectionMethod($controller, $method);
                $args = [];
                foreach ($ref->getParameters() as $param) {
                    $type = $param->getType();
                    $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                    if ($typeName === Request::class) {
                        $args[] = new Request();
                    } elseif ($typeName && isset($modelArgs[$typeName])) {
                        $args[] = $modelArgs[$typeName];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } elseif ($param->allowsNull()) {
                        $args[] = null;
                    } else {
                        throw new \RuntimeException('desteklenmeyen parametre tipi: ' . ($typeName ?? '?') . ' $' . $param->getName());
                    }
                }
                $response = $controller->{$method}(...$args);
                $content = is_object($response) && method_exists($response, 'getContent') ? $response->getContent() : (string) $response;
                $out .= "OK  | {$name} (" . strlen($content) . " bayt)\n";
                $ok++;
            } catch (\Throwable $e) {
                $out .= "HATA| {$name}: " . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
                $fail++;
            }
        }

        session()->forget(['admin_id', 'admin_name']);

        return "Admin panel smoke test: {$ok} basarili, {$fail} hatali\n\n" . $out;
    }

    // 19 Agustos 2026: kullanicinin talebi - "bursa'daki butun on kayitli
    // kurumlarin yemek listesi alanina" filigranli bir ornek gorsel koy.
    // GUVENLIK: SADECE sahiplenilmemis (is_claimed=false) VE su an hic
    // yemek listesi gorseli OLMAYAN (whereNull) kurumlari hedefler - bir
    // kurum yetkilisi kendi gercek listesini yuklediyse ASLA uzerine
    // yazilmaz.
    //
    // DIKKAT (kullanicinin acik talebi, ayni gunku canli gorsel olayindan
    // hemen sonra): "paylasimli yemek gorseli OLMAMALI, her kurumun ayri
    // olmali". Bu yuzden TEK bir ortak dosya yollari TUM kurumlara
    // ATANMIYOR - her kurum icin KAYNAK gorselin BAGIMSIZ bir kopyasi
    // (kendi rastgele dosya adiyla, gercek bir kurum yuklemesiyle
    // AYIRT EDILEMEZ sekilde) olusturulur ve CrossDomainImageSync ile
    // ANINDA 3 domain'e de yazilir - boylece bir kurum ileride kendi
    // gercek listesini yukleyip eskisini sildiginde SADECE kendi
    // kopyasi silinir, baska hicbir kurumu etkilemez.
    //
    // Paylasimli hostingte tek istekte binlerce kurumu isleme riskine
    // karsi offset/limit ile sayfali calisir (bkz. facilityBorrowDemoImagesBulk
    // ayni desen). dry_run=1 (varsayilan) hicbir sey degistirmez.
    private function menuImageDemoApply(Request $request): string
    {
        $citySlug = (string) $request->query('city_slug', 'bursa');
        $dryRun = $request->query('dry_run', '1') !== '0';
        $limit = (int) $request->query('limit', 40);
        $offset = (int) $request->query('offset', 0);
        $sourcePath = 'facilities/demo/menu-sample-source.webp';

        $city = DB::table('cities')->where('slug', $citySlug)->first();
        if (! $city) {
            return "HATA: '{$citySlug}' slug'li sehir bulunamadi.";
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            return "HATA: kaynak ornek gorsel diskte yok ({$sourcePath}). Once FTP ile yuklenmeli.";
        }

        $totalRemaining = Facility::where('city_id', $city->id)
            ->where('is_claimed', false)
            ->whereNull('menu_image_path')
            ->whereNull('deleted_at')
            ->count();

        $facilities = Facility::where('city_id', $city->id)
            ->where('is_claimed', false)
            ->whereNull('menu_image_path')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get(['id', 'name']);

        $out = "Sehir: {$citySlug} (id={$city->id})\n";
        $out .= "Toplam etkilenecek kurum (sahiplenilmemis + yemek listesi bos): {$totalRemaining}\n";
        $out .= "Bu pencerede islenen: {$facilities->count()} (offset={$offset}, limit={$limit})\n";
        $out .= $dryRun ? "MOD: dry_run (hicbir sey degistirilmedi)\n\n" : "MOD: UYGULANDI - her kurum icin BAGIMSIZ bir kopya olusturuldu\n\n";

        if (! $dryRun) {
            $sourceContents = $disk->get($sourcePath);
            $imageSync = app(\App\Services\CrossDomainImageSync::class);

            foreach ($facilities as $f) {
                $newPath = 'facilities/' . \Illuminate\Support\Str::random(32) . '.webp';
                $disk->put($newPath, $sourceContents);
                $imageSync->syncStore($newPath);

                Facility::whereKey($f->id)->update([
                    'menu_image_path' => $newPath,
                    'menu_image_updated_at' => now(),
                ]);

                $out .= "  #{$f->id} {$f->name} -> {$newPath}\n";
            }
        } else {
            foreach ($facilities as $f) {
                $out .= "  #{$f->id} {$f->name}\n";
            }
        }

        if ($totalRemaining > $offset + $limit) {
            $out .= "\nNOT: bu pencere limite ulasti, kalanlari kapsamak icin offset=" . ($offset + $limit) . " ile tekrar cagirin.";
        }

        return $out;
    }

    // 21 Agustos 2026: 20 Agustos'taki dosya kotasi acil durumunda, bu
    // menuImageDemoApply() ile 331 Bursa kurumu icin olusturulan BAGIMSIZ
    // 195718 baytlik kopyalar, "supheli tekrar" sanilip byte-boyutu
    // eslesmesiyle YANLISLIKLA topluca silinmisti (gercek sorun sessions
    // klasoruymus, bkz. CleanupOldSessionFiles). facilities.menu_image_path
    // veritabaninda hala o (artik var olmayan) yollari gosteriyordu, kirik
    // gorsel olarak ortaya cikti. DB SATIRLARINA DOKUNULMAZ - sadece ayni
    // yolda dosya eksikse kaynak orneginden yeniden olusturulur. Her domain
    // kendi yerel diskini kontrol eder, bu yuzden 3 domainde de ayri ayri
    // cagrilmalidir.
    private function menuImageRepair(Request $request): string
    {
        $dryRun = $request->query('dry_run', '1') !== '0';
        $sourcePath = 'facilities/demo/menu-sample-source.webp';
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return "HATA: kaynak ornek gorsel diskte yok ({$sourcePath}).";
        }

        $facilities = Facility::whereNotNull('menu_image_path')
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'menu_image_path']);

        $missing = $facilities->filter(fn ($f) => $f->menu_image_path
            && ! str_starts_with($f->menu_image_path, 'facilities/demo/')
            && $disk->missing($f->menu_image_path));

        if ($missing->isEmpty()) {
            return "Kontrol edilen {$facilities->count()} kurumdan hicbirinde eksik dosya yok (bu domainde).";
        }

        $out = "Bu domainde eksik dosyasi olan kurum: {$missing->count()}/{$facilities->count()}\n\n";

        if ($dryRun) {
            foreach ($missing as $f) {
                $out .= "  #{$f->id} {$f->name} -> {$f->menu_image_path}\n";
            }
            $out .= "\nGercekten onarmak icin dry_run=0 ile tekrar cagirin.";

            return $out;
        }

        $sourceContents = $disk->get($sourcePath);
        $restored = 0;
        foreach ($missing as $f) {
            $disk->put($f->menu_image_path, $sourceContents);
            $restored++;
            $out .= "  onarildi: #{$f->id} {$f->name} -> {$f->menu_image_path}\n";
        }
        $out .= "\nToplam onarilan: {$restored}";

        return $out;
    }
}

// 28 Temmuz 2026: "Canli API" aktif etme denemesi burada YAPILDI ve
// BASARISIZ OLDU - sonuc kalicilastirmak icin not: /opt/alt/python311
// ile izole bir venv kurulup playwright+openpyxl+requests+beautifulsoup4
// basariyla kuruldu, Chromium tarayicisi da indirildi (root gerekmedi).
// Ancak tarayiciyi GERCEKTEN baslatma testi sunucuda eksik bir paylasimli
// kutuphane yuzunden basarisiz oldu: "libatk-bridge-2.0.so.0: cannot open
// shared object file". Bu kutuphaneyi kurmanin normal yolu
// "playwright install --with-deps" (apt-get gerektirir, root ister) -
// bu paylasimli cPanel hostinginde root/SSH erisimi YOK, bu yuzden bu
// ozellik bu sunucuda kalici olarak calistirilamaz. Deneme sirasinda
// olusturulan venv ve indirilen tarayici dosyalari (storage/app/
// veri-cekici-venv, ~/.cache/ms-playwright) FTP ile temizlendi.
// Bu ozellik olmadan Excel import yolu zaten tam calisiyor durumda.
