<?php

namespace App\Services;

use App\Models\City;
use App\Models\DataImportRow;
use App\Models\District;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityServiceOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DataImportRowApprovalService
{
    public function __construct(private FacilityImportImageService $imageService)
    {
    }

    public function approve(DataImportRow $row, bool $publish = true): Facility
    {
        // 21 Temmuz 2026: diger tum onay akislarinin (FacilityClaimController,
        // FacilityRegistrationController, WalletTopupController) aksine bu
        // metodda kilit/durum guard'i yoktu - telefon/adresi bos bir satirda
        // (isDuplicate hicbir zaman yakalayamaz) cift tiklama veya iki
        // admin'in ayni satiri ayni anda onaylamasi IKI AYRI canli Facility
        // olusturabiliyordu. lockForUpdate + status kontrolu, tum islem tek
        // transaction'da tutularak digerleriyle tutarli hale getirildi.
        //
        // "skipped" durumunu yazan iki dal (mukerrer/kamu-belediye-vakif)
        // BILEREK transaction ICINDE degil - orada yazip sonra exception
        // firlatsaydik, transaction rollback bu yaziyi da geri alirdi ve
        // satir sonsuza dek "pending" gorunurdu. Bunun yerine ozel bir
        // exception ile isaretlenip, rollback TAMAMLANDIKTAN SONRA burada
        // ayri bir sorguyla kalici olarak yaziliyor.
        try {
            return DB::transaction(function () use ($row, $publish) {
                return $this->approveLocked($row, $publish);
            });
        } catch (DataImportRowSkipped $e) {
            $row->update(['status' => 'skipped', 'message' => $e->getMessage()]);
            throw new RuntimeException($e->getMessage());
        }
    }

    private function approveLocked(DataImportRow $row, bool $publish): Facility
    {
        $row = DataImportRow::whereKey($row->id)->lockForUpdate()->firstOrFail();

        if ($row->status === 'approved' || $row->facility_id) {
            throw new RuntimeException('Bu satır zaten onaylanmış.');
        }

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

        if ($this->isDuplicate($item, $city)) {
            throw new DataImportRowSkipped('Benzer kayit var, kurum olusturulmadi.');
        }

        $ownershipType = classify_facility_ownership_type($item['name']);
        if (in_array($ownershipType, ['kamu', 'belediye', 'vakif'], true)) {
            throw new DataImportRowSkipped('Kamu/belediye/vakfa ait kurum, platform kapsami disinda.');
        }

        $districtModel = $this->districtModel($city, $item['district']);
        $description = $item['description'] ?: $this->generatedDescription($item, $category);
        $phoneType = classify_phone_type($item['phone']);

        $facility = Facility::create([
            'name' => $item['name'],
            'slug' => $this->uniqueSlug($item['name']),
            'city_id' => $city->id,
            'district_id' => $districtModel?->id,
            'facility_category_id' => $category->id,
            'ownership_type' => $ownershipType,
            'district' => $districtModel?->name ?? $item['district'],
            'address' => $item['address'],
            'lat' => $this->coordinate($item['lat']),
            'lng' => $this->coordinate($item['lng']),
            'phone' => $item['phone'],
            'phone_type' => $phoneType,
            'invitation_status' => $phoneType === 'mobile' ? 'not_started' : ($phoneType === 'landline' ? 'landline_only' : 'contact_missing'),
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
            $item['name'].' için oluşturulan ön kayıt profilidir.',
            $categoryName.' alanında hizmet verdiği Google Maps verilerinden tespit edilmiştir.',
        ];
        if ($place) {
            $parts[] = 'Adres/bölge bilgisi: '.$place.'.';
        }
        if ($item['phone']) {
            $parts[] = 'Telefon bilgisi admin tarafından doğrulanabilir: '.$item['phone'].'.';
        }
        $parts[] = 'Kurum yetkilisi sahiplenme başvurusu yaptığında profil bilgileri, görseller ve hizmet detayları resmi belge kontrolünden sonra güncellenebilir.';

        return implode(' ', $parts);
    }

    private function services(FacilityCategory $category): array
    {
        $section = service_section_for_scope($category->brand_scope);
        $features = array_slice($section['features'] ?? [], 0, 3);

        return array_values(array_unique(array_filter(array_merge([$category->name], $features))));
    }

    /**
     * "Ayni kurumun birden fazla kaydi asla olmamali" garantisi: telefon
     * sehir genelinde (ilce/kategori farkli olsa da) karsilastirilir.
     * Telefon yoksa isim+adres sehir genelinde (ilce SINIRLAMASI OLMADAN)
     * karsilastirilir — ayni isletme farkli ilce aramalarinda (arama
     * yarıçapı genişleyince kücük ilcelerde de sehir merkezindeki uzman
     * merkezler cikabiliyor) tekrar bulunup farkli district degeriyle
     * kaydedilebiliyordu, bu da eski (sadece ilce-bazli) kontrolden
     * kaciyordu. Adres de eslesme sartina eklendi ki "Zubeyde Hanim
     * Anaokulu" gibi Turkiye'de cok yaygin, farkli ilcelerde gercekten
     * ayri fiziksel kurumlar olan isimler yanlislikla mukerrer sayilmasin
     * — sadece isim VE adres birebir ayniysa mukerrer kabul edilir.
     */
    private function isDuplicate(array $item, City $city): bool
    {
        $normalizedPhone = $this->normalizePhone($item['phone'] ?? '');

        if ($normalizedPhone !== '') {
            $phoneMatch = Facility::where('city_id', $city->id)
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

        return Facility::where('city_id', $city->id)
            ->get(['id', 'name', 'address'])
            ->contains(fn ($f) => $this->normalizeName($f->name) === $normalizedName
                && $this->normalizeAddress($f->address) === $normalizedAddress);
    }

    // 15 Agustos 2026: bkz. DataExtractorImportService::normalizePhone() ayni
    // tarihli yorum - ulke kodu/basindaki sifir farki mukerrer kontrolunu
    // atlatiyordu, classify_phone_type() ile ayni on-ek temizleme uygulandi.
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

    private function normalizeName(?string $name): string
    {
        $ascii = Str::of((string) $name)->lower()->ascii()->toString();
        $clean = preg_replace('/[^a-z0-9]+/', ' ', $ascii);

        return trim(preg_replace('/\s+/', ' ', $clean));
    }

    /**
     * Google Maps'in adres alanini bazen dogru cekemedigi, "Adresi kopyala"
     * (kopyala butonunun etiketi) gibi bir arayuz metnini adres sanip
     * kaydettigi goruldu — bu placeholder'lar mukerrer kontrolunde
     * kullanilirsa farkli isletmeleri yanlislikla ayni adrese sahip
     * gosterip mukerrer sayabilir, bu yuzden bos sayilir.
     */
    private function normalizeAddress(?string $address): string
    {
        $normalized = $this->normalizeName($address);

        return $normalized === 'adresi kopyala' ? '' : $normalized;
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

/**
 * "skipped" durumunu approve() icinde transaction disinda kalici olarak
 * yazabilmek icin kullanilan isaretci exception (bkz. approve() yorumu).
 */
class DataImportRowSkipped extends RuntimeException
{
}
