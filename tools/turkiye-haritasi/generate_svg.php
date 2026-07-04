<?php

/**
 * Turkiye 81 il haritasini gercek GeoJSON sinir verisinden interaktif bir
 * SVG'ye donusturur. Kaynak veri: alpers/Turkey-Maps-GeoJSON (MIT lisansli,
 * bkz. kaynak_lisans.txt). Bu script tek seferlik/elle calistirilan bir
 * build araci — uygulama calisma zamaninda calismaz.
 *
 * Kullanim: php tools/turkiye-haritasi/generate_svg.php
 * Cikti: public/images/turkiye-harita.svg
 */

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Support\Str;

$sourcePath = __DIR__.'/tr-cities.json';
$outputPath = __DIR__.'/../../public/images/turkiye-harita.svg';

if (! is_file($sourcePath)) {
    fwrite(STDERR, "Kaynak dosya bulunamadi: {$sourcePath}\n");
    exit(1);
}

$data = json_decode(file_get_contents($sourcePath), true, flags: JSON_THROW_ON_ERROR);

// GeoJSON kaynagindaki isim, projedeki il config'i ile (config/turkiye.php)
// bire bir eslesmiyorsa burada elle eslenir. Tek bilinen fark: kaynak veri
// "Afyon" kullaniyor, biz "Afyonkarahisar" kullaniyoruz.
$nameOverrides = [
    'Afyon' => 'Afyonkarahisar',
];

// Kaynak veride bazi iller "Polygon" (coordinates: Ring[]), bazilari
// "MultiPolygon" (coordinates: Polygon[] = Ring[][]) olarak geliyor. Ikisini
// de ayni sekilde islemek icin Polygon'u tek elemanli bir MultiPolygon'a
// (Ring[][] -> Ring[][][] degil, sadece bir sarmalayici) donusturuyoruz.
function asMultiPolygon(array $geometry): array
{
    return $geometry['type'] === 'Polygon'
        ? [$geometry['coordinates']]
        : $geometry['coordinates'];
}

// 1) Global bounding box (butun iller birlikte, dogru oranli haritayi kurmak icin)
$minLon = $minLat = INF;
$maxLon = $maxLat = -INF;

foreach ($data['features'] as $feature) {
    foreach (asMultiPolygon($feature['geometry']) as $polygon) {
        foreach ($polygon as $ring) {
            foreach ($ring as [$lon, $lat]) {
                $minLon = min($minLon, $lon);
                $maxLon = max($maxLon, $lon);
                $minLat = min($minLat, $lat);
                $maxLat = max($maxLat, $lat);
            }
        }
    }
}

$midLatRad = deg2rad(($minLat + $maxLat) / 2);
$lonScale = cos($midLatRad); // enlem daraldikca boylam mesafesini duzeltir (basit equirectangular)

$targetHeight = 520.0;
$lonSpan = ($maxLon - $minLon) * $lonScale;
$latSpan = $maxLat - $minLat;
$targetWidth = $targetHeight * ($lonSpan / $latSpan);

function project(float $lon, float $lat, float $minLon, float $minLat, float $maxLon, float $maxLat, float $lonScale, float $targetHeight, float $targetWidth, float $latSpan): array
{
    $lonSpanScaled = ($maxLon - $minLon) * $lonScale ?: 1;
    $x = ($lon - $minLon) * $lonScale / $lonSpanScaled * $targetWidth;
    $y = ($maxLat - $lat) / $latSpan * $targetHeight;

    return [round($x, 2), round($y, 2)];
}

// Kucuk bir min-mesafe basitlestirmesi: SVG path'ini sismeden makul boyutta
// tutmak icin, projeksiyondan sonra birbirine cok yakin ardisik noktalari atar.
function simplifyPoints(array $points, float $minDistance = 1.2): array
{
    if (count($points) < 3) {
        return $points;
    }

    $result = [$points[0]];
    foreach ($points as $point) {
        $last = end($result);
        $dx = $point[0] - $last[0];
        $dy = $point[1] - $last[1];
        if (sqrt($dx * $dx + $dy * $dy) >= $minDistance) {
            $result[] = $point;
        }
    }

    return $result;
}

$paths = [];
$labelPoints = [];

foreach ($data['features'] as $feature) {
    $rawName = $feature['properties']['name'];
    $name = $nameOverrides[$rawName] ?? $rawName;
    $slug = Str::slug($name);

    $subpaths = [];
    $allProjectedPoints = [];

    foreach (asMultiPolygon($feature['geometry']) as $polygon) {
        foreach ($polygon as $ring) {
            $projected = array_map(
                fn ($coord) => project($coord[0], $coord[1], $minLon, $minLat, $maxLon, $maxLat, $lonScale, $targetHeight, $targetWidth, $latSpan),
                $ring
            );
            $projected = simplifyPoints($projected);

            if (count($projected) < 3) {
                continue;
            }

            $allProjectedPoints = array_merge($allProjectedPoints, $projected);
            $d = 'M '.implode(' L ', array_map(fn ($p) => $p[0].','.$p[1], $projected)).' Z';
            $subpaths[] = $d;
        }
    }

    if (! $subpaths) {
        continue;
    }

    $paths[] = [
        'slug' => $slug,
        'name' => $name,
        'd' => implode(' ', $subpaths),
    ];

    // Etiket/nokta konumu icin kaba bir merkez (bbox ortasi yeterli, il
    // isimlerini haritada okunur kucuk noktalarla gostermek icin kullanilir).
    $xs = array_column($allProjectedPoints, 0);
    $ys = array_column($allProjectedPoints, 1);
    $labelPoints[$slug] = [
        'x' => round((min($xs) + max($xs)) / 2, 1),
        'y' => round((min($ys) + max($ys)) / 2, 1),
    ];
}

$width = round($targetWidth, 1);
$height = round($targetHeight, 1);

$svgPaths = '';
foreach ($paths as $p) {
    $svgPaths .= sprintf(
        "  <path class=\"il\" data-il=\"%s\" data-il-adi=\"%s\" d=\"%s\"></path>\n",
        $p['slug'],
        htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'),
        $p['d']
    );
}

$svg = <<<SVG
<svg viewBox="0 0 {$width} {$height}" xmlns="http://www.w3.org/2000/svg" class="turkiye-harita" role="img" aria-label="Turkiye il haritasi">
{$svgPaths}</svg>
SVG;

file_put_contents($outputPath, $svg);

// Etiket koordinatlarini da ayri bir JSON olarak sakla — Blade tarafinda
// il ismi/sayi balonu konumlandirmak icin kullanilabilir (opsiyonel).
file_put_contents(__DIR__.'/../../public/images/turkiye-harita-noktalari.json', json_encode($labelPoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

fwrite(STDOUT, 'Tamamlandi: '.count($paths)." il yazildi -> {$outputPath}\n");
fwrite(STDOUT, "Boyut: {$width} x {$height}\n");
