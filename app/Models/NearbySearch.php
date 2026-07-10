<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NearbySearch extends Model
{
    protected $fillable = ['brand', 'lat', 'lng', 'city_name', 'ip', 'family_user_id'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }
}
