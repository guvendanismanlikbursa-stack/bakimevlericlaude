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

    // Kurum sahiplerinin/adminin panelden yukledigi GERCEK fotograflar icin:
    // telefon kameralarindan gelen 5-15 MB'lik dosyalar disk alanini hizla
    // tuketebiliyor, bu yuzden bu yol icin daha agresif (maksimum derece)
    // sikistirma uygulanir.
    private const UPLOAD_MAX_DIMENSION = 1280;
    private const UPLOAD_QUALITY = 60;

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
     * Veri cekici/on kayit gorsel havuzundan kopyalanan STOK gorseller icin:
     * gercek kurum fotografi olmadigini acikca belirtmek amaciyla capraz,
     * tekrarlanan "ÖRNEKTİR" filigrani basar, sonra ayni sekilde kucultup
     * WebP'ye cevirir. Watermark metni bos birakilirsa sadece sikistirir.
     */
    public function storeFromLocalFile(string $sourcePath, string $directory, string $disk = 'public', ?string $watermarkText = null, ?string $filename = null): string
    {
        $mime = $this->detectMime($sourcePath);
        $data = null;

        if (extension_loaded('imagick')) {
            $data = $this->processWithImagick($sourcePath, $watermarkText);
        }

        if ($data === null && extension_loaded('gd') && function_exists('imagewebp')) {
            $data = $this->processWithGd($sourcePath, $mime, $watermarkText);
        }

        if ($data === null) {
            // Ne Imagick ne GD var: filigran basilamaz, orijinal dosya oldugu gibi kopyalanir.
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
            $path = trim($directory, '/').'/'.($filename ? "{$filename}.{$ext}" : Str::random(32).'.'.$ext);
            Storage::disk($disk)->put($path, file_get_contents($sourcePath));

            return $path;
        }

        $path = trim($directory, '/').'/'.($filename ? "{$filename}.webp" : Str::random(32).'.webp');
        Storage::disk($disk)->put($path, $data);

        return $path;
    }

    private function detectMime(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => null,
        };
    }

    private function processWithGd(string $realPath, ?string $mime, ?string $watermarkText): ?string
    {
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
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
            imagedestroy($source);
        }

        if ($watermarkText) {
            $this->tileWatermarkGd($target, $watermarkText);
        }

        imagepalettetotruecolor($target);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        ob_start();
        $ok = imagewebp($target, null, self::QUALITY);
        $data = ob_get_clean();
        imagedestroy($target);

        return ($ok && $data) ? $data : null;
    }

    private function tileWatermarkGd($image, string $text): void
    {
        $fontPath = resource_path('fonts/DejaVuSans-Bold.ttf');
        if (! is_file($fontPath)) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $fontSize = max(14, (int) round(min($width, $height) / 14));
        $angle = -28;

        imagealphablending($image, true);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 78);
        $fill = imagecolorallocatealpha($image, 255, 255, 255, 48);

        $stepX = (int) round($fontSize * strlen($text) * 0.62);
        $stepY = (int) round($fontSize * 4.5);

        for ($y = -$height; $y < 2 * $height; $y += $stepY) {
            for ($x = -$width; $x < 2 * $width; $x += $stepX) {
                imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadow, $fontPath, $text);
                imagettftext($image, $fontSize, $angle, $x, $y, $fill, $fontPath, $text);
            }
        }
    }

    private function processWithImagick(string $realPath, ?string $watermarkText): ?string
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

            if ($watermarkText) {
                $this->tileWatermarkImagick($image, $watermarkText);
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

    private function tileWatermarkImagick(\Imagick $image, string $text): void
    {
        $fontPath = resource_path('fonts/DejaVuSans-Bold.ttf');
        if (! is_file($fontPath)) {
            return;
        }

        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $fontSize = max(14, (int) round(min($width, $height) / 14));

        $draw = new \ImagickDraw();
        $draw->setFont($fontPath);
        $draw->setFontSize($fontSize);
        $draw->setFillColor(new \ImagickPixel('rgba(255,255,255,0.38)'));
        $draw->setStrokeColor(new \ImagickPixel('rgba(0,0,0,0.30)'));
        $draw->setStrokeWidth(1);

        $stepX = (int) round($fontSize * strlen($text) * 0.62);
        $stepY = (int) round($fontSize * 4.5);

        for ($y = 0; $y < $height + $stepY; $y += $stepY) {
            for ($x = -$width; $x < 2 * $width; $x += $stepX) {
                $image->annotateImage($draw, $x, $y, -28, $text);
            }
        }
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

            if ($longest > self::UPLOAD_MAX_DIMENSION) {
                $ratio = self::UPLOAD_MAX_DIMENSION / $longest;
                $image->resizeImage(
                    max(1, (int) round($width * $ratio)),
                    max(1, (int) round($height * $ratio)),
                    \Imagick::FILTER_LANCZOS,
                    1
                );
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(self::UPLOAD_QUALITY);
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

        if ($longest > self::UPLOAD_MAX_DIMENSION) {
            $ratio = self::UPLOAD_MAX_DIMENSION / $longest;
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
        $ok = imagewebp($target, null, self::UPLOAD_QUALITY);
        $data = ob_get_clean();

        imagedestroy($source);
        if ($isNewCanvas) {
            imagedestroy($target);
        }

        return ($ok && $data) ? $data : null;
    }
}
