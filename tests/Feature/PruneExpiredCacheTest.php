<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

// 23 Ekim 2026: süresi dolmuş önbellek dosyaları üç sitede de silinir; geçerli
// ve süresiz önbellek, site olmayan klasörler ve önbellek dışı dosyalar kalır.
class PruneExpiredCacheTest extends TestCase
{
    public function test_only_expired_cache_files_are_deleted_on_all_sites(): void
    {
        $kok = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pec_'.uniqid();
        $veri = fn ($site) => $kok.'/'.$site.'/storage/framework/cache/data/ab/cd';
        foreach (['site1', 'site2'] as $site) {
            File::makeDirectory($veri($site), 0755, true);
            file_put_contents($kok.'/'.$site.'/artisan', '');
            file_put_contents($veri($site).'/eski', (time() - 60).'s:4:"eski";');
            file_put_contents($veri($site).'/gecerli', (time() + 3600).'s:7:"gecerli";');
            file_put_contents($veri($site).'/suresiz', '0000000000s:7:"suresiz";');
        }
        // "artisan" olmayan klasöre dokunulmaz.
        File::makeDirectory($veri('site-degil'), 0755, true);
        file_put_contents($veri('site-degil').'/eski', (time() - 60).'x');
        // Önbellek dışındaki dosyaya dokunulmaz.
        File::makeDirectory($kok.'/site1/storage/app/public/facilities', 0755, true);
        file_put_contents($kok.'/site1/storage/app/public/facilities/foto.webp', (time() - 60).'x');

        $this->artisan('platform:prune-expired-cache', ['--kok' => $kok, '--dry-run' => true])->assertSuccessful();
        $this->assertFileExists($veri('site1').'/eski');

        $this->artisan('platform:prune-expired-cache', ['--kok' => $kok])->assertSuccessful();
        foreach (['site1', 'site2'] as $site) {
            $this->assertFileDoesNotExist($veri($site).'/eski');
            $this->assertFileExists($veri($site).'/gecerli');
            $this->assertFileExists($veri($site).'/suresiz');
        }
        $this->assertFileExists($veri('site-degil').'/eski');
        $this->assertFileExists($kok.'/site1/storage/app/public/facilities/foto.webp');
        File::deleteDirectory($kok);
    }
}
