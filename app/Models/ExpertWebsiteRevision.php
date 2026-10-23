<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 23 Ekim 2026: doktor sitesinde yapılan her değişikliğin önceki hali.
class ExpertWebsiteRevision extends Model
{
    protected $fillable = ['expert_website_id', 'editor_id', 'editor_name', 'before'];

    protected function casts(): array
    {
        return ['before' => 'array'];
    }

    public function website()
    {
        return $this->belongsTo(ExpertWebsite::class, 'expert_website_id');
    }
}
