<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SearchQuery extends Model
{
    protected $fillable = [
        'brand', 'city_id', 'facility_category_id', 'service', 'search_date', 'count',
    ];

    protected function casts(): array
    {
        return [
            'search_date' => 'date',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    /**
     * Ayni gun icinde ayni (brand, city, category, service) kombinasyonu
     * tekrar aranirsa yeni satir acmaz, mevcut satirin sayacini artirir —
     * boylece bu tablo "site_visits" gibi kontrollu buyur.
     */
    public static function record(string $brand, ?int $cityId, ?int $categoryId, ?string $service): void
    {
        $today = Carbon::today();

        $row = static::query()
            ->where('brand', $brand)
            ->where('city_id', $cityId)
            ->where('facility_category_id', $categoryId)
            ->where('service', $service)
            ->whereDate('search_date', $today)
            ->first();

        if ($row) {
            $row->increment('count');
            return;
        }

        static::create([
            'brand' => $brand,
            'city_id' => $cityId,
            'facility_category_id' => $categoryId,
            'service' => $service,
            'search_date' => $today,
            'count' => 1,
        ]);
    }
}
