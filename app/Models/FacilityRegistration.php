<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityRegistration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand', 'name', 'facility_category_id', 'city_id', 'district', 'address', 'phone',
        'description', 'capacity', 'price_min', 'price_max',
        'applicant_name', 'applicant_email', 'applicant_phone',
        'status', 'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'price_min' => 'float',
            'price_max' => 'float',
        ];
    }

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
