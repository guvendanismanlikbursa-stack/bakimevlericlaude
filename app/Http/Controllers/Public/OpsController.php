<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Deploy script'inin migrate + cache yenileme gibi birkac SABIT komutu
// uzaktan tetikleyebilmesi icin - CronRunnerController'daki paylasilan-
// sifre deseninin birebir ayni. SADECE asagidaki sabit listedeki eylemler
// calisir (keyfi shell komutu YOK). Bugune kadar her deploy'da public/'e
// kimliksiz, gecici bir PHP script atip is bitince silme deseninin
// yerini alir - o script orada durdugu saniyeler bile yetkisiz erisime
// acik bir pencereydi, bu uc kalici ve token korumali.
class OpsController extends Controller
{
    private const ACTIONS = ['migrate', 'package-discover', 'cache-refresh', 'log-tail', 'sentry-test', 'queue-status', 'queue-work', 'queue-test'];

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
            'package-discover' => $this->packageDiscover(),
            'cache-refresh' => $this->cacheRefresh(),
            'log-tail' => $this->logTail((int) $request->query('bytes', 8000)),
            'sentry-test' => $this->sentryTest(),
            'queue-status' => $this->queueStatus(),
            'queue-work' => $this->queueWork(),
            'queue-test' => $this->queueTest(),
        };

        return response($output, 200)->header('Content-Type', 'text/plain');
    }

    private function migrate(): string
    {
        Artisan::call('migrate', ['--force' => true]);

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

        foreach (['route:clear', 'route:cache', 'config:clear', 'config:cache', 'view:clear', 'view:cache', 'cache:clear'] as $command) {
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

        return "queue.default=" . config('queue.default') . "\nbekleyen=$pending\nbasarisiz=$failed\n";
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
}
