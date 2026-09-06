<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryViewSnapshot extends Model
{
    protected $fillable = ['brand_scope', 'date', 'total_views', 'facility_count'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
