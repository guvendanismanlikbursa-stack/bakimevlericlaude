<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'facility_id', 'brand', 'applicant_name', 'applicant_age', 'applicant_location',
        'applicant_phone', 'applicant_email', 'experience', 'desired_position',
        'status', 'forwarded_at', 'forwarded_by', 'consent_ip', 'consent_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'facility_id' => 'integer',
            'applicant_age' => 'integer',
            'forwarded_at' => 'datetime',
            'consent_accepted_at' => 'datetime',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function forwardedBy()
    {
        return $this->belongsTo(Admin::class, 'forwarded_by');
    }
}
