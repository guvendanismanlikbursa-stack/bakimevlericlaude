<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityServiceOption extends Model
{
    protected $fillable = ['section_slug', 'name', 'slug'];

    public function facilities()
    {
        return $this->belongsToMany(Facility::class)->withTimestamps();
    }
}
