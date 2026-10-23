<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

// 23 Ekim 2026: kullanıcının talebi - "bu akşamdan itibaren yeni eklenen
// bütün her şeyi yedeğe ekleyerek devam et". Tam dosya yedeği (3,6+ GB)
// 23 Ekim 2026'da bilgisayara indirildi; sunucuda artık her gece yalnızca
// son yedekten sonra eklenen/değişen dosyalar (kurum görselleri, videolar,
// menüler, sahiplenme belgeleri) + .env küçük bir zip'e alınır.
//
// Neden parça parça: zamanlanmış işler cPanel cron'unun çağırdığı bir web
// isteği içinde çalışıyor (bkz. CronRunnerController); web sunucusu uzun
// süren isteği log bırakmadan kesiyordu - pazar gecesi 3,6 GB'lık tek zip
// bu yüzden hiç oluşmuyordu. Günlük değişen dosyalar küçük olduğu için
// birkaç saniyede biter. Görseller zaten sıkıştırılmış (WebP/JPEG/MP4)
// olduğundan zip'te yeniden sıkıştırılmaz (CM_STORE), işlem çok hızlıdır.
class BackupFilesDaily extends Command
{
    protected $signature = 'backup:files-daily {--keep-days=90 : Bu günden eski günlük dosya yedekleri silinir}';

    protected $description = 'Son yedekten sonra eklenen/değişen dosyaları storage/app/private/backups altına zip olarak yedekler';

    public function handle(): int
    {
        set_time_limit(0);

        if (! is_primary_site()) {
            $this->info('Ana site değil, dosya yedeği ana sitede alınıyor - atlandı.');

            return self::SUCCESS;
        }
        if (! class_exists(\ZipArchive::class)) {
            record_platform_error('backup-files-daily', 'Günlük dosya yedeği alınamadı', 'ZipArchive eklentisi yok.');

            return self::FAILURE;
        }

        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir, 0755);
        $statePath = $dir.DIRECTORY_SEPARATOR.'files-backup-state.json';
        $state = File::exists($statePath) ? (json_decode((string) File::get($statePath), true) ?: []) : [];
        // İlk çalışmada başlangıç: tam yedeğin bilgisayara indirildiği günün başı (23 Ekim 2026).
        $since = (int) ($state['son_yedek'] ?? \Illuminate\Support\Carbon::parse('2026-10-23 00:00:00', config('app.timezone'))->getTimestamp());
        $basla = now()->getTimestamp();

        $zipPath = $dir.DIRECTORY_SEPARATOR.'files-backup-gunluk-'.now()->format('Y-m-d_His').'.zip';
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            record_platform_error('backup-files-daily', 'Günlük dosya yedeği alınamadı', 'Zip dosyası açılamadı: '.basename($zipPath));

            return self::FAILURE;
        }

        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $zip->addFile($envPath, '.env');
        }

        $sources = [
            'uploads/facilities' => storage_path('app/public/facilities'),
            'uploads/claims' => storage_path('app/private/claims'),
        ];
        $adet = 0;
        $boyut = 0;
        foreach ($sources as $prefix => $sourceDir) {
            if (! File::isDirectory($sourceDir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getMTime() < $since) {
                    continue;
                }
                $name = $prefix.'/'.str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1));
                $zip->addFile($file->getPathname(), $name);
                $zip->setCompressionName($name, \ZipArchive::CM_STORE);
                $adet++;
                $boyut += $file->getSize();
            }
        }
        $zip->close();

        if (! File::exists($zipPath)) {
            record_platform_error('backup-files-daily', 'Günlük dosya yedeği oluşmadı', basename($zipPath).' diske yazılamadı.');

            return self::FAILURE;
        }

        // Bir sonraki gece bu çalışmanın başladığı andan sonrası alınır.
        File::put($statePath, json_encode(['son_yedek' => $basla, 'son_dosya' => basename($zipPath), 'dosya_sayisi' => $adet], JSON_UNESCAPED_UNICODE));
        $this->info('Günlük dosya yedeği: '.basename($zipPath)." ({$adet} dosya, ".number_format($boyut / 1024 / 1024, 1).' MB)');

        $cutoff = now()->subDays((int) $this->option('keep-days'))->getTimestamp();
        foreach (File::files($dir) as $f) {
            if (str_starts_with($f->getFilename(), 'files-backup-gunluk-') && $f->getMTime() < $cutoff) {
                File::delete($f->getPathname());
            }
        }

        return self::SUCCESS;
    }
}
