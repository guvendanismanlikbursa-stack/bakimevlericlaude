<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityAgeGroup extends Model
{
    public const TYPES = [
        '0_1' => '0-1 Yaş (Bebek)',
        '1_2' => '1-2 Yaş',
        '2_3' => '2-3 Yaş',
        '3_4' => '3-4 Yaş',
        '4_6' => '4-6 Yaş (Anaokulu)',
    ];

    protected $fillable = ['facility_id', 'age_group', 'price_min', 'price_max', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price_min' => 'float',
            'price_max' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function label(): string
    {
        return self::TYPES[$this->age_group] ?? $this->age_group;
    }
}
