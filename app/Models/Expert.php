<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// 7 Ekim 2026: "Özel Doktorlar" (doktorlar) - Facility'den BAGIMSIZ, ayri
// bir model. Bkz. migration yorumu - mevcut kurum sistemine dokunmadan
// ayri insa edilir.
class Expert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'title', 'branch', 'branch_label', 'city_id', 'district',
        'address', 'lat', 'lng', 'phone', 'phone_type', 'clinic_name', 'description',
        'google_maps_url', 'rating', 'review_count', 'reviews',
        'source', 'source_payload', 'is_published', 'is_claimed',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'rating' => 'float',
            'review_count' => 'integer',
            'reviews' => 'array',
            'source_payload' => 'array',
            'is_published' => 'boolean',
            'is_claimed' => 'boolean',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // 7 Ekim 2026: kullanicinin talebi - karttaki yuvarlak rozette rastgele
    // bir bas harf yerine, doktorun unvanindan ("Prof. Dr.", "Uzm. Dr." vb.)
    // kisa bir kisaltma gosterilsin - daha "sik" ve bilgilendirici. Akademik
    // unvanlar (Prof./Doç.) en onde kontrol edilir (en belirleyici olan),
    // sonra Op./Uzm./Dt./Dr. - hicbiri eslesmezse (ör. "Çocuk Hekimi" gibi
    // standart bir kisaltmasi olmayan unvanlar) isim bas harfine duser.
    //
    // ONEMLI: mb_strtoupper() PHP'de Turkce'ye ozel degil - "i" harfini
    // Turkce "İ" degil duz ASCII "I"ya cevirir (ör. "Hekimi" -> "HEKIMI",
    // "HEKİMİ" DEGIL). Karsilastirma dizeleri bu yuzden BILEREK duz ASCII
    // "I" ile yazilmistir (ör. 'HEKIM', 'DR') - Turkce "İ/ı" varyantiyla
    // yazilsaydi hicbir zaman eslesmezdi.
    public function titleAbbreviation(): string
    {
        $t = mb_strtoupper((string) $this->title, 'UTF-8');

        return match (true) {
            str_contains($t, 'PROF') => 'PROF',
            str_contains($t, 'DOÇ') => 'DOÇ',
            str_contains($t, 'OP') => 'OP',
            str_contains($t, 'UZM') => 'UZM',
            str_contains($t, 'DT') => 'DT',
            str_contains($t, 'DR') || str_contains($t, 'HEKIM') => 'DR',
            default => mb_substr((string) $this->name, 0, 1, 'UTF-8'),
        };
    }

    // 7 Ekim 2026: kullanicinin talebi - "her rütbe farklı renklerde olsun
    // proflar hepsi aynı renk uzm farklı bir renk dr ler farklı renk gibi".
    // titleAbbreviation() ile AYNI kisaltmaya gore renk secilir ki ikisi
    // birbirinden sapmasin (ör. "UZM" hem rozette hem renkte tutarli olsun).
    public function titleRankColor(): string
    {
        return match ($this->titleAbbreviation()) {
            'PROF' => '#7c3aed',
            'DOÇ' => '#4f46e5',
            'OP' => '#ea580c',
            'UZM' => '#2563eb',
            'DT' => '#0891b2',
            'DR' => '#0d9488',
            default => '#64748b',
        };
    }
}
