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
    public function __construct(private FacilityImportImageService $imageService, private GeocodingService $geocodingService)
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
        // 20 Eylul 2026: kullanicinin talebi - veri cekici artik yeni kurum
        // tararken ayni sayfadan Google yorumlarini da cekebiliyor (bkz.
        // google_maps_scraper.py). Yorumlar facility_id olusmadan once
        // (import ANINDA henuz yok) ayri bir JSON'da toplandigi icin, bu
        // linki facilities.google_maps_url'e kaydediyoruz ki import SONRASI
        // yorumlar bu link ile eslestirilip ice aktarilabilsin (bkz.
        // OpsController::veriCekiciImportGoogleReviewsByMapsUrl).
        'maps_url' => ['google haritalar linki', 'maps url', 'google maps url', 'harita linki'],
    ];

    /**
     * 14 Ekim 2026: kullanıcının talebi - tek bir dosyada BİRDEN FAZLA
     * ilçeye yayılmış taranmış veri (ör. tüm Bursa'daki bireysel
     * fizyoterapistler) içe aktarılması gerekti. Bu metod önceden SADECE
     * admin "Veri Çekici" ekranındaki tek-ilçe akışı için $district
     * parametresiyle TEK bir ilçeyi tüm satırlara uyguluyordu. $district
     * artık null geçilebilir - o zaman HER SATIRIN KENDİ ilçesi, adres
     * alanından (expertImportFile() ile AYNI kanıtlanmış regex deseni)
     * otomatik çıkarılır. Mevcut çağrılar (her zaman dolu $district
     * geçiyor) davranış olarak HİÇ etkilenmez - bu satır içi, ek bir dal.
     */
    public function import(string $path, City $city, FacilityCategory $category, ?string $district, bool $publish, ?int $adminId = null, ?string $fileName = null, ?string $neighborhood = null): array
    {
        $rows = app(SimpleXlsxReader::class)->rows($path);
        $perRowDistrict = ! filled($district);
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

            if ($this->isDuplicate($item, $city)) {
                $batch->rows()->create(['row_number' => $line + 2, 'status' => 'skipped', 'name' => $item['name'], 'phone' => $item['phone'], 'message' => 'Benzer kayit var', 'payload' => $item]);
                $skipped++;
                continue;
            }

            $ownershipType = classify_facility_ownership_type($item['name']);
            if (in_array($ownershipType, ['kamu', 'belediye', 'vakif'], true)) {
                $batch->rows()->create(['row_number' => $line + 2, 'status' => 'skipped', 'name' => $item['name'], 'phone' => $item['phone'], 'message' => 'Kamu/belediye/vakfa ait kurum, platform kapsami disinda', 'payload' => $item]);
                $skipped++;
                continue;
            }

            $phoneType = classify_phone_type($item['phone']);

            // 14 Ekim 2026: $district null geçildiyse (bkz. yukarıdaki
            // $perRowDistrict yorumu) bu satırın ilçesi KENDİ adresinden
            // çıkarılır - tüm dosyaya tek bir ilçe dayatılmaz.
            if ($perRowDistrict) {
                $rowDistrictName = $this->addressDistrict($item['address'] ?? '');
                $rowDistrictModel = $this->districtModel($city, $rowDistrictName ?: null);
            } else {
                $rowDistrictName = $district;
                $rowDistrictModel = $districtModel;
            }

            // 10 Eylul 2026: bkz. DataImportRowApprovalService ayni tarihli
            // yorum - mahalle verilmisse adresin basina eklenir.
            $address = $item['address'];
            if (filled($neighborhood) && stripos($address, $neighborhood) === false) {
                $address = trim($neighborhood.' Mahallesi'.($address !== '' ? ', '.$address : ''));
            }

            // 17 Agustos 2026: bkz. DataImportRowApprovalService ayni tarihli
            // yorum - bu dogrudan-yayinlama yolu (xlsx yukleme, onay bekletmez)
            // icin de ayni otomatik geocoding fallback'i uygulanir.
            $lat = $this->coordinate($item['lat']);
            $lng = $this->coordinate($item['lng']);
            if ($lat === null && $lng === null && filled($address)) {
                $coords = $this->geocodingService->geocodeAddress($address, $rowDistrictModel?->name ?? $rowDistrictName, $city->name);
                if ($coords) {
                    $lat = $coords['lat'];
                    $lng = $coords['lng'];
                }
            }

            try {
                $facility = Facility::create([
                    'name' => $item['name'],
                    'slug' => $this->uniqueSlug($item['name']),
                    'city_id' => $city->id,
                    'district_id' => $rowDistrictModel?->id,
                    'facility_category_id' => $category->id,
                    'ownership_type' => $ownershipType,
                    'district' => $rowDistrictModel?->name ?? $rowDistrictName,
                    'address' => $address,
                    'lat' => $lat,
                    'lng' => $lng,
                    'phone' => $item['phone'],
                    'phone_type' => $phoneType,
                    'invitation_status' => $phoneType === 'mobile' ? 'not_started' : ($phoneType === 'landline' ? 'landline_only' : 'contact_missing'),
                    'email' => $item['email'],
                    'rating' => $this->rating($item['rating']),
                    'google_maps_url' => filled($item['maps_url'] ?? null) ? $item['maps_url'] : null,
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
                // 1 Eylul 2026: kullanicinin talebi - bkz. FacilityImportImageService::
                // attachDefaultMenuImage() yorumu, Turkiye geneli her yeni kurumda.
                $this->imageService->attachDefaultMenuImage($facility);

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

    /**
     * "Ayni kurumun birden fazla kaydi asla olmamali" garantisi: telefon
     * sehir genelinde karsilastirilir. Telefon yoksa isim+adres sehir
     * genelinde (ilce sinirlamasi olmadan) karsilastirilir — ayni isletme
     * farkli ilce aramalarinda tekrar bulunup farkli district degeriyle
     * kaydedilebiliyordu. Adres de sarta eklendi ki Turkiye'de cok yaygin
     * ayni-isimli ama farkli fiziksel kurumlar (orn. "Zubeyde Hanim
     * Anaokulu") yanlislikla mukerrer sayilmasin.
     */
    private function isDuplicate(array $item, City $city): bool
    {
        $normalizedPhone = $this->normalizePhone($item['phone'] ?? '');

        // 16 Ekim 2026: kullanicinin talebi - admin tarafindan silinmis
        // (soft-delete) kurumlar "zaten islenmis, bir daha aktarilmayacak"
        // sayilmali. withTrashed() olmadan Eloquent'in varsayilan global
        // scope'u silinenleri gormezden geliyordu, bu yuzden ayni veri
        // dosyasi tekrar calistirilirsa bilerek silinen kayitlar (ör.
        // alakasiz/yanlis kategoriye dusmus kurumlar) GERI OLUSTURULUYORDU.
        if ($normalizedPhone !== '') {
            $phoneMatch = Facility::withTrashed()->where('city_id', $city->id)
                ->whereNotNull('phone')
                ->get(['id', 'phone'])
                ->contains(fn ($f) => $this->normalizePhone($f->phone) === $normalizedPhone);

            if ($phoneMatch) {
                return true;
            }
        }

        $normalizedName = $this->normalizeName($item['name'] ?? '');
        $normalizedAddress = $this->normalizeAddress($item['address'] ?? '');
        if ($normalizedName === '' || $normalizedAddress === '') {
            return false;
        }

        // 6 Ekim 2026: kullanicinin bulgusu - ayni gercek kurum, basina/sonuna
        // farkli bir mahalle/semt metni eklenerek (ör. "Istiklal, Fatih Cd.
        // No:146" vs "Agaköy Mahallesi, Istiklal, Fatih Cd. No:146" - AYNI
        // bina, sadece biri mahalle on ekiyle) farkli taramalarda iki/uc kez
        // kaydedilebiliyordu - TAM esitlik sarti bunu yakalamiyordu. Artik
        // isim AYNI VE adreslerden biri digerinin icinde geciyorsa (substring)
        // da mukerrer sayilir - cadde/sokak/kapi no kismi sabit kaldigi surece
        // sadece mahalle on eki degisimini yakalar, farkli isimli kurumlari
        // (ayri sart - normalizedName hala tam esit olmali) etkilemez.
        return Facility::withTrashed()->where('city_id', $city->id)
            ->get(['id', 'name', 'address'])
            ->contains(function ($f) use ($normalizedName, $normalizedAddress) {
                if ($this->normalizeName($f->name) !== $normalizedName) {
                    return false;
                }
                $existingAddress = $this->normalizeAddress($f->address);
                if ($existingAddress === '') {
                    return false;
                }

                return $existingAddress === $normalizedAddress
                    || str_contains($existingAddress, $normalizedAddress)
                    || str_contains($normalizedAddress, $existingAddress);
            });
    }

    // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
    // yapilan denetimde bulundu - eskiden sadece rakam disini siliyordu,
    // ulke kodu/basindaki sifir farkini hesaba katmiyordu. "0532 123 45 67"
    // ile "+90 532 123 45 67" AYNI numara oldugu halde farkli normalize
    // sonucu uretip mukerrer kontrolunu atlatiyordu - ayni kurum Google
    // Maps'ten farkli aramalarda farkli telefon formatiyla cekilirse iki
    // kez import edilebiliyordu. classify_phone_type() helper'indaki AYNI
    // on-ek temizleme mantigi burada da uygulandi.
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * "Adresi kopyala" gibi Google Maps arayuz metinlerinin adres sanilip
     * kaydedildigi durumlarda mukerrer kontrolunu yanlis yonlendirmemesi
     * icin bu placeholder bos sayilir.
     */
    private function normalizeAddress(?string $address): string
    {
        $normalized = $this->normalizeName($address);

        return $normalized === 'adresi kopyala' ? '' : $normalized;
    }

    private function normalizeName(?string $name): string
    {
        $ascii = Str::of((string) $name)->lower()->ascii()->toString();
        $clean = preg_replace('/[^a-z0-9]+/', ' ', $ascii);

        return trim(preg_replace('/\s+/', ' ', $clean));
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

    /**
     * 14 Ekim 2026: expertImportFile() (OpsController) içindeki KANITLANMIŞ
     * aynı regex - "...16500 Mustafakemalpaşa/Bursa" gibi bir adresin
     * SONUNDAKİ "ilçe/il" kalıbından ilçeyi çıkarır. Bulunamazsa boş döner
     * (uydurma yapılmaz, o satırın ilçesi/district_id'si null kalır).
     */
    private function addressDistrict(string $address): string
    {
        if (preg_match('/\d{4,5}\s+([^,\/\d]+)\/[^\/,]+\s*$/u', $address, $m)) {
            return trim($m[1]);
        }

        return '';
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
            $category->name.' alanında hizmet verdiği Google Maps verilerinden tespit edilmiştir.',
        ];
        if (filled($item['email'])) {
            $parts[] = 'E-posta: '.$item['email'];
        }
        // 7 Ekim 2026: kullanicinin acik talebi - henuz yorumu olmayan (bu
        // asamada, ice aktarilir aktarilmaz) kurumlarda da ucret/bilgi
        // talep formuna yonlendirme MUTLAKA bulunmali. ReviewBasedDescriptionGenerator
        // daha sonra GERCEK yorum gelince bu aciklamayi zengin bir metinle
        // (kendi CTA'siyla birlikte) DEGISTIRECEK - bu sadece o ana kadarki
        // bosluk icin.
        $parts[] = 'Güncel fiyat bilgisi ve uygunluk durumu için aşağıdaki formu doldurarak bu kurumdan bilgi talep edebilirsiniz.';

        return implode("\n", $parts);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kurum';
        $slug = $base;
        $i = 1;
        while (Facility::withTrashed()->where('slug', $slug)->exists()) {
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
