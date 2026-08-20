<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// Bakim: 20 Agustos 2026'da storage/framework/sessions'ta Laravel'in
// lottery-tabanli GC'sinin (config/session.php 'lottery') beklendigi gibi
// calismadigi, dosyalarin haftalarca hic silinmedigi ve hesabin dosya sayisi
// (inode) sinirini doldurdugu tespit edildi. Bu komut, lottery'den bagimsiz,
// duzenli calisan bir guvenlik agi olarak eklendi.
class CleanupOldSessionFiles extends Command
{
    protected $signature = 'platform:cleanup-old-sessions {--minutes=180 : Bu dakikadan eski oturum dosyalari silinir}';

    protected $description = 'storage/framework/sessions altindaki eski oturum dosyalarini siler';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dir = storage_path('framework/sessions');

        if (! is_dir($dir)) {
            $this->info('Klasor bulunamadi, atlandi.');

            return self::SUCCESS;
        }

        $cutoff = time() - ($minutes * 60);
        $deleted = 0;
        $scanned = 0;

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
            if ($mtime !== false && $mtime < $cutoff && @unlink($path)) {
                $deleted++;
            }
        }
        closedir($handle);

        $this->info("{$deleted}/{$scanned} eski oturum dosyasi silindi (>{$minutes} dk).");

        return self::SUCCESS;
    }
}
