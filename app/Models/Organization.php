<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// 7 Ekim 2026: "Organizasyon Şirketleri" - Facility VE Expert'ten BAGIMSIZ,
// Expert ile AYNI mimaride (bkz. migration yorumu) ucuncu bagimsiz bolum.
class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'title', 'service_type', 'service_type_label', 'city_id', 'district',
        'address', 'lat', 'lng', 'phone', 'phone_type', 'clinic_name',
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

    // 7 Ekim 2026: Expert'teki titleAbbreviation/titleRankColor'in karsiligi
    // - ama sirketlerde akademik unvan kavrami yok, rozette sadece isim bas
    // harfi gosterilir, sabit tek renk kullanilir.
    public function avatarAbbreviation(): string
    {
        return mb_substr((string) $this->name, 0, 1, 'UTF-8');
    }

    public function avatarColor(): string
    {
        return '#db2777';
    }
}
