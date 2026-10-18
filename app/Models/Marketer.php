<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marketer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'password', 'is_active', 'last_login_at', 'is_logged_in',
        'assigned_city_ids', 'assigned_districts', 'assigned_category_ids', 'can_view_offer_page',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_logged_in' => 'boolean',
            'assigned_city_ids' => 'array',
            'assigned_districts' => 'array',
            'assigned_category_ids' => 'array',
            'can_view_offer_page' => 'boolean',
        ];
    }

    public function notes()
    {
        return $this->hasMany(FacilityMarketingNote::class);
    }

    /**
     * 15 Ekim 2026: kullanicinin talebi - pazarlamacilara il/ilce/kategori
     * bazinda bolge/kapsam atamasi, boylece birden fazla pazarlamaci ayni
     * kurumu bosuna aramaz. Hicbir alan atanmamissa (hepsi bos/null)
     * pazarlamaci ESKISI GIBI sinirsiz gorur - geriye donuk uyumluluk.
     */
    public function hasScopeRestriction(): bool
    {
        return filled($this->assigned_city_ids) || filled($this->assigned_districts) || filled($this->assigned_category_ids);
    }
}
