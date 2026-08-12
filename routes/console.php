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

// Bakim: paylasimli (cPanel) hosting'de kalici bir "queue:work" daemon'i
// (supervisor/systemd) kurulamadigindan, kuyruk mevcut "* * * * * schedule:run"
// cron'una binerek her dakika en fazla ~50 saniye boyunca tuketilir. Kuyruk
// bosaldiginda hemen cikar (--stop-when-empty), bu yuzden pratikte gecikme
// saniyeler mertebesinde kalir.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
