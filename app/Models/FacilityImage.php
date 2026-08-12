<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityImage extends Model
{
    protected $fillable = ['facility_id', 'path', 'sort_order'];

    // 28 Temmuz 2026: canli uctan uca testte bulundu - facility_id INTEGER'a
    // cast edilmiyordu (MySQL/PDO string doner), oysa FacilityUser::facility_id
    // ZATEN cast'li (bkz. o modeldeki 28 Temmuz 2026 yorumu, ayni hata sinifi).
    // Sonuc: Facility/ProfileController::deleteImage() icindeki === karsilastirmasi
    // her zaman string("6851") === int(6851) gibi FALSE donuyordu - kurum
    // yetkilisi kendi galerisinden TEK BIR gorseli bile hicbir zaman silemiyordu.
    protected function casts(): array
    {
        return [
            'facility_id' => 'integer',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
