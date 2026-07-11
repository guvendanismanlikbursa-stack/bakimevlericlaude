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

// Bakim: paylasimli (cPanel) hosting'de kalici bir "queue:work" daemon'i
// (supervisor/systemd) kurulamadigindan, kuyruk mevcut "* * * * * schedule:run"
// cron'una binerek her dakika en fazla ~50 saniye boyunca tuketilir. Kuyruk
// bosaldiginda hemen cikar (--stop-when-empty), bu yuzden pratikte gecikme
// saniyeler mertebesinde kalir.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
