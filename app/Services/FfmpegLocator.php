<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * 27 Agustos 2026: kullanicinin talebi - anlasmali kurumlara video yukleme
 * ozelligi icin. Sunucuda FFmpeg sistem paketi olarak kurulamadigi
 * (paylasimli hosting, root yok) icin bagimsiz (static, johnvansickle.com)
 * bir surumu app'in kendi depolama alanina kuruldu (bkz.
 * OpsController::ffmpegInstall()). Bu sinif, o yerel kurulum yolunu ve
 * olasi sistem yollarini AYNI listede tarayan TEK kaynak - OpsController
 * (teshis) ve VideoCompressionService (gercek kullanim) ayni listeyi kullanir.
 */
class FfmpegLocator
{
    public static function localInstallPath(): string
    {
        return storage_path('app/private/bin/ffmpeg');
    }

    public static function candidates(): array
    {
        return ['ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/alt/ffmpeg/bin/ffmpeg', self::localInstallPath()];
    }

    public static function resolve(): ?string
    {
        foreach (self::candidates() as $binary) {
            try {
                $probe = new Process([$binary, '-version']);
                $probe->run();
                if ($probe->isSuccessful()) {
                    return $binary;
                }
            } catch (\Throwable $e) {
                // sessizce atla, siradaki adayi dene
            }
        }

        return null;
    }
}
