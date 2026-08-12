<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

// 10-11 Agustos 2026: bir kurumun (gercek, sahiplenilmis) 9 galeri gorseli
// yuklendikten sonra sessizce diskten kayboldu - hicbir hata/log/bildirim
// olmadan fark edildi, kurum yetkilisi gorsel eklemis gibi gorunuyor ama
// site kirik gosteriyordu (bkz. ProfileController::uploadImage'daki
// yukleme-ANI dogrulamasi - o an dosya gercekten vardi, bu yuzden hicbir
// hata tetiklenmedi, sorun SONRADAN olustu). Bu komut GUNLUK calisip
// SON 48 SAATTE yuklenen GERCEK (demo/on-kayit sablonu olmayan) gorselleri
// tekrar kontrol eder - biri "sonradan" kaybolmussa bunu ilk defa burada
// yakalariz. Bulunca record_platform_error() (bkz. app/helpers.php)
// uzerinden hem admin paneline ("Hatalar" ekrani) kalici kayit dusurur
// hem her admin hesabina mail atar.
class CheckGalleryHealth extends Command
{
    protected $signature = 'gallery:check-health {--hours=48 : Bu saat icinde yuklenen gorseller kontrol edilir}';

    protected $description = 'Son yuklenen kurum galeri gorsellerinin sunucu diskinde var olup olmadigini VE gece yedeklemesinin gercekten calisip calismadigini kontrol eder, sorun bulursa admin panelinde kayit birakip mail atar';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $images = DB::table('facility_images')
            ->join('facilities', 'facilities.id', '=', 'facility_images.facility_id')
            ->where('facility_images.created_at', '>=', $cutoff)
            ->where('facility_images.path', 'not like', 'facilities/demo/%')
            ->select('facility_images.id', 'facility_images.facility_id', 'facility_images.path', 'facility_images.created_at', 'facilities.name as facility_name')
            ->get();

        $broken = $images->filter(fn ($img) => ! Storage::disk('public')->exists($img->path))->values();

        if ($broken->isEmpty()) {
            $this->info("Kontrol edildi: {$images->count()} gorsel (son {$hours} saat), hepsi diskte mevcut.");
        } else {
            $this->error("{$broken->count()} kirik gorsel bulundu (taranan: {$images->count()}).");

            $list = $broken->map(fn ($img) => "- {$img->facility_name} (kurum #{$img->facility_id}), gorsel #{$img->id}, yuklenme: {$img->created_at}, yol: {$img->path}")->implode("\n");

            record_platform_error(
                'gallery-health-check',
                "{$broken->count()} kurum galeri görseli sunucuda bulunamadı",
                "Son {$hours} saatte yuklenen {$images->count()} gercek kurum gorseli tarandi, {$broken->count()} tanesi veritabaninda kayitli oldugu halde sunucu diskinde YOK (kirik gorunuyor). Etkilenen kayitlar:\n\n{$list}\n\nBu, veri cekiciden gelen 'demo' ornek gorsellerle ilgili degildir (onlar bu taramaya hic dahil edilmez) - bunlar kurumun kendi yukledigi GERCEK gorsellerin sunucudan kaybolmasi. Kurum yetkilisine tekrar yuklemesi soylenmeli.",
                ['broken_ids' => $broken->pluck('id')->all(), 'facility_ids' => $broken->pluck('facility_id')->unique()->values()->all()]
            );
        }

        $this->checkBackupCompleted();

        // 12 Agustos 2026: SUCCESS donuyor - kirik gorsel/eksik yedek bulunmasi
        // bir KOMUT/SCRIPT hatasi degil, birer IS BULGUSU (zaten yukarida
        // record_platform_error() ile ayrica raporlandi). FAILURE
        // donmek Laravel'in kendi Scheduler'ini "scheduled command
        // failed" diye AYRI, alakasiz bir ikinci hata/mail uretmeye
        // tetikliyordu (bkz. ScheduleRunCommand) - tek, anlamli bir
        // bildirim yeterli.
        return self::SUCCESS;
    }

    // 12 Agustos 2026: kullanicinin talebi - "her sabah kontrol eden script"
    // artik gece 03:30'daki backup:database komutunun GERCEKTEN calisip
    // dosya birakip birakmadigini da dogrular (calismasi/basarisiz olmasi
    // sessizce fark edilmeden gunlerce surebilirdi). Ayri bir kaynak adiyla
    // (daily-backup-check) raporlanir ki Hatalar panelinde goruntu
    // sorunlarindan ayirt edilebilsin.
    private function checkBackupCompleted(): void
    {
        $dir = storage_path('app/private/backups');
        $today = now()->format('Y-m-d');

        $files = File::isDirectory($dir) ? collect(File::files($dir)) : collect();
        $sqlBackup = $files->first(fn ($f) => str_starts_with($f->getFilename(), "backup-{$today}_") && str_ends_with($f->getFilename(), '.sql.gz'));
        $filesBackup = $files->first(fn ($f) => str_starts_with($f->getFilename(), "files-backup-{$today}_") && str_ends_with($f->getFilename(), '.zip'));

        $problems = [];

        if (! $sqlBackup) {
            $problems[] = "Bugun ({$today}) icin veritabani yedegi (backup-{$today}_*.sql.gz) storage/app/private/backups klasorunde bulunamadi - gece 03:30'daki backup:database komutu hic calismamis veya basarisiz olmus olabilir.";
        } elseif ($sqlBackup->getSize() < 1024) {
            $problems[] = "Bugunku veritabani yedegi ({$sqlBackup->getFilename()}) suphesiz kucuk (".$sqlBackup->getSize()." bayt) - yarim kalmis/bozuk olabilir.";
        }

        if (! $filesBackup) {
            $problems[] = "Bugun icin dosya yedegi (files-backup-{$today}_*.zip - kurum gorselleri, sahiplenme belgeleri ve .env) bulunamadi.";
        }

        if (empty($problems)) {
            $this->info("Yedekleme kontrolu: bugunku ({$today}) yedek dosyalari mevcut.");

            return;
        }

        $message = implode("\n", $problems);
        $this->error("Yedekleme kontrolu basarisiz:\n{$message}");

        record_platform_error(
            'daily-backup-check',
            'Günlük yedekleme kontrolü başarısız',
            $message
        );
    }
}
