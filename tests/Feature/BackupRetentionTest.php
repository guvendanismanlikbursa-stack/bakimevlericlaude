<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

// 17 Ekim 2026: kullanıcının talebi - SQL yedeğinden son 3 gün, dosya
// zip'inden yalnızca en son olan saklanır.
class BackupRetentionTest extends TestCase
{
    public function test_prune_keeps_recent_sql_and_only_latest_zip(): void
    {
        $dir = storage_path('framework/testing/backup-retention');
        File::deleteDirectory($dir);
        File::makeDirectory($dir, 0755, true);

        $make = function (string $name, int $daysAgo) use ($dir) {
            file_put_contents("$dir/$name", 'x');
            touch("$dir/$name", now()->subDays($daysAgo)->timestamp);
        };
        $make('backup-old.sql.gz', 5);
        $make('backup-recent.sql.gz', 1);
        $make('files-backup-older.zip', 13);
        $make('files-backup-newer.zip', 6);

        $command = app(BackupDatabase::class);
        $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
        $method = new \ReflectionMethod($command, 'pruneOldBackups');
        $method->invoke($command, $dir, 3);

        $this->assertFileDoesNotExist("$dir/backup-old.sql.gz");
        $this->assertFileExists("$dir/backup-recent.sql.gz");
        $this->assertFileDoesNotExist("$dir/files-backup-older.zip");
        $this->assertFileExists("$dir/files-backup-newer.zip");

        File::deleteDirectory($dir);
    }
}
