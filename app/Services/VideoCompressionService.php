<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * 27 Agustos 2026: kullanicinin talebi - SADECE anlasmali (is_broker_managed)
 * kurumlara ozel tanitim videosu yukleme (bkz. Admin\FacilityController -
 * bu servis kurumun anlasmali olup olmadigini KENDISI kontrol etmez, cagiran
 * taraf sorumludur). En fazla 60 saniye + agresif sikistirma (kullanicinin
 * acik talebi - depolama maliyetini kontrol altinda tutmak icin) H.264 MP4'e
 * cevrilir (genis tarayici/telefon uyumlulugu icin, webm degil).
 *
 * Depolama BILEREK 3 domain'e kopyalanmiyor (kullanicinin acik talebi -
 * "tek depolama alanı") - facility_asset() helper'i zaten TUM gorseller
 * icin oldugu gibi videoyu da her zaman bakimevleri.com uzerinden sunar.
 */
class VideoCompressionService
{
    private const MAX_DURATION_SECONDS = 60;

    private const MAX_WIDTH = 720;

    /**
     * @throws \RuntimeException 'ffmpeg_unavailable', 'unsupported_video_format', veya 'video_too_long'
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $ffmpeg = FfmpegLocator::resolve();
        if (! $ffmpeg) {
            throw new \RuntimeException('ffmpeg_unavailable');
        }

        $realPath = $file->getRealPath();
        if (! $realPath || ! is_file($realPath)) {
            throw new \RuntimeException('unsupported_video_format');
        }

        $duration = $this->probeDuration($ffmpeg, $realPath);
        if ($duration === null) {
            throw new \RuntimeException('unsupported_video_format');
        }
        if ($duration > self::MAX_DURATION_SECONDS) {
            throw new \RuntimeException('video_too_long');
        }

        $outPath = tempnam(sys_get_temp_dir(), 'vidout_').'.mp4';
        @unlink($outPath);

        try {
            // 27 Agustos 2026: shared hosting'te PHP istegi kendi
            // max_execution_time'ina (kisa, ör. 30sn) sahip - cagiran taraf
            // (Admin\FacilityController) set_time_limit() ile bunu genisletir,
            // burada sadece Process'in KENDI zaman asimini genis tutuyoruz.
            $convert = new Process([
                $ffmpeg, '-y', '-i', $realPath,
                '-vf', 'scale='.self::MAX_WIDTH.':-2:force_original_aspect_ratio=decrease',
                '-c:v', 'libx264', '-preset', 'medium', '-crf', '30',
                '-c:a', 'aac', '-b:a', '96k', '-ac', '2',
                '-movflags', '+faststart',
                $outPath,
            ]);
            $convert->setTimeout(150);
            $convert->run();

            if (! $convert->isSuccessful() || ! is_file($outPath) || filesize($outPath) < 500) {
                throw new \RuntimeException('unsupported_video_format');
            }

            $path = trim($directory, '/').'/'.Str::random(32).'.mp4';
            Storage::disk($disk)->put($path, file_get_contents($outPath));

            return $path;
        } finally {
            @unlink($outPath);
        }
    }

    /**
     * FFmpeg'in kendisi (ffprobe'a ihtiyac duymadan) '-i' ile cagrildiginda
     * cikti dosyasi verilmese bile stderr'e "Duration: HH:MM:SS.ms" satirini
     * yazar - bu, ikinci bir ikili dosyaya (ffprobe) ihtiyac duymadan sure
     * tespiti icin yeterli.
     */
    private function probeDuration(string $ffmpeg, string $inputPath): ?float
    {
        $probe = new Process([$ffmpeg, '-i', $inputPath]);
        $probe->setTimeout(20);
        $probe->run();

        if (preg_match('/Duration:\s*(\d+):(\d+):(\d+\.\d+)/', $probe->getErrorOutput(), $m)) {
            return ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + (float) $m[3];
        }

        return null;
    }
}
