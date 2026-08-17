<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Deploy script'inin ve dis izleme (UptimeRobot vb.) araclarinin canliyi
// dogrulamak icin kullandigi uc. Hassas veri icermez, herkese acik olmasi
// guvenlidir. Bugun yasadigimiz 3 kesintinin ucunu de (autoload, paket
// kaydi, Socialite config'i) bu uc devreye girseydi saniyeler icinde
// yakalardik.
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'FAIL: '.$e->getMessage();
            $healthy = false;
        }

        try {
            Cache::put('_health_check', '1', 5);
            $checks['cache'] = Cache::get('_health_check') === '1' ? 'ok' : 'FAIL: okunamadi';
            if ($checks['cache'] !== 'ok') {
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['cache'] = 'FAIL: '.$e->getMessage();
            $healthy = false;
        }

        try {
            $path = 'health-check-'.uniqid().'.txt';
            Storage::disk('local')->put($path, 'ok');
            $checks['storage'] = Storage::disk('local')->exists($path) ? 'ok' : 'FAIL: yazilamadi';
            Storage::disk('local')->delete($path);
            if ($checks['storage'] !== 'ok') {
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['storage'] = 'FAIL: '.$e->getMessage();
            $healthy = false;
        }

        $checks['socialite'] = class_exists(\Laravel\Socialite\Facades\Socialite::class) ? 'ok' : 'FAIL: sinif bulunamadi';
        if ($checks['socialite'] !== 'ok') {
            $healthy = false;
        }

        try {
            $pendingMigrations = DB::table('migrations')->count() < count(glob(database_path('migrations/*.php')));
            $checks['migrations'] = $pendingMigrations ? 'FAIL: bekleyen migration var' : 'ok';
            if ($pendingMigrations) {
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['migrations'] = 'FAIL: '.$e->getMessage();
            $healthy = false;
        }

        // 17 Agustos 2026: kullanicinin bildirdigi gercek olay - gece yedegi
        // (backup:database, her gun 03:30'da zamanlanmis) bu docroot'ta
        // sessizce calismiyordu, hicbir yerde gorunmuyordu ta ki kullanici
        // fark edip sorana kadar. cron/schedule:run gunluk gorevlerin
        // calisip calismadigini disaridan izleyen ayri bir mekanizma yoktu.
        // Artik en son yedek dosyasi 36 saatten eskiyse (gunluk dongude
        // makul bir tolerans payi) bu uc FAIL doner - dis izleme (UptimeRobot
        // vb.) bunu yakalar.
        // Test/CI ortaminda (GitHub Actions --no-dev kurulumu dahil) hic
        // yedek dosyasi olmayabilir - bu, production'da izlenen gercek bir
        // gorev-calisti-mi sinyali, yerel/CI ortamin gecerliligiyle ilgisi
        // yok, bu yuzden testing ortaminda atlanir.
        if (app()->environment('testing')) {
            $checks['backup'] = 'atlandi (test ortami)';
        } else {
            try {
                $backupDir = storage_path('app/private/backups');
                $latest = null;
                if (\Illuminate\Support\Facades\File::isDirectory($backupDir)) {
                    foreach (\Illuminate\Support\Facades\File::files($backupDir) as $file) {
                        if (str_starts_with($file->getFilename(), 'backup-') && $file->getExtension() === 'gz') {
                            $latest = max($latest ?? 0, $file->getMTime());
                        }
                    }
                }
                if ($latest === null) {
                    $checks['backup'] = 'FAIL: hic yedek dosyasi bulunamadi';
                    $healthy = false;
                } else {
                    $ageHours = (now()->timestamp - $latest) / 3600;
                    $checks['backup'] = $ageHours > 36
                        ? 'FAIL: en son yedek '.round($ageHours).' saat once alinmis (gunluk gorev calismiyor olabilir)'
                        : 'ok';
                    if ($ageHours > 36) {
                        $healthy = false;
                    }
                }
            } catch (\Throwable $e) {
                $checks['backup'] = 'FAIL: '.$e->getMessage();
                $healthy = false;
            }
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'fail',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
