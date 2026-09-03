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
 * "tek depolama alanı") - facility_asset() helper'i videoyu da (tum
 * gorseller gibi) her zaman bakimevleri.com uzerinden sunar. 3 Eylul 2026:
 * bunun calisabilmesi icin TEK kopyanin GERCEKTEN bakimevleri.com'da
 * olmasi sart - baska bir domain'den yuklenirse cagiran taraf
 * sync_video_to_canonical_domain() (bkz. helpers.php) ile onu oraya da
 * yazmali, aksi halde video kirik/gorunmez olur (yasanan gercek hata).
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

        // 3 Eylul 2026: kullanicinin bildirdigi gercek hata - "desteklenmeyen
        // veya bozuk video dosyasi" hicbir log/hata kaydi birakmiyordu,
        // gercek sebebi (ffmpeg'in GERCEK stderr ciktisi) hic gorunmuyordu.
        // Simdi hem probe hem donusum asamasinda basarisizlik durumunda
        // ffmpeg'in kendi ciktisi loglanir.
        [$duration, $probeOutput] = $this->probeDuration($ffmpeg, $realPath);
        if ($duration === null) {
            \Illuminate\Support\Facades\Log::warning('Video sure tespiti basarisiz (unsupported_video_format).', [
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'ffmpeg_output' => mb_substr($probeOutput, -2000),
            ]);
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
            // 3 Eylul 2026: kullanicinin bildirdigi gercek hata - once
            // "AVX512 sanallastirmada calismiyor" sanilmisti (asm=0 eklendi)
            // ama hata AYNEN devam etti; yeni bir canli teshis ucuyla
            // (/_ops/ffmpeg-x264-diagnose, gecici, sonradan kaldirilabilir)
            // GERCEK kok neden bulundu: "x264 [error]: malloc of size
            // 1586256 failed". Sunucu nproc'ta 40 cekirdek gosteriyor,
            // x264 buna gore otomatik cok sayida thread acmaya calisiyor,
            // her thread kendi arabellegini ayiriyor - toplam bu paylasimli
            // hesabin ulimit -v (sanal bellek) sinirini (~2GB) asiyor,
            // encoder hic acilamiyor. Cozum: thread sayisini sabit ve
            // dusuk tutmak (hem ffmpeg hem x264'un kendi ic thread havuzu
            // icin ayri ayri belirtilmesi gerekiyor).
            $convert = new Process([
                $ffmpeg, '-y', '-threads', '1', '-i', $realPath,
                '-vf', 'scale='.self::MAX_WIDTH.':-2:force_original_aspect_ratio=decrease',
                '-c:v', 'libx264', '-preset', 'medium', '-crf', '30', '-x264-params', 'threads=1',
                '-c:a', 'aac', '-b:a', '96k', '-ac', '2',
                '-movflags', '+faststart',
                $outPath,
            ]);
            $convert->setTimeout(150);
            $convert->run();

            if (! $convert->isSuccessful() || ! is_file($outPath) || filesize($outPath) < 500) {
                \Illuminate\Support\Facades\Log::warning('Video donusumu basarisiz (unsupported_video_format).', [
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'exit_code' => $convert->getExitCode(),
                    'ffmpeg_output' => mb_substr($convert->getErrorOutput(), -2000),
                ]);
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
     *
     * @return array{0: ?float, 1: string} [sure_saniye, ffmpeg_ciktisi]
     */
    private function probeDuration(string $ffmpeg, string $inputPath): array
    {
        $probe = new Process([$ffmpeg, '-i', $inputPath]);
        $probe->setTimeout(20);
        $probe->run();

        if (preg_match('/Duration:\s*(\d+):(\d+):(\d+\.\d+)/', $probe->getErrorOutput(), $m)) {
            return [((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + (float) $m[3], $probe->getErrorOutput()];
        }

        return [null, $probe->getErrorOutput()];
    }
}
