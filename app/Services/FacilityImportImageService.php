<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacilityImportImageService
{
    private const MAX_IMAGES = 10;

    // Bu havuzdaki gorseller gercek kurum fotografi degil, stok/ornek
    // gorsellerdir (bkz. on kayitli kurumlarin dogasi geregi). Yanlislikla
    // "gercek fotograf" sanilmasin diye her kopyaya bu filigran basilir.
    private const WATERMARK_TEXT = 'ÖRNEKTİR';

    // Her kurum turu (FacilityCategory) icin SABIT, PAYLASILAN 5 ornek
    // gorsel: her on kayitli kurum ayni dosyalari referans eder, kurum
    // basina yeniden uretilip diskte binlerce kopya birikmez (bkz.
    // storage/app/public/facilities/demo/{category_id}/).
    private const DEMO_DIRECTORY = 'facilities/demo';

    public function __construct(private ImageCompressionService $imageCompressor)
    {
    }

    private const SECTION_DIRECTORIES = [
        'yasli-bakim' => ['yasli', 'yaşlı', 'bakim', 'bakım'],
        'cocuk' => ['cocuk', 'çocuk', 'kres', 'kreş', 'anaokul'],
        'rehabilitasyon' => ['rehabilitasyon', 'rehab'],
    ];

    public function attachRandomImages(Facility $facility, FacilityCategory $category, ?int $count = null): int
    {
        $count = min(self::MAX_IMAGES, max(0, $count ?? (int) config('platform.import_image_count', 5)));
        $remaining = self::MAX_IMAGES - $facility->images()->count();
        if ($count < 1 || $remaining < 1) {
            return 0;
        }

        $demoPaths = $this->demoImagesFor($category);
        if ($demoPaths === []) {
            return 0;
        }

        $selected = array_slice($demoPaths, 0, min($count, $remaining, count($demoPaths)));
        $start = $facility->images()->count();
        $attached = 0;

        foreach ($selected as $index => $path) {
            FacilityImage::create([
                'facility_id' => $facility->id,
                'path' => $path,
                'sort_order' => $start + $index,
            ]);
            $attached++;
        }

        return $attached;
    }

    /**
     * Bu kategori icin sabit paylasilan gorsel havuzunun disk yollarini
     * dondurur; henuz yoksa (ilk cagrida) stok havuzundan secip bir kere
     * uretir ve kalici olarak DEMO_DIRECTORY altina yazar. Backfill/temizlik
     * scriptleri de bu havuzu almak icin bu metodu kullanir.
     */
    public function demoImagesFor(FacilityCategory $category): array
    {
        $disk = 'public';
        $directory = self::DEMO_DIRECTORY.'/'.$category->id;
        $needed = (int) config('platform.import_image_count', 5);

        $existing = collect(Storage::disk($disk)->files($directory))->sort()->values();
        if ($existing->count() >= $needed) {
            return $existing->take($needed)->all();
        }

        $pool = $this->imagePool($this->sectionSlug($category));
        if ($pool === []) {
            return $existing->all();
        }

        shuffle($pool);
        $missing = $needed - $existing->count();
        $created = [];

        for ($i = 0; $i < $missing && $i < count($pool); $i++) {
            $filename = (string) ($existing->count() + $i + 1);
            $created[] = $this->imageCompressor->storeFromLocalFile(
                $pool[$i]->getPathname(),
                $directory,
                $disk,
                self::WATERMARK_TEXT,
                $filename
            );
        }

        return $existing->concat($created)->all();
    }

    private function imagePool(string $sectionSlug): array
    {
        $basePath = (string) config('platform.import_image_pool_path');
        if (! is_dir($basePath)) {
            return [];
        }

        $directories = collect(File::directories($basePath));
        $matched = $directories->filter(function (string $directory) use ($sectionSlug) {
            $name = Str::of(basename($directory))->lower()->ascii()->toString();
            foreach (self::SECTION_DIRECTORIES[$sectionSlug] ?? [] as $keyword) {
                if (str_contains($name, Str::ascii($keyword))) {
                    return true;
                }
            }

            return false;
        })->values()->all();

        $searchDirectories = $matched ?: $directories->all();
        $files = [];
        foreach ($searchDirectories as $directory) {
            // allFiles: gorseller alt klasorlere (orn. "bakimevi/1/", "bakimevi/2/")
            // dagitilmis olabilir, tek seviye File::files() bunlari atlardi.
            foreach (File::allFiles($directory) as $file) {
                if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function sectionSlug(FacilityCategory $category): string
    {
        $section = service_section_for_scope($category->brand_scope);

        return $section['slug'] ?? 'yasli-bakim';
    }
}
