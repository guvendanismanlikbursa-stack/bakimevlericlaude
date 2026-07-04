<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildFacilityDetail extends Model
{
    protected $fillable = ['facility_id', 'details'];
    protected function casts(): array { return ['details' => 'array']; }
}
