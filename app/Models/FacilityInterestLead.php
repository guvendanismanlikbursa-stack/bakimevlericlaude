<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 20 Eylul 2026: kullanicinin talebi - bkz. migration'daki ayni tarihli
// yorum. Ön kayıtlı kurumlardaki "Bilgi ve Fiyat Talep Et" formunun kaydı.
class FacilityInterestLead extends Model
{
    protected $fillable = [
        'facility_id', 'brand', 'full_name', 'phone', 'email', 'message', 'status', 'sent_to_facility_at',
    ];

    protected $casts = [
        'sent_to_facility_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
