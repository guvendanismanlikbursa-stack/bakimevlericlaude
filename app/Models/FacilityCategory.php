<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'brand_scope', 'seo_description',
        'price_tier_standart_min', 'price_tier_premium_min', 'price_tier_ultra_min',
    ];

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    /**
     * 16 Temmuz 2026: fiyat segmenti (Ekonomik/Standart/Premium/Ultra Premium)
     * esikleri kurum turune gore degisir (bkz. migration). Deger hic
     * girilmemisse (beklenmedik durum, normalde migration hepsini doldurur)
     * config('platform.default_price_tiers') yedek olarak kullanilir.
     */
    public function priceTierThresholds(): array
    {
        $defaults = config('platform.default_price_tiers');

        return [
            'standart_min' => (float) ($this->price_tier_standart_min ?? $defaults['standart_min']),
            'premium_min' => (float) ($this->price_tier_premium_min ?? $defaults['premium_min']),
            'ultra_min' => (float) ($this->price_tier_ultra_min ?? $defaults['ultra_min']),
        ];
    }

    /**
     * Sadece verilen markaya ait kategorileri getirir.
     */
    public function scopeForBrand($query, string $brandSlug)
    {
        return $query->where('brand_scope', $brandSlug);
    }
}
