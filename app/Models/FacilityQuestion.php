<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityQuestion extends Model
{
    protected $fillable = [
        'facility_id', 'brand', 'family_user_id', 'asker_name', 'question',
        'answer', 'answered_by', 'answered_at', 'status',
    ];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function answeredByUser()
    {
        return $this->belongsTo(FacilityUser::class, 'answered_by');
    }

    public function scopeAnswered($query)
    {
        return $query->whereNotNull('answer');
    }

    public function scopePending($query)
    {
        return $query->whereNull('answer');
    }
}
