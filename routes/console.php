<?php

use App\Services\ScheduledJobMonitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 17 Agustos 2026: kullanicinin talebi - bu oturumda AYNI KOKTEN 3 ayri
// sessiz aksama yasandi (yedekleme gunlerce calismadi, kuyruk kilidi 24
// saat takildi, gunluk istatistik gorevi 5 gun calismadi) - hicbiri hata
// vermedi, sadece "olmadi". Artik asagidaki HER Schedule::command() kendi
// ScheduledJobMonitor::attach() cagrisiyla sarmalanir - /admin/zamanlanan-
// gorevler ekrani ve /_saglik ucu "son basarili calisma ne zamandi, beklenen
// sikliktan cok mu gecikmis" sorusunu artik GORE BILIR, tesadufen degil.

// Bakim: islem gunlugunu (admin_events) 180 gunden eski kayitlardan
// aylik olarak temizler. docs/PRODUCTION.md'deki cron (schedule:run) calistigi surece
// ek bir islem gerekmez.
ScheduledJobMonitor::attach(
    Schedule::command('admin-events:prune')->monthly(),
    'admin-events:prune',
    60 * 24 * 31
);

// Bakim: veritabaninin tamaminin gzip'li SQL yedegini gunluk olarak alir,
// 14 gunden eski yedekleri siler (bkz. App\Console\Commands\BackupDatabase).
ScheduledJobMonitor::attach(
    Schedule::command('backup:database')->dailyAt('03:30'),
    'backup:database',
    60 * 24
);

// Bakim: 48 saattir cevaplanmamis aile sorulari icin kuruma tek seferlik
// hatirlatma gonderir (bkz. App\Console\Commands\RemindUnansweredQuestions).
ScheduledJobMonitor::attach(
    Schedule::command('questions:remind-unanswered')->dailyAt('10:00'),
    'questions:remind-unanswered',
    60 * 24
);

// Bakim: son 48 saatte yuklenen kurum galeri gorsellerinin sunucu diskinde
// gercekten var olup olmadigini kontrol eder, "sessizce kaybolan" gorsel
// olayini erken yakalar (bkz. App\Console\Commands\CheckGalleryHealth).
ScheduledJobMonitor::attach(
    Schedule::command('gallery:check-health')->dailyAt('09:00'),
    'gallery:check-health',
    60 * 24
);

// 12 Agustos 2026: kullanicinin talebi - "her sabah kontrol" artik sadece
// goruntulerle sinirli degil; 3 markanin da uzerinde aile/kurum kaydi,
// sahiplenme, teklif talebi gibi en kritik kullanici gorevlerini gercek
// HTTP istekleriyle uctan uca dener (bkz. App\Console\Commands\CheckUserFlows).
// Gorsel kontrolunden once calisir ki ikisi de ayni sabah penceresinde
// tamamlansin.
ScheduledJobMonitor::attach(
    Schedule::command('platform:check-user-flows')->dailyAt('08:45'),
    'platform:check-user-flows',
    60 * 24
);

// 26 Agustos 2026: kullanicinin talebi - "her bolumde ki her ozellik
// mutlaka farkli senaryolarla test edilmeli". CheckUserFlows sadece genel
// site akislarini dener, admin paneline hic dokunmaz - bu haftaki gercek
// hatalarin (yerinde sahiplendirme, sahiplenmeyi geri alma) TAMAMI tam
// olarak bu kor noktadaydi (bkz. App\Console\Commands\CheckAdminFlows).
// 08:45'teki kontrolden 5 dakika sonra calisir ki GET_LOCK pencereleri
// cakismasin.
ScheduledJobMonitor::attach(
    Schedule::command('platform:check-admin-flows')->dailyAt('08:50'),
    'platform:check-admin-flows',
    60 * 24
);

// 15 Agustos 2026: kullanicinin talebi - "testler hata bulunca otomatik
// duzeltebilecek script" icin bilerek DAR kapsamli bir cozum: sadece
// CheckUserFlows'un basarisiz akislarda inceleme icin biraktigi eski
// test-veri kalintilarini (qatest.daily.*@example.com, 2 gunden eski)
// temizler - gercek kod hatalarina dokunmaz (bkz. App\Console\Commands\
// CleanupStaleQaDebris). Gunluk kontrolden sonra, haftada bir yeterli.
ScheduledJobMonitor::attach(
    Schedule::command('platform:cleanup-stale-qa-debris')->weeklyOn(1, '08:00'),
    'platform:cleanup-stale-qa-debris',
    60 * 24 * 7
);

