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

        $pool = $this->imagePool($this->sectionSlug($category));
        if ($pool === []) {
            return 0;
        }

        shuffle($pool);
        $selected = array_slice($pool, 0, min($count, $remaining, count($pool)));
        $start = $facility->images()->count();
        $attached = 0;

        foreach ($selected as $index => $source) {
            $extension = strtolower($source->getExtension()) ?: 'jpg';
            $filename = $facility->id.'-'.Str::slug(pathinfo($source->getFilename(), PATHINFO_FILENAME)).'-'.Str::random(6).'.'.$extension;
            $path = 'facilities/imported/'.$filename;

            Storage::disk('public')->put($path, File::get($source->getPathname()));
            FacilityImage::create([
                'facility_id' => $facility->id,
                'path' => $path,
                'sort_order' => $start + $index,
            ]);
            $attached++;
        }

        return $attached;
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
            foreach (File::files($directory) as $file) {
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
