<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilySavedSearch extends Model
{
    protected $fillable = [
        'family_user_id', 'brand', 'section_slug', 'filters', 'label', 'last_checked_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'last_checked_at' => 'datetime',
    ];

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }
}
