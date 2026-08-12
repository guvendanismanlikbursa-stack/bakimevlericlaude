<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityReview extends Model
{
    protected $fillable = [
        'facility_id', 'family_user_id', 'brand', 'reviewer_name', 'reviewer_phone', 'rating', 'body', 'status', 'approved_at',
        'facility_reply', 'facility_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'facility_id' => 'integer',
            'family_user_id' => 'integer',
            'approved_at' => 'datetime',
            'rating' => 'integer',
            'facility_replied_at' => 'datetime',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}