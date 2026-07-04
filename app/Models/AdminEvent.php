<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminEvent extends Model
{
    protected $fillable = ['action_site', 'admin_id', 'event_type', 'entity_type', 'entity_id', 'detail_json'];

    protected function casts(): array
    {
        return ['detail_json' => 'array'];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
