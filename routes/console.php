<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bakim: islem gunlugunu (admin_events) 180 gunden eski kayitlardan
// aylik olarak temizler. docs/PRODUCTION.md'deki cron (schedule:run) calistigi surece
// ek bir islem gerekmez.
Schedule::command('admin-events:prune')->monthly();

// Bakim: veritabaninin tamaminin gzip'li SQL yedegini gunluk olarak alir,
// 14 gunden eski yedekleri siler (bkz. App\Console\Commands\BackupDatabase).
Schedule::command('backup:database')->dailyAt('03:30');

// Bakim: 48 saattir cevaplanmamis aile sorulari icin kuruma tek seferlik
// hatirlatma gonderir (bkz. App\Console\Commands\RemindUnansweredQuestions).
Schedule::command('questions:remind-unanswered')->dailyAt('10:00');

// Bakim: son 48 saatte yuklenen kurum galeri gorsellerinin sunucu diskinde
// gercekten var olup olmadigini kontrol eder, "sessizce kaybolan" gorsel
// olayini erken yakalar (bkz. App\Console\Commands\CheckGalleryHealth).
Schedule::command('gallery:check-health')->dailyAt('09:00');

// 12 Agustos 2026: kullanicinin talebi - "her sabah kontrol" artik sadece
// goruntulerle sinirli degil; 3 markanin da uzerinde aile/kurum kaydi,
// sahiplenme, teklif talebi gibi en kritik kullanici gorevlerini gercek
// HTTP istekleriyle uctan uca dener (bkz. App\Console\Commands\CheckUserFlows).
// Gorsel kontrolunden once calisir ki ikisi de ayni sabah penceresinde
// tamamlansin.
Schedule::command('platform:check-user-flows')->dailyAt('08:45');

// 15 Agustos 2026: kullanicinin talebi - "testler hata bulunca otomatik
// duzeltebilecek script" icin bilerek DAR kapsamli bir cozum: sadece
// CheckUserFlows'un basarisiz akislarda inceleme icin biraktigi eski
// test-veri kalintilarini (qatest.daily.*@example.com, 2 gunden eski)
// temizler - gercek kod hatalarina dokunmaz (bkz. App\Console\Commands\
// CleanupStaleQaDebris). Gunluk kontrolden sonra, haftada bir yeterli.
Schedule::command('platform:cleanup-stale-qa-debris')->weeklyOn(1, '08:00');

// 12 Agustos 2026: kullanicinin talebi - kurum performans panelindeki
// "gecen aya gore" trend gorunumu icin her gece kurumlarin o gunku
// goruntulenme/favori/talep/teklif sayilarini kaydeder (bkz.
// App\Console\Commands\SnapshotFacilityDailyStats).
Schedule::command('facility:snapshot-daily-stats')->dailyAt('23:55');

// 14 Agustos 2026: kullanicinin talebi - "hizli yanit veren kurum" rozeti
// icin her gece son 90 gundeki ortalama teklif yanit suresini hesaplar
// (bkz. App\Console\Commands\CalculateFacilityResponseTime,
// Facility::hasFastResponseBadge()).
Schedule::command('facility:calculate-response-time')->dailyAt('23:50');

// 14 Agustos 2026: kullanicinin talebi - "kayitli arama" ozelligi. Kriterlere
// uyan yeni kurum eklendiginde aileye bildirim gonderir (bkz.
// App\Console\Commands\NotifyFamilySavedSearches, Family\SavedSearchController).
Schedule::command('family:notify-saved-searches')->dailyAt('09:15');

// 12 Agustos 2026: kullanicinin talebi - "yorum yazmaya davet edilmiyorum".
// Teklifi kabul edip bir sure gecen ama hic yorum yazmamis aileleri
// otomatik davet eder (bkz. App\Console\Commands\InviteFamiliesToReview).
Schedule::command('reviews:invite-families')->dailyAt('10:30');

// 13 Agustos 2026: kullanicinin talebi - belge yuklemesi basvuru aninda
// zorunlu olmaktan cikarildi, suistimali onlemek icin 24 saat icinde
// belge eklenmezse basvuru otomatik silinir (bkz. App\Console\Commands\
// ExpireUndocumentedClaims).
Schedule::command('claims:expire-undocumented')->hourly();

// Bakim: paylasimli (cPanel) hosting'de kalici bir "queue:work" daemon'i
// (supervisor/systemd) kurulamadigindan, kuyruk mevcut "* * * * * schedule:run"
// cron'una binerek her dakika en fazla ~50 saniye boyunca tuketilir. Kuyruk
// bosaldiginda hemen cikar (--stop-when-empty), bu yuzden pratikte gecikme
// saniyeler mertebesinde kalir.
//
// 15 Agustos 2026: kullanicinin bildirdigi gercek olay - 13.08'de uygulama
// icinden gonderilen bir mesajin bildirim maili 2 gun boyunca gitmedi,
// ancak baska bir islem cache:clear cagirinca (bkz. OpsController::
// cacheRefresh) aninda gitti. Kok neden: withoutOverlapping() parametresiz
// cagrilinca varsayilan kilit suresi 1440 DAKIKA (24 saat) - eger bir
// queue:work calismasi host tarafindan yarida kesilirse (bellek/sure
// limiti, ani surec sonlandirma) kilit dosyasini TEMIZ birakamiyor ve
// sonraki TUM calismalar sessizce atlaniyor, ta ki 24 saat dolana veya
// (bu olayda oldugu gibi) cache tesadufen baska bir sebeple temizlenene
// kadar. Gercek is suresi max-time=50 saniye oldugu icin 5 dakikadan eski
// bir kilit kesinlikle olu demektir - kendi kendini cok daha hizli
// iyilestirsin diye kilit suresi 5 dakikaya dusuruldu.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);
