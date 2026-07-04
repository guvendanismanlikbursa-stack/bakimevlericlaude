<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacilityArchiveService
{
    public function archiveBeforeDelete(Facility $facility): string
    {
        $facility->loadMissing(['city', 'district', 'category', 'images', 'serviceOptions']);

        $folder = 'silinenler/'.$facility->id.'-'.Str::slug($facility->name ?: 'kurum').'-'.now()->format('Ymd-His');
        Storage::disk('public')->makeDirectory($folder.'/gorseller');

        $images = [];
        foreach ($facility->images as $image) {
            $archivedPath = null;
            if ($image->path && Storage::disk('public')->exists($image->path)) {
                $archivedPath = $folder.'/gorseller/'.basename($image->path);
                Storage::disk('public')->copy($image->path, $archivedPath);
            }

            $images[] = [
                'id' => $image->id,
                'path' => $image->path,
                'archived_path' => $archivedPath,
                'sort_order' => $image->sort_order,
            ];
        }

        $districtModel = $facility->relationLoaded('district') ? $facility->getRelation('district') : null;

        Storage::disk('public')->put($folder.'/kurum.json', json_encode([
            'archived_at' => now()->toISOString(),
            'facility' => $facility->toArray(),
            'city' => $facility->city?->toArray(),
            'district_text' => $facility->getAttribute('district'),
            'district_model' => $districtModel?->toArray(),
            'category' => $facility->category?->toArray(),
            'service_options' => $facility->serviceOptions->toArray(),
            'images' => $images,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $folder;
    }
}
