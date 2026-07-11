<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Bakim: Bu host paylasimli cPanel hosting oldugu icin shell_exec/mysqldump
// binary'sine guvenilir erisim yok - saf PHP/PDO ile tablo tablo dump alinir.
// Yedekler sunucunun kendi diskinde tutulur (offsite degildir), amac "yanlislikla
// veri silme/bozma" senaryosuna karsi hizli bir geri donus noktasi saglamaktir.
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep-days=14 : Bu gunden eski yedekler silinir}';

    protected $description = 'Veritabaninin tamamini gzip\'li SQL dump olarak storage/app/private/backups altina yedekler';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $dir = storage_path('app/private/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filename = 'backup-'.now()->format('Y-m-d_His').'.sql.gz';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        if ($driver === 'sqlite') {
            $dbPath = config("database.connections.{$connection}.database");
            $gz = gzopen($path, 'wb9');
            gzwrite($gz, file_get_contents($dbPath));
            gzclose($gz);
        } elseif ($driver === 'mysql') {
            $this->dumpMysql($path);
        } else {
            $this->error("Desteklenmeyen DB driver: {$driver}");

            return self::FAILURE;
        }

        $size = File::size($path);
        $this->info("Yedek olusturuldu: {$filename} (".number_format($size / 1024, 1).' KB)');

        $this->pruneOldBackups($dir, (int) $this->option('keep-days'));

        return self::SUCCESS;
    }

    private function dumpMysql(string $path): void
    {
        $gz = gzopen($path, 'wb9');
        gzwrite($gz, "-- Bakim Platform DB yedegi - ".now()->toDateTimeString()."\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $dbName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$dbName}";

        foreach (DB::select('SHOW TABLES') as $tableRow) {
            $table = $tableRow->$tableKey;

            $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n".$create->{'Create Table'}.";\n\n");

            $columns = null;
            $buffer = [];

            foreach (DB::table($table)->cursor() as $row) {
                $rowArr = (array) $row;
                if ($columns === null) {
                    $columns = array_keys($rowArr);
                }
                $buffer[] = $rowArr;

                if (count($buffer) >= 500) {
                    $this->writeInsertBatch($gz, $table, $columns, $buffer);
                    $buffer = [];
                }
            }

            if (! empty($buffer)) {
                $this->writeInsertBatch($gz, $table, $columns, $buffer);
            }
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);
    }

    /**
     * @param  resource  $gz
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function writeInsertBatch($gz, string $table, array $columns, array $rows): void
    {
        $pdo = DB::getPdo();
        $columnList = '`'.implode('`, `', $columns).'`';

        $valueRows = array_map(function (array $row) use ($pdo) {
            $values = array_map(function ($value) use ($pdo) {
                return is_null($value) ? 'NULL' : $pdo->quote((string) $value);
            }, $row);

            return '('.implode(', ', $values).')';
        }, $rows);

        gzwrite($gz, "INSERT INTO `{$table}` ({$columnList}) VALUES\n".implode(",\n", $valueRows).";\n\n");
    }

    private function pruneOldBackups(string $dir, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach (File::files($dir) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("{$deleted} eski yedek silindi (>{$keepDays} gun).");
        }
    }
}
