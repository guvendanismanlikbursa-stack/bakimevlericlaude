<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marketer extends Model
{
    protected $fillable = ['name', 'phone', 'password', 'is_active', 'last_login_at', 'is_logged_in'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_logged_in' => 'boolean',
        ];
    }

    public function notes()
    {
        return $this->hasMany(FacilityMarketingNote::class);
    }
}
