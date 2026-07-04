<?php

namespace App\Console\Commands;

use App\Services\ImageCompressionService;
use Illuminate\Console\Command;

// Sunucuya deploy ettikten sonra bir kere calistirilip kurum galerisi
// gorsellerinin gercekten sikistirilip sikistirilamayacagini dogrulamak
// icindir (bkz. docs/PRODUCTION.md bolum 14) — GD/Imagick gelistirme
// ortaminda kurulu olmadigindan bu kontrol yerelde anlamli sonuc vermez.
class CheckImageCompression extends Command
{
    protected $signature = 'diagnostics:image-compression';

    protected $description = 'Sunucuda kurum galerisi gorsel sikistirmasinin (GD/Imagick) calisip calismadigini test eder';

    public function handle(ImageCompressionService $service): int
    {
        $result = $service->diagnostics();

        if (! $result['driver']) {
            $this->error($result['message']);
            $this->line('Kurulum icin: sunucuya php-gd veya php-imagick eklentisi ekleyin (bkz. docs/PRODUCTION.md bolum 1).');

            return self::FAILURE;
        }

        if (! $result['ok']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);
        $this->line("Kullanilan surucu: {$result['driver']}");
        $this->line("Test goruntusu: {$result['input_bytes']} bayt (PNG) -> {$result['output_bytes']} bayt (WebP)");

        return self::SUCCESS;
    }
}
