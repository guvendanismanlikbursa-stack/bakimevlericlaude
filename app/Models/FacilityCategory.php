<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityCategory extends Model
{
    protected $fillable = ['name', 'slug', 'brand_scope', 'seo_description'];

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    /**
     * Sadece verilen markaya ait kategorileri getirir.
     */
    public function scopeForBrand($query, string $brandSlug)
    {
        return $query->where('brand_scope', $brandSlug);
    }
}
