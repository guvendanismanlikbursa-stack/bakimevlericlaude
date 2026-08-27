<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $this->guardAgainstRealDatabase($app);

        return $app;
    }

    /**
     * 2026-07-09 olayi: eski/stale bir bootstrap/cache/config.php dosyasi,
     * phpunit.xml'deki DB_DATABASE=:memory: ayarini gormezden gelip
     * RefreshDatabase'in GERCEK database/database.sqlite dosyasini sifirlamasina
     * (ve binlerce gercek kurum kaydinin silinmesine) sebep oldu. Bu kontrol,
     * hangi sebeple olursa olsun (stale cache, yanlis .env, vs.) testlerin
     * sqlite ':memory:' disinda bir veritabanina baglanmasini FATAL hata ile
     * engeller - bir daha asla gercek veriye dokunulmasin diye.
     */
    private function guardAgainstRealDatabase($app): void
    {
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection === 'sqlite' && $database !== ':memory:') {
            fwrite(STDERR, "\n\nFATAL: Testler gercek bir sqlite dosyasina ({$database}) baglanmaya calisiyor, ':memory:' degil.\n".
                "Once 'php artisan config:clear' calistirin (muhtemelen eski bir bootstrap/cache/config.php var).\n".
                "Testler GUVENLIK icin durduruldu.\n\n");
            exit(1);
        }
    }

    protected function fakePngUpload(string $name = 'document.png'): UploadedFile
    {
        $dir = storage_path('framework/testing/files');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.uniqid('upload_', true).'.png';
        // 26 Agustos 2026: onceki sabit base64 1x1 PNG'nin IDAT CRC'si bozuktu -
        // finfo/mime bazli eski dogrulamayi (gorunuste "gecerli PNG") geciyordu
        // ama GD'nin gercek PNG decode'u basarisiz oluyordu; bkz. ImageCompressionService
        // ayni tarihli yorum - artik decode BASARISIZ olan dosyalar sessizce ham
        // kaydedilmek yerine reddediliyor, bu yuzden testlerin GERCEKTEN gecerli
        // (2x2, beyaz) bir PNG kullanmasi gerekiyor.
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWP8//8/AwMDEwMDAwMDAwAkBgMBmjCi+wAAAABJRU5ErkJggg=='));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    // 14 Agustos 2026: kullanicinin talebi uzerine yapilan genis denetimde
    // bulunan guvenlik acigi testi icin - Facility\SubscriptionController::
    // store() eskiden 'image' validation kuralini kullaniyordu, bu SVG'yi
    // de kabul ediyordu (script gomulebilen bir format). Bu helper GERCEK
    // bir SVG dosyasi (icinde <script> ile) uretir, mimes: kuralinin bunu
    // reddettigini test edebilmek icin.
    protected function fakeMaliciousSvgUpload(string $name = 'evil.svg'): UploadedFile
    {
        $dir = storage_path('framework/testing/files');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.uniqid('upload_', true).'.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>');

        return new UploadedFile($path, $name, 'image/svg+xml', null, true);
    }

    // 27 Agustos 2026: video yukleme ozelligi icin - GERCEK, gecerli (1sn,
    // 64x64) bir H.264 MP4 klip, ffmpeg ile onceden uretilip base64 olarak
    // gomuldu (fakePngUpload'daki ayni gerekce: VideoCompressionService
    // GERCEK ffmpeg ile decode/prob yapiyor, sahte/bozuk bir dosya kabul edilmez).
    protected function fakeMp4Upload(string $name = 'video.mp4'): UploadedFile
    {
        $dir = storage_path('framework/testing/files');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.uniqid('upload_', true).'.mp4';
        file_put_contents($path, base64_decode('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'));

        return new UploadedFile($path, $name, 'video/mp4', null, true);
    }
}