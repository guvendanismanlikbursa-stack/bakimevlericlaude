<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\DataImportBatch;
use App\Models\FacilityCategory;
use App\Services\DataImportRowApprovalService;
use Illuminate\Console\Command;

/**
 * tools/veri-cekici/google_maps_scraper.py'nin ciktisini (harici bir
 * Python script'i tarafindan toplanmis {ilce: [...], kategori_id: ...}
 * seklinde JSON) inceleme kuyruguna (data_import_rows, pending_review)
 * alir. Google Maps'ten canli cekme yapmaz, sadece onceden toplanmis
 * sonuclari veritabanina aktarir.
 */
class ImportScrapedFacilities extends Command
{
    protected $signature = 'veri-cekici:import-json
        {path : JSON dosyasinin tam yolu}
        {city : Sehir slug (orn. istanbul)}
        {--admin-id= : Islemi yapan admin ID (opsiyonel)}';

    protected $description = 'Harici Python scraper ciktisini (JSON) data_import_rows inceleme kuyruguna aktarir';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("Dosya bulunamadi: {$path}");

            return self::FAILURE;
        }

        $city = City::where('slug', $this->argument('city'))->first();
        if (! $city) {
            $this->error('Sehir bulunamadi: '.$this->argument('city'));

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $adminId = $this->option('admin-id');
        $rowService = app(DataImportRowApprovalService::class);

        $totalRows = 0;
        $batchesCreated = 0;

        foreach ($data as $key => $entries) {
            if (! $entries) {
                continue;
            }

            [$district, $categoryId] = explode('|', $key, 2);
            $category = FacilityCategory::find((int) $categoryId);
            if (! $category) {
                $this->warn("Kategori bulunamadi (id={$categoryId}), atlaniyor: {$key}");

                continue;
            }

            $batch = DataImportBatch::create([
                'source' => 'google_maps_veri_cekici_auto',
                'admin_id' => $adminId,
                'city_id' => $city->id,
                'facility_category_id' => $category->id,
                'file_name' => "otomatik (istanbul toplu): {$district} / {$category->name}",
                'total_rows' => count($entries),
                'status' => 'pending_review',
                'meta' => ['district' => $district, 'source_file' => basename($path)],
            ]);
            $batchesCreated++;

            foreach ($entries as $index => $payload) {
                $payload = is_array($payload) ? $payload : [];
                $payload['district'] = $district;
                $item = $rowService->normalize($payload);

                $batch->rows()->create([
                    'row_number' => $index + 1,
                    'status' => filled($item['name']) ? 'pending_review' : 'skipped',
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                    'message' => filled($item['name']) ? 'Onay bekliyor.' : 'Kurum adi bos.',
                    'payload' => $item,
                ]);
                $totalRows++;
            }
        }

        $this->info("{$batchesCreated} batch, {$totalRows} satir inceleme kuyruguna alindi.");

        return self::SUCCESS;
    }
}
