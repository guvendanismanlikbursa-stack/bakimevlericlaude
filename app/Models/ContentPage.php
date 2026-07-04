<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    protected $fillable = ['brand', 'type', 'title', 'summary', 'slug', 'body'];

    public function scopePages($query)
    {
        return $query->where('type', 'page');
    }

    public function scopeGuides($query)
    {
        return $query->where('type', 'guide');
    }
}
