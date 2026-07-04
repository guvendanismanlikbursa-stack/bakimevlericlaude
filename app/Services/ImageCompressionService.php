<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kurum galerisi gorsellerini (admin ve kurum paneli yuklemeleri) maksimum
 * performans icin WebP'ye cevirip kucultur: sayfa yuklenme hizi/bant
 * genisligi onemli olcude azalir. Imagick > GD > (ikisi de yoksa) orijinal
 * dosya sirasiyla denenir; sunucuda ikisi de yoksa yukleme yine de basarili
 * olur, sadece sikistirma atlanir (bkz. docs/PRODUCTION.md PHP eklentileri).
 *
 * Sadece YENI yuklemelere uygulanir; storage'daki mevcut gorseller bilerek
 * dokunulmadan birakildi.
 */
class ImageCompressionService
{
    private const MAX_DIMENSION = 1600;
    private const QUALITY = 78;

    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $data = $this->compress($file);

        if ($data === null) {
            return $file->store($directory, $disk);
        }

        $path = trim($directory, '/').'/'.Str::random(32).'.webp';
        Storage::disk($disk)->put($path, $data);

        return $path;
    }

    /**
     * Sunucuda gercekten sikistirma yapilip yapilamayacagini kontrol eder
     * (bkz. `php artisan diagnostics:image-compression`). Bu ortamda
     * (gelistirme makinesi) GD/Imagick kurulu olmadigi icin sadece bu
     * komutu CALISTIRAN sunucuda anlamli sonuc verir.
     *
     * @return array{driver: ?string, ok: bool, message: string, input_bytes: ?int, output_bytes: ?int}
     */
    public function diagnostics(): array
    {
        $driver = extension_loaded('imagick') ? 'imagick' : (extension_loaded('gd') && function_exists('imagewebp') ? 'gd' : null);

        if (! $driver) {
            return [
                'driver' => null,
                'ok' => false,
                'message' => 'Ne Imagick ne de webp destekli GD kurulu. Yeni galeri gorselleri sikistirilmadan (orijinal haliyle) kaydedilecek.',
                'input_bytes' => null,
                'output_bytes' => null,
            ];
        }

        // Kucuk bir test PNG'i bellekte uretip tam boru hattindan (resize+webp) geciriyoruz.
        $testImage = imagecreatetruecolor(400, 300);
        $blue = imagecolorallocate($testImage, 30, 111, 92);
        imagefilledrectangle($testImage, 0, 0, 400, 300, $blue);
        ob_start();
        imagepng($testImage);
        $pngData = ob_get_clean();
        imagedestroy($testImage);

        $tmpPath = tempnam(sys_get_temp_dir(), 'imgtest_').'.png';
        file_put_contents($tmpPath, $pngData);

        try {
            $output = $driver === 'imagick'
                ? $this->compressWithImagick($tmpPath)
                : $this->compressWithGd($tmpPath, 'image/png');

            if ($output === null) {
                return [
                    'driver' => $driver,
                    'ok' => false,
                    'message' => "{$driver} kurulu ama test gorseli webp'ye cevrilemedi (bkz. sunucu PHP error log).",
                    'input_bytes' => strlen($pngData),
                    'output_bytes' => null,
                ];
            }

            return [
                'driver' => $driver,
                'ok' => true,
                'message' => "{$driver} ile sikistirma calisiyor.",
                'input_bytes' => strlen($pngData),
                'output_bytes' => strlen($output),
            ];
        } finally {
            @unlink($tmpPath);
        }
    }

    private function compress(UploadedFile $file): ?string
    {
        $realPath = $file->getRealPath();

        if (! $realPath || ! is_file($realPath)) {
            return null;
        }

        if (extension_loaded('imagick')) {
            $result = $this->compressWithImagick($realPath);
            if ($result !== null) {
                return $result;
            }
        }

        // Bazi ucuz hosting GD derlemelerinde gd yuklu olsa da webp encode
        // fonksiyonu bulunmayabilir; bu durumda da orijinal dosyaya dus.
        if (extension_loaded('gd') && function_exists('imagewebp')) {
            return $this->compressWithGd($realPath, $file->getMimeType());
        }

        return null;
    }

    private function compressWithImagick(string $realPath): ?string
    {
        try {
            $image = new \Imagick($realPath);
            $image = $image->coalesceImages() ?: $image;
            $image->autoOrient();

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $longest = max($width, $height);

            if ($longest > self::MAX_DIMENSION) {
                $ratio = self::MAX_DIMENSION / $longest;
                $image->resizeImage(
                    max(1, (int) round($width * $ratio)),
                    max(1, (int) round($height * $ratio)),
                    \Imagick::FILTER_LANCZOS,
                    1
                );
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(self::QUALITY);
            $image->stripImage();

            $data = $image->getImageBlob();
            $image->destroy();

            return $data ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function compressWithGd(string $realPath, ?string $mime): ?string
    {
        $source = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : null,
            default => null,
        };

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $longest = max($width, $height);

        $target = $source;
        $isNewCanvas = false;

        if ($longest > self::MAX_DIMENSION) {
            $ratio = self::MAX_DIMENSION / $longest;
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));

            $target = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $isNewCanvas = true;
        }

        imagepalettetotruecolor($target);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        ob_start();
        $ok = imagewebp($target, null, self::QUALITY);
        $data = ob_get_clean();

        imagedestroy($source);
        if ($isNewCanvas) {
            imagedestroy($target);
        }

        return ($ok && $data) ? $data : null;
    }
}
