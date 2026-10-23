<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

// 23 Ekim 2026: kullanıcının talebi - her akşam yalnızca son yedekten sonra
// eklenen/değişen dosyalar (+ .env) yedeklenir; eski dosyalar tekrar alınmaz.
class BackupFilesDailyTest extends TestCase
{
    public function test_daily_files_backup_contains_only_new_files_and_advances_state(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bfd_'.uniqid();
        File::makeDirectory($root.'/app/public/facilities/1', 0755, true);
        File::makeDirectory($root.'/app/private/claims', 0755, true);
        $this->app->useStoragePath($root);

        file_put_contents($root.'/app/public/facilities/1/eski.webp', 'eski');
        touch($root.'/app/public/facilities/1/eski.webp', now()->subDays(3)->timestamp);
        file_put_contents($root.'/app/public/facilities/1/yeni.webp', 'yeni');
        file_put_contents($root.'/app/private/claims/belge.pdf', 'belge');
        File::makeDirectory($root.'/app/private/backups', 0755, true);
        file_put_contents($root.'/app/private/backups/files-backup-state.json', json_encode(['son_yedek' => now()->subDay()->timestamp]));

        $this->artisan('backup:files-daily')->assertSuccessful();

        $zips = glob($root.'/app/private/backups/files-backup-gunluk-*.zip');
        $this->assertCount(1, $zips);
        $zip = new \ZipArchive;
        $zip->open($zips[0]);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        $this->assertContains('uploads/facilities/1/yeni.webp', $names);
        $this->assertContains('uploads/claims/belge.pdf', $names);
        $this->assertNotContains('uploads/facilities/1/eski.webp', $names);

        $state = json_decode(file_get_contents($root.'/app/private/backups/files-backup-state.json'), true);
        $this->assertGreaterThanOrEqual(now()->subMinute()->timestamp, $state['son_yedek']);
        $this->assertSame(2, $state['dosya_sayisi']);

        File::deleteDirectory($root);
    }
}
