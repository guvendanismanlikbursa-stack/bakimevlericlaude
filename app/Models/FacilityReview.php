<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityReview extends Model
{
    protected $fillable = [
        'facility_id', 'brand', 'reviewer_name', 'reviewer_phone', 'rating', 'body', 'status', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}