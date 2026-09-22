<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 20 Eylul 2026: kullanicinin talebi - bkz. migration'daki ayni tarihli
// yorum. Veri cekici ile toplanan Google yorumlari - platformun kendi
// aile yorumlarindan (FacilityReview) TAMAMEN AYRI ve ayrica etiketli
// gosterilir, asla karistirilmaz.
class FacilityGoogleReview extends Model
{
    protected $fillable = [
        'facility_id', 'author_name', 'rating', 'body', 'relative_time_text', 'position', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'position' => 'integer',
            'fetched_at' => 'datetime',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
