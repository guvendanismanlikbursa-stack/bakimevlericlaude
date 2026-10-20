<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

/**
 * 20 Ekim 2026: PageSpeed raporu - kurum kartlarındaki görseller gösterildikleri
 * boyutun ~3 katı (960x939) iniyordu. Bu uç, bir görselin kart boyutundaki
 * küçük kopyasını İLK İSTEKTE bir kez üretir, kaydeder ve kalıcı olarak ona
 * yönlendirir. Sonraki sayfalarda facility_thumb() doğrudan hazır kopyayı verir.
 */
class FacilityThumbController extends Controller
{
    private const WIDTHS = [640];

    public function show(int $width, string $path)
    {
        abort_unless(in_array($width, self::WIDTHS, true), 404);
        $thumb = facility_thumb_path($path, $width);
        abort_unless($thumb !== null, 404);

        $source = storage_path('app/public/'.$path);
        $target = storage_path('app/public/'.$thumb);

        if (! is_file($target)) {
            abort_unless(is_file($source), 404);
            if (! $this->createThumb($source, $target, $width)) {
                // Küçültülemezse orijinal görsel gösterilir (kart boş kalmasın).
                return redirect()->to(facility_asset($path), 302);
            }
        }

        return redirect()->to(facility_asset($thumb), 301)->header('Cache-Control', 'public, max-age=31536000');
    }

    private function createThumb(string $source, string $target, int $width): bool
    {
        if (! function_exists('imagewebp')) {
            return false;
        }
        $data = @file_get_contents($source);
        $img = $data !== false ? @imagecreatefromstring($data) : false;
        if (! $img) {
            return false;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $width) {
            $nh = (int) round($h * $width / $w);
            $small = imagecreatetruecolor($width, $nh);
            imagealphablending($small, false);
            imagesavealpha($small, true);
            imagecopyresampled($small, $img, 0, 0, 0, 0, $width, $nh, $w, $h);
            imagedestroy($img);
            $img = $small;
        }

        $dir = dirname($target);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            imagedestroy($img);

            return false;
        }
        $tmp = $target.'.'.getmypid().'.tmp';
        $ok = @imagewebp($img, $tmp, 78);
        imagedestroy($img);
        if (! $ok || ! @rename($tmp, $target)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }
}
