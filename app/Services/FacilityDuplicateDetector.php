<?php

namespace App\Services;

use App\Models\City;
use App\Models\Facility;
use Illuminate\Support\Str;

/**
 * 19 Agustos 2026: kullanicinin talebi - "kurum kendi kendine kayit olursa
 * mukerrer kayit riski". Daha once bu eslestirme mantigi sadece Veri Cekici
 * (DataImportRowApprovalService) icin vardi - kurumun KENDI yaptigi
 * "Kurum Kaydi" basvurusunda (Public\FacilityRegistrationController) HIC
 * yoktu. Ayni kurum icin iki ayri kayit (biri on-kayitli, biri yeni
 * kendi-kaydi) olusabiliyordu - yorumlar/goruntulenme/gecmis talepler
 * ikiye bolunuyordu. Bu servis DataImportRowApprovalService::isDuplicate()
 * ile AYNI (halihazirda "Zubeyde Hanim Anaokulu" gibi yanlis pozitiflere
 * karsi ayarlanmis) eslestirme mantigini tek yerde toplar, ikisi de burayi
 * kullanir.
 */
class FacilityDuplicateDetector
{
    public function findDuplicate(?string $phone, ?string $name, ?string $address, City $city): ?Facility
    {
        $normalizedPhone = $this->normalizePhone($phone ?? '');

        if ($normalizedPhone !== '') {
            $phoneMatch = Facility::where('city_id', $city->id)
                ->whereNotNull('phone')
                ->get(['id', 'phone', 'name', 'slug', 'is_claimed'])
                ->first(fn ($f) => $this->normalizePhone($f->phone) === $normalizedPhone);

            if ($phoneMatch) {
                return $phoneMatch;
            }
        }

        $normalizedName = $this->normalizeName($name ?? '');
        $normalizedAddress = $this->normalizeAddress($address ?? '');
        if ($normalizedName === '' || $normalizedAddress === '') {
            return null;
        }

        return Facility::where('city_id', $city->id)
            ->get(['id', 'name', 'address', 'slug', 'is_claimed'])
            ->first(fn ($f) => $this->normalizeName($f->name) === $normalizedName
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
}
