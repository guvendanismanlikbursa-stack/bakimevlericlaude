<?php

namespace App\Services;

use App\Models\City;
use App\Models\DataImportRow;
use App\Models\District;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityServiceOption;
use Illuminate\Support\Str;
use RuntimeException;

class DataImportRowApprovalService
{
    public function __construct(private FacilityImportImageService $imageService)
    {
    }

    public function approve(DataImportRow $row, bool $publish = true): Facility
    {
        $row->loadMissing(['batch.city', 'batch.category']);
        $batch = $row->batch;
        $city = $batch?->city;
        $category = $batch?->category;

        if (! $city || ! $category) {
            throw new RuntimeException('Satirin sehir veya kategori bilgisi eksik.');
        }

        $item = $this->normalize($row->payload ?? []);
        if (! filled($item['name'])) {
            throw new RuntimeException('Kurum adi bos satir onaylanamaz.');
        }

        if ($this->isDuplicate($item, $city, $item['district'] ?: null)) {
            $row->update(['status' => 'skipped', 'message' => 'Benzer kayit var, kurum olusturulmadi.']);
            throw new RuntimeException('Benzer kayit var, kurum olusturulmadi.');
        }

        $districtModel = $this->districtModel($city, $item['district']);
        $description = $item['description'] ?: $this->generatedDescription($item, $category);

        $facility = Facility::create([
            'name' => $item['name'],
            'slug' => $this->uniqueSlug($item['name']),
            'city_id' => $city->id,
            'district_id' => $districtModel?->id,
            'facility_category_id' => $category->id,
            'district' => $districtModel?->name ?? $item['district'],
            'address' => $item['address'],
            'lat' => $this->coordinate($item['lat']),
            'lng' => $this->coordinate($item['lng']),
            'phone' => $item['phone'],
            'email' => $item['email'],
            'rating' => $this->rating($item['rating']),
            'description' => $description,
            'services' => $this->services($category),
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
        $imageCount = $this->imageService->attachRandomImages($facility, $category, 5);

        $row->update([
            'facility_id' => $facility->id,
            'status' => 'approved',
            'message' => 'Onaylandi, kurum olusturuldu. Eklenen gorsel: '.$imageCount,
            'payload' => array_merge($item, ['attached_images' => $imageCount]),
        ]);

        $batch->increment('created_count');

        return $facility;
    }

    public function enrich(DataImportRow $row): DataImportRow
    {
        $row->loadMissing('batch.category');
        $item = $this->normalize($row->payload ?? []);
        $category = $row->batch?->category;
        $item['description'] = $this->generatedDescription($item, $category);
        $item['auto_fill'] = true;
        $item['auto_fill_note'] = 'Otomatik aciklama olusturuldu; onayda kurum tipine gore 5 gorsel eklenecek.';

        $row->update([
            'status' => 'enriched',
            'name' => $item['name'],
            'phone' => $item['phone'],
            'message' => 'Otomatik doldurma hazir.',
            'payload' => $item,
        ]);

        return $row;
    }

    public function normalize(array $payload): array
    {
        return [
            'name' => trim((string) ($payload['name'] ?? $payload['İşyeri Adı'] ?? $payload['Isyeri Adi'] ?? $payload['Isyeri Adı'] ?? $payload['Kurum Adı'] ?? $payload['Kurum Adi'] ?? $payload['Kurum Ad?'] ?? '')),
            'category' => trim((string) ($payload['category'] ?? $payload['Kategori'] ?? '')),
            'address' => trim((string) ($payload['address'] ?? $payload['Adres'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? $payload['Telefon'] ?? '')),
            'email' => trim((string) ($payload['email'] ?? $payload['E-posta'] ?? $payload['Eposta'] ?? '')),
            'rating' => trim((string) ($payload['rating'] ?? $payload['Puan'] ?? '')),
            'district' => trim((string) ($payload['district'] ?? $payload['Ilce'] ?? $payload['İlçe'] ?? '')),
            'description' => trim((string) ($payload['description'] ?? '')),
            'lat' => trim((string) ($payload['lat'] ?? $payload['Enlem'] ?? '')),
            'lng' => trim((string) ($payload['lng'] ?? $payload['Boylam'] ?? '')),
        ];
    }

    private function coordinate(string $value): ?float
    {
        $value = trim(str_replace(',', '.', $value));

        return is_numeric($value) ? (float) $value : null;
    }

    private function generatedDescription(array $item, ?FacilityCategory $category): string
    {
        $categoryName = $category?->name ?: ($item['category'] ?: 'Kurum');
        $place = trim(($item['district'] ? $item['district'].' / ' : '').($item['address'] ?: ''));
        $parts = [
            $item['name'].' icin olusturulan on kayit profilidir.',
            $categoryName.' alaninda hizmet verdigi Google Maps verilerinden tespit edilmistir.',
        ];
        if ($place) {
            $parts[] = 'Adres/bolge bilgisi: '.$place.'.';
        }
        if ($item['phone']) {
            $parts[] = 'Telefon bilgisi admin tarafindan dogrulanabilir: '.$item['phone'].'.';
        }
        $parts[] = 'Kurum yetkilisi sahiplenme basvurusu yaptiginda profil bilgileri, gorseller ve hizmet detaylari resmi belge kontrolunden sonra guncellenebilir.';

        return implode(' ', $parts);
    }

    private function services(FacilityCategory $category): array
    {
        $section = service_section_for_scope($category->brand_scope);
        $features = array_slice($section['features'] ?? [], 0, 3);

        return array_values(array_unique(array_filter(array_merge([$category->name], $features))));
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

    private function districtModel(City $city, ?string $district): ?District
    {
        if (! filled($district)) {
            return null;
        }

        return District::where('city_id', $city->id)->where('slug', Str::slug($district))->first();
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
            'cocuk' => $facility->childDetail()->firstOrCreate(['facility_id' => $facility->id], ['details' => $details + ['odak' => 'cocuk gelisimi, guvenli ortam, veli iletisim sureci']]),
            'rehabilitasyon' => $facility->rehabDetail()->firstOrCreate(['facility_id' => $facility->id], ['details' => $details + ['odak' => 'terapi plani, uzman kadro, takip ve raporlama']]),
            default => $facility->elderlyDetail()->firstOrCreate(['facility_id' => $facility->id], ['details' => $details + ['odak' => 'bakim plani, saglik takibi, sosyal yasam']]),
        };
    }

    private function rating(string $value): float
    {
        $value = str_replace(',', '.', $value);
        preg_match('/\d+(\.\d+)?/', $value, $match);

        return isset($match[0]) ? min(5, (float) $match[0]) : 0.0;
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
}
