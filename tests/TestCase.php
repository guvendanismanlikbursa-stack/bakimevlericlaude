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
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}