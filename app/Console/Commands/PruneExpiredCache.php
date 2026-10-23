<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// 23 Ekim 2026: kullanıcının talebi - hosting dosya (inode) sayısı arama
// botlarının taradığı sayfaların önbellek dosyalarıyla sürekli artıyordu.
// Laravel'in dosya önbelleği süresi dolan dosyayı yalnızca yeniden
// okunduğunda siler; okunmayan sayfanın dosyası sonsuza kadar kalır. Bu
// görev üç sitenin (aynı ev dizinindeki, içinde "artisan" olan klasörler)
// storage/framework/cache/data klasöründe YALNIZCA süresi dolmuş dosyaları
// siler. Süresi dolmamış (geçerli) önbelleğe dokunmaz. Dosyanın ilk 10
// karakteri Laravel'in yazdığı bitiş zamanıdır (0 = süresiz, silinmez).
class PruneExpiredCache extends Command
{
    protected $signature = 'platform:prune-expired-cache {--kok= : Sitelerin bulunduğu ev dizini (test için)} {--dry-run : Yalnızca say}';

    protected $description = 'Üç sitenin dosya önbelleğinden süresi dolmuş dosyaları siler';

    public function handle(): int
    {
        $kok = rtrim((string) ($this->option('kok') ?: dirname(base_path())), '/\\');
        $simdi = time();
        $toplamSilinen = 0;
        foreach (array_diff(scandir($kok) ?: [], ['.', '..']) as $ad) {
            $site = $kok.DIRECTORY_SEPARATOR.$ad;
            $veri = $site.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'data';
            if (is_link($site) || ! is_file($site.DIRECTORY_SEPARATOR.'artisan') || ! is_dir($veri)) {
                continue;
            }
            $silinen = 0;
            $kalan = 0;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($veri, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $oge) {
                if ($oge->isLink()) {
                    continue;
                }
                if ($oge->isDir()) {
                    if (! $this->option('dry-run')) {
                        @rmdir($oge->getPathname()); // yalnızca boşsa silinir
                    }

                    continue;
                }
                $bas = @file_get_contents($oge->getPathname(), false, null, 0, 10);
                $bitis = ($bas !== false && ctype_digit($bas)) ? (int) $bas : null;
                if ($bitis !== null && $bitis !== 0 && $bitis < $simdi) {
                    if ($this->option('dry-run') || @unlink($oge->getPathname())) {
                        $silinen++;
                    }
                } else {
                    $kalan++;
                }
            }
            $toplamSilinen += $silinen;
            $this->line("{$ad}: süresi dolan {$silinen} dosya ".($this->option('dry-run') ? 'silinecek' : 'silindi').", geçerli {$kalan} dosya kaldı");
        }
        $this->info("Toplam: {$toplamSilinen}");

        return self::SUCCESS;
    }
}
