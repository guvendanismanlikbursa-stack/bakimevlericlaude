<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 23 Ekim 2026: kurum sitesindeki her içerik değişikliğinin önceki hali
// (kim, ne zaman); admin panelinden eski hale dönmek için.
class FacilityWebsiteRevision extends Model
{
    protected $fillable = ['facility_website_id', 'editor_type', 'editor_id', 'editor_name', 'before'];

    protected function casts(): array
    {
        return ['before' => 'array', 'facility_website_id' => 'integer'];
    }

    public function site()
    {
        return $this->belongsTo(FacilityWebsite::class, 'facility_website_id');
    }
}
