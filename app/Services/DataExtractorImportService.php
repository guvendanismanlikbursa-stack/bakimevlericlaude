<?php

namespace App\Services;

use App\Models\City;
use App\Models\DataImportBatch;
use App\Models\District;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityServiceOption;
use Illuminate\Support\Str;

class DataExtractorImportService
{
    public function __construct(private FacilityImportImageService $imageService)
    {
    }

    private const HEADER_ALIASES = [
        'name' => ['isyeri adi', 'is yeri adi', 'kurum adi', 'name'],
        'category' => ['kategori', 'category'],
        'address' => ['adres', 'address'],
        'phone' => ['telefon', 'phone'],
        'email' => ['e-posta', 'eposta', 'email'],
        'rating' => ['puan', 'rating'],
        'lat' => ['enlem', 'lat', 'latitude'],
        'lng' => ['boylam', 'lng', 'longitude'],
    ];

    public function import(string $path, City $city, FacilityCategory $category, ?string $district, bool $publish, ?int $adminId = null, ?string $fileName = null): array
    {
        $rows = app(SimpleXlsxReader::class)->rows($path);
        $districtModel = $this->districtModel($city, $district);
        $batch = DataImportBatch::create([
            'source' => 'google_maps_veri_cekici',
            'admin_id' => $adminId,
            'city_id' => $city->id,
            'district_id' => $districtModel?->id,
            'facility_category_id' => $category->id,
            'file_name' => $fileName,
            'total_rows' => max(0, count($rows) - 1),
            'status' => 'running',
        ]);

        if (count($rows) < 2) {
            $batch->update(['status' => 'failed', 'error_count' => 1]);
            return ['created' => 0, 'skipped' => 0, 'errors' => ['Excel dosyasinda veri satiri yok.'], 'batch_id' => $batch->id];
        }

        $headers = array_shift($rows);
        $map = $this->mapHeaders($headers);
        if (! isset($map['name'])) {
            $batch->update(['status' => 'failed', 'error_count' => 1]);
            return ['created' => 0, 'skipped' => 0, 'errors' => ['Kurum adi kolonu bulunamadi.'], 'batch_id' => $batch->id];
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $line => $values) {
            $item = $this->itemFromRow($values, $map);
            if (! filled($item['name'])) {
                $batch->rows()->create(['row_number' => $line + 2, 'status' => 'skipped', 'message' => 'Kurum adi bos', 'payload' => $item]);
                $skipped++;
                continue;
            }

            if ($this->isDuplicate($item, $city, $district)) {
                $batch->rows()->create(['row_number' => $line + 2, 'status' => 'skipped', 'name' => $item['name'], 'phone' => $item['phone'], 'message' => 'Benzer kayit var', 'payload' => $item]);
                $skipped++;
                continue;
            }

            try {
                $facility = Facility::create([
                    'name' => $item['name'],
                    'slug' => $this->uniqueSlug($item['name']),
                    'city_id' => $city->id,
                    'district_id' => $districtModel?->id,
                    'facility_category_id' => $category->id,
                    'district' => $districtModel?->name ?? $district,
                    'address' => $item['address'],
                    'lat' => $this->coordinate($item['lat']),
                    'lng' => $this->coordinate($item['lng']),
                    'phone' => $item['phone'],
                    'email' => $item['email'],
                    'rating' => $this->rating($item['rating']),
                    'description' => $this->description($item, $category),
                    'services' => [$category->name],
                    'is_published' => $publish,
                    'is_featured' => false,
                    'is_claimed' => false,
                    'free_quote_credits' => 0,
                    'balance' => 0,
                    'source' => 'google_maps_veri_cekici',
                    'source_payload' => $item,
                ]);

                $this->syncServiceOption($facility, $category);
                $this->createSectionDetail($facility, $category, $item);
                $imageCount = $this->imageService->attachRandomImages($facility, $category);

                $batch->rows()->create([
                    'facility_id' => $facility->id,
                    'row_number' => $line + 2,
                    'status' => 'created',
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                    'payload' => array_merge($item, ['attached_images' => $imageCount]),
                ]);
                $created++;
            } catch (\Throwable $e) {
                $message = ($line + 2).'. satir: '.$e->getMessage();
                $errors[] = $message;
                $batch->rows()->create([
                    'row_number' => $line + 2,
                    'status' => 'error',
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                    'message' => $message,
                    'payload' => $item,
                ]);
            }
        }

        $batch->update([
            'created_count' => $created,
            'skipped_count' => $skipped,
            'error_count' => count($errors),
            'status' => count($errors) ? 'completed_with_errors' : 'completed',
        ]);

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors, 'batch_id' => $batch->id];
    }

    private function mapHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $normalized[$this->norm($header)] = $index;
        }

        $map = [];
        foreach (self::HEADER_ALIASES as $key => $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($this->norm($alias), $normalized)) {
                    $map[$key] = $normalized[$this->norm($alias)];
                    break;
                }
            }
        }

        return $map;
    }

    private function itemFromRow(array $values, array $map): array
    {
        $item = [];
        foreach (array_keys(self::HEADER_ALIASES) as $key) {
            $item[$key] = isset($map[$key]) ? trim((string) ($values[$map[$key]] ?? '')) : '';
        }

        return $item;
    }

    private function isDuplicate(array $item, City $city, ?string $district): bool
    {
        return Facility::query()
            ->where('city_id', $city->id)
            ->where(function ($query) use ($item, $district) {
                if (filled($item['phone'])) {
                    $query->orWhere('phone', $item['phone']);
                }
                $query->orWhere(function ($q) use ($item, $district) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($item['name'])])
                        ->where('district', $district);
                });
            })
            ->exists();
    }

    private function createSectionDetail(Facility $facility, FacilityCategory $category, array $item): void
    {
        $section = service_section_for_scope($category->brand_scope);
        $details = [
            'on_kayit' => true,
            'kaynak' => 'google_maps_veri_cekici',
            'cekilen_kategori' => $item['category'] ?? null,
            'puan' => $item['rating'] ?? null,
            'admin_notu' => 'On kayit verisi admin tarafindan dogrulanip zenginlestirilmelidir.',
        ];

        match ($section['slug'] ?? null) {
            'cocuk' => $facility->childDetail()->firstOrCreate(['facility_id' => $facility->id], ['details' => $details + [
                'odak' => 'cocuk gelisimi, guvenli ortam, veli iletisim sureci',
            ]]),
            'rehabilitasyon' => $facility->rehabDetail()->firstOrCreate(['facility_id' => $facility->id], ['details' => $details + [
                'odak' => 'terapi plani, uzman kadro, takip ve raporlama',
            ]]),
            default => $facility->elderlyDetail()->firstOrCreate(['facility_id' => $facility->id], ['details' => $details + [
                'odak' => 'bakim plani, saglik takibi, sosyal yasam',
            ]]),
        };
    }

    private function districtModel(City $city, ?string $district): ?District
    {
        if (! filled($district)) {
            return null;
        }

        return District::where('city_id', $city->id)
            ->where('slug', Str::slug($district))
            ->first();
    }

    private function syncServiceOption(Facility $facility, FacilityCategory $category): void
    {
        $section = service_section_for_scope($category->brand_scope);
        $option = FacilityServiceOption::where('section_slug', $section['slug'] ?? '')
            ->where('slug', Str::slug($category->name))
            ->first();

        if (! $option && $section) {
            $option = FacilityServiceOption::firstOrCreate([
                'section_slug' => $section['slug'],
                'slug' => Str::slug($category->name),
            ], ['name' => $category->name]);
        }

        if ($option) {
            $facility->serviceOptions()->syncWithoutDetaching([$option->id]);
        }
    }

    private function rating(string $value): float
    {
        $value = str_replace(',', '.', $value);
        preg_match('/\d+(\.\d+)?/', $value, $match);

        return isset($match[0]) ? min(5, (float) $match[0]) : 0.0;
    }

    private function coordinate(string $value): ?float
    {
        $value = trim(str_replace(',', '.', $value));

        return is_numeric($value) ? (float) $value : null;
    }

    private function description(array $item, FacilityCategory $category): string
    {
        $parts = [
            $category->name.' icin Google Maps veri cekici ile on kayit olarak eklenmistir.',
        ];
        if (filled($item['email'])) {
            $parts[] = 'E-posta: '.$item['email'];
        }

        return implode("\n", $parts);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kurum';
        $slug = $base;
        $i = 1;
        while (Facility::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function norm($value): string
    {
        return Str::of((string) $value)->lower()->ascii()->trim()->toString();
    }
}
