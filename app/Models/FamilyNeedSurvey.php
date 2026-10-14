<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyNeedSurvey extends Model
{
    protected $fillable = [
        'name', 'phone', 'city_id', 'district', 'neighborhood',
        'age_group', 'service_type', 'urgency', 'ownership_preference',
        'created_by_admin_id',
    ];

    public const AGE_GROUPS = [
        '0_2' => '0–2 yaş', '3_5' => '3–5 yaş', '6_9' => '6–9 yaş', '10_14' => '10–14 yaş', '15_18' => '15–18 yaş',
    ];

    public const SERVICE_TYPES = [
        'kres_anaokulu' => 'Kreş / Anaokulu',
        'ozel_egitim_rehabilitasyon' => 'Özel Eğitim ve Rehabilitasyon',
        'ihtiyac_yok' => 'Şu anda bir ihtiyacımız yok',
    ];

    public const URGENCIES = [
        '0_3_ay' => '0–3 ay içinde', '3_6_ay' => '3–6 ay içinde', '6_12_ay' => '6–12 ay içinde', 'ihtiyac_yok' => 'Şu an ihtiyacım yok',
    ];

    public const OWNERSHIP_PREFERENCES = [
        'ozel' => 'Özel', 'kamu' => 'Kamu / Belediye',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function sends()
    {
        return $this->hasMany(FamilyNeedSurveySend::class);
    }

    /**
     * 12 Ekim 2026: bu anketin ihtiyac turune karsilik gelen gercek
     * facility_categories.brand_scope degerleri - config/brands.php
     * service_sections ile AYNI scope sozlugu kullanilir.
     */
    public function categoryScopes(): array
    {
        return match ($this->service_type) {
            'kres_anaokulu' => ['cocuk-bakim'],
            'ozel_egitim_rehabilitasyon' => ['ozel-egitim', 'rehabilitasyon', 'fizik-tedavi'],
            default => [],
        };
    }

    /**
     * 12 Ekim 2026: ankete ve konuma uygun, en fazla 10 kurumu dondurur -
     * mahalle eslesenler once, sonra ayni ilce, sonra ayni il. Kullanicinin
     * talebi: "en uygun maksimum 10 kurum listelensin fazlasina ihtiyac yok".
     */
    public function matchingFacilities()
    {
        $scopes = $this->categoryScopes();
        if (empty($scopes)) {
            return collect();
        }

        $query = Facility::query()
            ->whereNull('deleted_at')
            ->where('name', 'not like', 'QATEST%')
            ->where('city_id', $this->city_id)
            ->whereHas('category', fn ($q) => $q->whereIn('brand_scope', $scopes))
            ->with(['city', 'category']);

        if ($this->ownership_preference === 'ozel') {
            $query->where(function ($q) {
                $q->whereNull('ownership_type')->orWhere('ownership_type', 'ozel');
            });
        } elseif ($this->ownership_preference === 'kamu') {
            $query->whereIn('ownership_type', ['kamu', 'belediye']);
        }

        $neighborhood = $this->neighborhood;
        $district = $this->district;

        return $query->get()
            ->sortByDesc(function ($f) use ($neighborhood, $district) {
                $score = 0;
                if ($neighborhood && $f->neighborhood && str_contains(mb_strtolower($f->neighborhood), mb_strtolower($neighborhood))) {
                    $score += 2;
                }
                if ($district && $f->district === $district) {
                    $score += 1;
                }

                return $score;
            })
            ->take(10)
            ->values();
    }
}
