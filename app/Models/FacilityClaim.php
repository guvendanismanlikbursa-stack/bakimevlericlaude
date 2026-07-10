<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityClaim extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'facility_id', 'brand', 'applicant_name', 'applicant_email', 'applicant_phone',
        'document_path', 'note', 'status', 'admin_note', 'reviewed_by', 'reviewed_at',
        'applicant_lat', 'applicant_lng', 'applicant_city_name', 'distance_km', 'applicant_ip',
    ];

    // Ham konum verisi hassas kisisel veridir; ileride bir API eklenirse
    // yanlislikla disa sizmasin (bkz. FamilyUser signup_lat/lng ayni desen).
    protected $hidden = ['applicant_lat', 'applicant_lng'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'applicant_lat' => 'float',
            'applicant_lng' => 'float',
            'distance_km' => 'float',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
