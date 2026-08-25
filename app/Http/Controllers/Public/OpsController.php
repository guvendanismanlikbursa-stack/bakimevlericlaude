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
    private const ACTIONS = ['migrate', 'seed', 'storage-link', 'create-admin', 'package-discover', 'cache-refresh', 'log-tail', 'sentry-test', 'queue-status', 'queue-work', 'queue-test', 'diagnostics-image', 'backup-now', 'geo-status', 'geo-missing-list', 'geo-apply', 'legal-page-set', 'geo-fill-city-centroid', 'python-check', 'category-audit', 'category-audit-city', 'invitation-status-audit', 'invitation-status-fix', 'invitation-detail', 'phone-type-audit', 'phone-type-fix', 'ownership-audit', 'ownership-fix', 'miscategory-scan', 'miscategory-fix', 'facility-remove', 'district-audit', 'district-fix', 'ownership-verify', 'facility-remove-by-ownership', 'ownership-fix-bulk', 'mail-render-test', 'qa-pick-facilities', 'qa-check', 'qa-setup', 'qa-setup-unclaimed', 'qa-password-reset-link', 'qa-registration-edit-link', 'qa-push-fix-subscription', 'qa-facility-set-known-password', 'qa-admin-push-diagnostic', 'qa-admin-push-test', 'fix-push-encoding', 'qa-staging-htpasswd-add', 'qa-staging-htpasswd-remove', 'qa-teardown', 'qa-verify-family-email', 'qa-debug-quote', 'qa-approve-claim', 'qa-cleanup-claim', 'qa-reject-claim', 'qa-reset-invitation-status', 'qa-approve-topup', 'qa-reject-topup', 'facility-user-unclaimed-audit', 'facility-user-unclaimed-fix', 'facility-set-city', 'php-upload-limits', 'queue-failed-detail', 'registration-revert-to-pending', 'registration-detail', 'document-diagnostic', 'admin-panel-smoke-test', 'qa-approve-registration', 'queue-flush-failed', 'gallery-health-scan', 'gallery-prune-broken', 'demo-images-cleanup', 'gallery-check-health', 'check-user-flows', 'cleanup-stale-qa-debris', 'test-platform-error', 'cleanup-test-platform-errors', 'name-cleanup-audit', 'name-cleanup-fix', 'facility-lookup', 'facility-borrow-demo-images', 'facility-borrow-demo-images-bulk', 'invite-review-families', 'snapshot-facility-stats', 'menu-image-demo-apply', 'restore-accidentally-deleted-claimed-facility-demo-images', 'sessions-gc', 'menu-image-repair', 'bursa-visit-export', 'mysql-tmp-diagnostics'];

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
            'legal-page-set' => $this->legalPageSet($request),
            'geo-fill-city-centroid' => $this->geoFillCityCentroid(),
            'python-check' => $this->pythonCheck(),
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
            'cleanup-stale-qa-debris' => $this->cleanupStaleQaDebris($request),
            'invite-review-families' => $this->inviteReviewFamilies(),
            'snapshot-facility-stats' => $this->snapshotFacilityStats(),
            'test-platform-error' => $this->testPlatformError(),
            'cleanup-test-platform-errors' => $this->cleanupTestPlatformErrors(),
            'menu-image-demo-apply' => $this->menuImageDemoApply($request),
            'restore-accidentally-deleted-claimed-facility-demo-images' => $this->restoreAccidentallyDeletedClaimedFacilityDemoImages(),
            'sessions-gc' => $this->sessionsGc($request),
            'menu-image-repair' => $this->menuImageRepair($request),
            'bursa-visit-export' => $this->bursaVisitExport($request),
            'mysql-tmp-diagnostics' => $this->mysqlTmpDiagnostics(),
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
            ->select('facilities.id', 'facilities.name', 'facilities.is_claimed', 'facility_categories.name as category_name', 'facility_categories.id as category_id')
            ->limit(20)
            ->get();

        if ($facilities->isEmpty()) {
            return "'{$q}' icin kurum bulunamadi.";
        }

        $out = '';
        foreach ($facilities as $f) {
            $out .= "#{$f->id} {$f->name} | kategori: {$f->category_name} (#{$f->category_id}) | sahiplenme: " . ($f->is_claimed ? 'SAHIPLENILMIS' : 'on kayitli/sahiplenilmemis') . "\n";
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
