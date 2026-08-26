<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityRoomType extends Model
{
    // Sabit, kapali bir set (yeni tip eklemek gelistirici degisikligi
    // gerektirir) - kullanicinin acikca istedigi 4 oda tipiyle sinirli
    // tutuldu, admin formunda serbest metin degil bu sabit liste kullanilir.
    public const TYPES = [
        'tek_kisilik' => 'Tek Kişilik Oda',
        'iki_kisilik' => '2 Kişilik Oda',
        'uc_kisilik' => '3 Kişilik Oda',
        'paylasimli' => 'Paylaşımlı Oda',
    ];

    protected $fillable = ['facility_id', 'room_type', 'price_min', 'price_max', 'sort_order'];

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
        return self::TYPES[$this->room_type] ?? $this->room_type;
    }
}
