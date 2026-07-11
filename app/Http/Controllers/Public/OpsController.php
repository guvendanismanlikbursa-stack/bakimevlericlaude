<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
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
    private const ACTIONS = ['migrate', 'package-discover', 'cache-refresh', 'log-tail'];

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
        // Sunucunun kendi (--no-dev, temiz) vendor/composer/installed.json'undan
        // bootstrap/cache/packages.php + services.php dosyalarini yeniden uretir.
        // Bugunku hatanin kok nedeni (bu iki dosyanin git'ten, yereldeki dev-dahil
        // haliyle production'a tasinmasi) bu sayede bir daha yasanmaz - dosyalar
        // artik hic FTP ile tasinmiyor, her zaman sunucuda dogru sekilde uretiliyor.
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
}
