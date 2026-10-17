<?php

namespace App\Console\Commands;

use App\Mail\BackupCreatedMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

// Bakim: Bu host paylasimli cPanel hosting oldugu icin shell_exec/mysqldump
// binary'sine guvenilir erisim yok - saf PHP/PDO ile tablo tablo dump alinir.
// 13 Temmuz 2026'dan itibaren yedek sadece sunucu diskinde degil, ayrica
// MAIL_FROM_ADDRESS adresine (bakimevleri@gmail.com) posta ekiyle de
// gonderiliyor - amac "yanlislikla veri silme/bozma" senaryosuna karsi
// hizli bir geri donus noktasi VE sunucu/hesap tamamen kaybedilirse bile
// erisilebilir bir kopya saglamaktir.
//
// 12 Agustos 2026: kullanicinin talebi - yedek SADECE veritabani tablolariydi,
// diskteki yuklenen kurum gorselleri/sahiplenme belgeleri ve .env (DB/mail
// sifreleri, API anahtarlari) yedeklenmiyordu. Sunucu tamamen kaybedilirse
// bu ikisi olmadan veritabani yedeği tek basina TAM bir kurtarma saglamaz
// (gorseller kirik link olur, .env sifirdan elle yazilmasi gerekir). Artik
// ayni gecede ikinci bir files-backup-*.zip da olusturuluyor (bkz.
// buildFilesZip()) ve boyutu izin verdigi surece o da e-postaya ekleniyor.
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep-days=3 : Bu gunden eski SQL yedekleri silinir} {--if-missing : Bugun icin SQL yedegi zaten varsa hicbir sey yapma} {--with-files : Dosya (gorsel) zip yedegini de al (varsayilan: sadece Pazar)}';

    protected $description = 'Veritabanini + yuklenen dosyalari/.env\'i storage/app/private/backups altina yedekler';

    public function handle(): int
    {
        // 4 Ekim 2026: bu komut hem CLI cron'dan (sinirsiz sure) hem de
        // /_ops/backup-now web ucundan (PHP'nin 30sn web limitine tabi)
        // tetiklenebiliyor - buyuk dosya sayisinda zip olusturma 30sn'yi
        // asabilir, bu yuzden kaynaktan bagimsiz guvenceye aliniyor.
        set_time_limit(0);

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $dir = storage_path('app/private/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // 17 Ekim 2026: kullanıcının talebi - 3 site aynı veritabanını
        // paylaştığı için aynı SQL yedeği her gece 3 kez (her sitenin kendi
        // klasörüne) alınıyordu. Yedek artık yalnızca ana sitede alınır;
        // diğer sitelerde kalmış eski yedekler temizlenir (klasörlerin ayrı
        // olduğu FTP ile doğrulandı, ana sitenin yedeklerine dokunulmaz).
        if (! is_primary_site() && ! $this->option('with-files')) {
            foreach (File::files($dir) as $file) {
                if (preg_match('/^(backup-.*\.sql\.gz|files-backup-.*\.zip)$/', $file->getFilename())) {
                    File::delete($file->getPathname());
                }
            }
            $this->info('Ana site degil, yedek ana sitede (public_html) aliniyor - atlandi.');

            return self::SUCCESS;
        }

        if ($this->option('if-missing') && count(glob($dir.DIRECTORY_SEPARATOR.'backup-'.now()->format('Y-m-d').'_*.sql.gz') ?: []) > 0) {
            $this->info('Bugunun yedegi zaten var, atlandi.');

            return self::SUCCESS;
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
        $sizeLabel = number_format($size / 1024, 1).' KB';
        $this->info("Yedek olusturuldu: {$filename} ({$sizeLabel})");

        // 25 Eylul 2026: gorsel klasoru her gece zip'lenince (yuzlerce MB) hosting kaynak sinirina takiliyor ve
        // gunluk yedek hic tamamlanmiyordu; SQL yedegi gunluk, dosya zip'i haftalik (Pazar) alinir.
        //
        // 11 Ekim 2026: kullanicinin bulgusu - CheckGalleryHealth'teki gunluk
        // kontrol, dosya yedegi eksikse bunu ANCAK 8 GUN SONRA fark ediyordu.
        // Kok neden: paylasimli hosting'in (buyuyen, artik 3.6+ GB'lik) zip
        // islemini bazi Pazarlar surec/kaynak siniri yuzunden SESSIZCE
        // yarida kesmesi (hicbir Exception/hata kaydi birakmadan) - haftalarca
        // fark edilmeden surebiliyordu. Artik buildFilesZip() try/catch'e
        // alinir, basarisiz olursa (veya beklenenden kucuk/bos ciktiginda)
        // AYNI GUN record_platform_error() ile acik bir hata kaydi dusurulur -
        // 8 gun beklemeden, admin Hatalar panelinde hemen gorulur.
        $filesZipPath = null;
        $filesZipSize = 0;
        // 17 Ekim 2026: denetim bulgusu (FTP ile doğrulandı) - bakimevibul ve
        // bakimeviara'nın storage/app/public/facilities klasörü ana sitenin
        // (public_html) klasörüne symlink. Üç sitenin cron'u Pazar 03:30'da
        // AYNI 3,6+ GB'lık klasörü AYNI ANDA zipliyordu; paylaşımlı sunucu
        // süreçleri log bırakmadan öldürüyor, hiçbir sitede zip oluşmuyordu.
        // Dosya zip'i artık yalnızca ana sitede alınır (SQL yedeği her sitede aynen sürer).
        if ($this->option('with-files') || (now()->isSunday() && is_primary_site())) {
            try {
                [$filesZipPath, $filesZipSize] = $this->buildFilesZip($dir);
                if (! $filesZipPath) {
                    record_platform_error(
                        'backup-files-zip',
                        'Haftalık dosya yedeği oluşturulamadı',
                        'buildFilesZip() null döndü (ZipArchive eklentisi eksik veya zip dosyası açılamadı) - '.now()->format('Y-m-d H:i:s')
                    );
                } elseif ($filesZipSize < 1024) {
                    record_platform_error(
                        'backup-files-zip',
                        'Haftalık dosya yedeği şüpheli derecede küçük',
                        basename($filesZipPath).' sadece '.$filesZipSize.' bayt - yarım kalmış olabilir.'
                    );
                }
            } catch (\Throwable $e) {
                record_platform_error(
                    'backup-files-zip',
                    'Haftalık dosya yedeği oluşturma hatası',
                    get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')'
                );
            }
        }
        if ($filesZipPath) {
            $this->info('Dosya yedegi olusturuldu: '.basename($filesZipPath).' ('.number_format($filesZipSize / 1024 / 1024, 1).' MB)');
        }

        $this->emailBackup($filename, $sizeLabel, $path, $size, $filesZipPath, $filesZipSize);

        $this->pruneOldBackups($dir, (int) $this->option('keep-days'));

        return self::SUCCESS;
    }

    /**
     * .env (gercek sunucu sifreleri/API anahtarlari) ile diskte duran,
     * veritabaninda SADECE dosya yolu olarak tutulan kurum gorselleri ve
     * sahiplenme basvuru belgelerini tek bir zip'te toplar. ZipArchive
     * eklentisi yoksa (beklenmez, XLSX export'ta zaten kullaniliyor) sessizce
     * atlanir - DB yedegi yine de tamamlanmis olur.
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function buildFilesZip(string $dir): array
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->warn('ZipArchive eklentisi yok, dosya yedegi atlandi.');

            return [null, null];
        }

        $zipPath = $dir.DIRECTORY_SEPARATOR.'files-backup-'.now()->format('Y-m-d_His').'.zip';
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->warn('files-backup zip acilamadi, atlandi.');

            return [null, null];
        }

        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $zip->addFile($envPath, '.env');
        }

        $sources = [
            'uploads/facilities' => storage_path('app/public/facilities'),
            'uploads/claims' => storage_path('app/private/claims'),
        ];

        // 4 Ekim 2026: kullanicinin bildirdigi gercek hata - bugunku toplu
        // importlar sonrasi facilities klasorunde on binlerce dosya
        // birikince File::allFiles() (TUM sonuclari once bir array'e
        // TOPLAYAN, Symfony Finder tabanli) 128MB PHP bellek limitini
        // asip Pazar gecesi otomatik yedegi KALICI olarak cokertiyordu.
        // RecursiveDirectoryIterator/RecursiveIteratorIterator ile dogrudan
        // STREAMING gezinmek (tek seferde bir tek dosya yolu bellekte)
        // bellek kullanimini dosya SAYISINDAN BAGIMSIZ, sabit tutar.
        foreach ($sources as $zipPrefix => $sourceDir) {
            if (! File::isDirectory($sourceDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $relative = substr($file->getPathname(), strlen($sourceDir) + 1);
                $zip->addFile($file->getPathname(), $zipPrefix.'/'.str_replace('\\', '/', $relative));
            }
        }

        $zip->close();

        if (! File::exists($zipPath) || File::size($zipPath) === 0) {
            return [null, null];
        }

        return [$zipPath, File::size($zipPath)];
    }

    // Gmail ekleri gercekte ~25MB sinirlidir, base64 kodlama da boyutu ~%33
    // buyuttugu icin guvenli tarafta kalmak adina 15MB ustunde dosyayi
    // eklemeden sadece bilgilendirme maili gonderir. Iki ek (SQL + files-zip)
    // birlikte 15MB'i asarsa, kucuk olan (genelde SQL dump) yine de eklenir -
    // dosya yedegi o zaman sadece sunucu diskinde kalir.
    private function emailBackup(string $filename, string $sizeLabel, string $path, int $size, ?string $filesZipPath = null, ?int $filesZipSize = null): void
    {
        $adminEmail = config('mail.from.address');
        if (! $adminEmail) {
            return;
        }

        $limit = 15 * 1024 * 1024;
        $attachmentPath = $size <= $limit ? $path : null;
        $combinedSize = $size + (int) $filesZipSize;
        $filesZipAttachPath = ($filesZipPath && $combinedSize <= $limit) ? $filesZipPath : null;

        try {
            Mail::to($adminEmail)->sendNow(new BackupCreatedMail($filename, $sizeLabel, $attachmentPath, $filesZipAttachPath));
        } catch (\Throwable $e) {
            $this->error('Yedek maili gonderilemedi: '.$e->getMessage());
            \Illuminate\Support\Facades\Log::error('Yedek maili gonderilemedi: '.$e->getMessage());
        }
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

        // 17 Ekim 2026: kullanıcının talebi - SQL yedeğinden son $keepDays
        // gün (varsayılan 3), 3,6+ GB'lık haftalık dosya zip'inden ise
        // yalnızca EN SON olan saklanır. Zip'ler gün sınırına göre
        // silinmez; aksi halde haftalık zip 3 gün sonra silinir, hafta
        // ortasında hiç dosya yedeği kalmazdı.
        $zips = [];
        foreach (File::files($dir) as $file) {
            if (str_starts_with($file->getFilename(), 'files-backup-')) {
                $zips[] = $file;

                continue;
            }
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        usort($zips, fn ($a, $b) => $b->getMTime() <=> $a->getMTime());
        foreach (array_slice($zips, 1) as $old) {
            File::delete($old->getPathname());
            $deleted++;
        }

        if ($deleted > 0) {
            $this->info("{$deleted} eski yedek silindi (>{$keepDays} gun).");
        }
    }
}
