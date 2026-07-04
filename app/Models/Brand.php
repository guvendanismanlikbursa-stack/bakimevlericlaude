<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['slug', 'name', 'theme', 'default_section', 'category_scope', 'is_active'];

    protected function casts(): array
    {
        return ['category_scope' => 'array', 'is_active' => 'boolean'];
    }
}
