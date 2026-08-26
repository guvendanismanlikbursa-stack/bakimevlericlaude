<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityProgramType extends Model
{
    public const TYPES = [
        'yarim_gun' => 'Yarım Gün',
        'tam_gun' => 'Tam Gün',
        'saatlik' => 'Saatlik Bakım',
    ];

    protected $fillable = ['facility_id', 'program_type', 'price_min', 'price_max', 'sort_order'];

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
        return self::TYPES[$this->program_type] ?? $this->program_type;
    }
}