// 12 Agustos 2026: kullanicinin talebi - kurum performans panelindeki
// "gecen aya gore" trend gorunumu icin her gece kurumlarin o gunku
// goruntulenme/favori/talep/teklif sayilarini kaydeder (bkz.
// App\Console\Commands\SnapshotFacilityDailyStats).
ScheduledJobMonitor::attach(
    Schedule::command('facility:snapshot-daily-stats')->dailyAt('23:55'),
    'facility:snapshot-daily-stats',
    60 * 24
);

// 6 Eylul 2026: kullanicinin talebi - admin dashboard'daki "Kurum Turune
// Gore Ilgi" kartinin TUM ZAMANLAR toplami yerine "son 30 gunde GERCEKTEN
// kazanilan goruntulenme"yi gosterebilmesi icin (bkz. App\Console\Commands\
// SnapshotCategoryViews ayni tarihli yorum) her gece bolum bazinda toplam
// goruntulenmeyi kaydeder.
ScheduledJobMonitor::attach(
    Schedule::command('category:snapshot-views')->dailyAt('23:50'),
    'category:snapshot-views',
    60 * 24
);

// 14 Agustos 2026: kullanicinin talebi - "hizli yanit veren kurum" rozeti
// icin her gece son 90 gundeki ortalama teklif yanit suresini hesaplar
// (bkz. App\Console\Commands\CalculateFacilityResponseTime,
// Facility::hasFastResponseBadge()).
ScheduledJobMonitor::attach(
    Schedule::command('facility:calculate-response-time')->dailyAt('23:50'),
    'facility:calculate-response-time',
    60 * 24
);

// 14 Agustos 2026: kullanicinin talebi - "kayitli arama" ozelligi. Kriterlere
// uyan yeni kurum eklendiginde aileye bildirim gonderir (bkz.
// App\Console\Commands\NotifyFamilySavedSearches, Family\SavedSearchController).
ScheduledJobMonitor::attach(
    Schedule::command('family:notify-saved-searches')->dailyAt('09:15'),
    'family:notify-saved-searches',
    60 * 24
);

// 12 Agustos 2026: kullanicinin talebi - "yorum yazmaya davet edilmiyorum".
// Teklifi kabul edip bir sure gecen ama hic yorum yazmamis aileleri
// otomatik davet eder (bkz. App\Console\Commands\InviteFamiliesToReview).
ScheduledJobMonitor::attach(
    Schedule::command('reviews:invite-families')->dailyAt('10:30'),
    'reviews:invite-families',
    60 * 24
);

// 13 Agustos 2026: kullanicinin talebi - belge yuklemesi basvuru aninda
// zorunlu olmaktan cikarildi, suistimali onlemek icin 24 saat icinde
// belge eklenmezse basvuru otomatik silinir (bkz. App\Console\Commands\
// ExpireUndocumentedClaims).
ScheduledJobMonitor::attach(
    Schedule::command('claims:expire-undocumented')->hourly(),
    'claims:expire-undocumented',
    60
);

// 20 Agustos 2026: storage/framework/sessions'ta Laravel'in lottery-tabanli
// otomatik GC'si calismamis, dosyalar haftalarca birikip hesabin 500.000
// dosya (inode) sinirini doldurmustu (bkz. App\Console\Commands\
// CleanupOldSessionFiles). Lottery'ye guvenmek yerine burada garanti bir
// zamanlanmis calisma eklendi.
ScheduledJobMonitor::attach(
    Schedule::command('platform:cleanup-old-sessions')->hourly(),
    'platform:cleanup-old-sessions',
    60
);

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
//
// 17 Agustos 2026: ScheduledJobMonitor'a baglandi. ILK deploy'da beklenen
// siklik 2 dakika verilmisti ("her dakika calismasi gerekiyor") ama canlida
// hemen /_saglik'i FAIL'e dusurdu: paylasimli cPanel hosting'in gercek cron
// tetikleme sikligi Laravel'in ->everyMinute() tanimindan BAGIMSIZ - host
// "* * * * *" calistirsa bile PHP process baslatma/kuyruk suresi ve olasi
// host-tarafi throttling yuzunden pratikte dakikalar arasi bosluk normal.
// 2 dakikalik esik gercek dunya jitter'ini tolere edemedi. Bu sistemin asil
// amaci (15 Agustos olayi - kilit 24 SAAT takildi) dakika hassasiyeti degil,
// saatler/gunler suren sessiz durmayi yakalamak, bu yuzden esik 15 dakikaya
// (1.5x ile ~22 dakika tolerans) cikarildi.
ScheduledJobMonitor::attach(
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
        ->everyMinute()
        ->withoutOverlapping(5),
    'queue:work',
    15
);
