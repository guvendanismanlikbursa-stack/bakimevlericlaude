<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityQuestion extends Model
{
    protected $fillable = [
        'facility_id', 'brand', 'family_user_id', 'asker_name', 'question',
        'answer', 'answered_by', 'answered_at', 'status', 'reminder_sent_at',
    ];

    // 28 Temmuz 2026: canli uctan uca testte bulundu - facility_id INTEGER'a
    // cast edilmiyordu, oysa FacilityUser::facility_id ZATEN cast'li (bkz. o
    // modeldeki 28 Temmuz 2026 yorumu, ayni hata sinifi). Sonuc: Facility/
    // QuestionController::answer() icindeki === karsilastirmasi her zaman
    // string("6851") === int(6851) gibi FALSE donuyordu - kurum yetkilisi
    // ailelerin sorularina ASLA cevap veremiyordu (her zaman 403).
    protected function casts(): array
    {
        return ['facility_id' => 'integer', 'answered_at' => 'datetime', 'reminder_sent_at' => 'datetime'];
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
