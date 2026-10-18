<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 27 Eylul 2026: pazarlamaci CRM'inde kurum basina birikerek tutulan arama
// notu gecmisi - bkz. Marketer\CrmController.
class FacilityMarketingNote extends Model
{
    protected $fillable = ['facility_id', 'marketer_id', 'admin_id', 'status', 'contact_name', 'contact_role', 'note', 'brochure_requested', 'callback_at'];

    // 18 Ekim 2026: görüşülen kişinin kurumdaki konumu.
    public const CONTACT_ROLES = ['calisan' => 'Çalışan', 'mudur' => 'Müdür', 'sahibi' => 'Sahibi'];

    /**
     * "Ayşe Yılmaz (Müdür)" gibi tek satırlık görüşülen kişi bilgisi; hiçbiri yoksa null.
     */
    public function contactLabel(): ?string
    {
        $role = self::CONTACT_ROLES[$this->contact_role] ?? null;
        if (! $this->contact_name && ! $role) {
            return null;
        }

        return trim(($this->contact_name ?: 'İsimsiz').($role ? " ({$role})" : ''));
    }

    protected function casts(): array
    {
        return [
            'brochure_requested' => 'boolean',
            'callback_at' => 'datetime',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function marketer()
    {
        // 15 Ekim 2026: kullanicinin talebi - artik sildigi pazarlamacilarin
        // GECMIS gorusme notlari (admin "Tum Gorusmeler" ekrani) kaybolmasin
        // ya da isim bos gorunmesin, bu yuzden silinmis pazarlamaci da
        // bulunabilir kalir (sadece giris yapamaz, bkz. MarketerAuth).
        return $this->belongsTo(Marketer::class)->withTrashed();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * 29 Eylul 2026: kullanicinin talebi - "100 arama -> 30 yetkiliyle
     * gorusme -> 15 detay dinledi -> 7 fiyat istedi -> 3 odeme" tarzi bir
     * satis hunisi. Her kurum icin simdiye kadar ULASTIGI EN ILERI asama
     * bulunur (bir kurum "fiyat istedi" oldu ise "yetkiliyle gorustu"
     * asamasini da GECMIS sayilir), sonra her asamaya ULASAN/GECEN toplam
     * kurum sayisi doner - klasik "en az bu kadar ilerledi" huni mantigi.
     * subscribed VE receipt_sent AYNI son asama sayilir (ikisi de odemeyi
     * gosterir, hangisi once girildiyse onemi yok).
     */
    public static function funnelStages(): array
    {
        $stageOrder = [
            'called' => 1,
            'reached_decision_maker' => 2,
            'listened_details' => 3,
            'price_requested' => 4,
            'subscribed' => 5,
            'receipt_sent' => 5,
        ];

        $maxStagePerFacility = static::query()
            ->select('facility_id', 'status')
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($notes) => $notes->pluck('status')->map(fn ($s) => $stageOrder[$s] ?? 0)->max());

        $labels = [
            1 => 'Arandı',
            2 => 'Yetkiliyle Görüştü',
            3 => 'Detay Dinledi',
            4 => 'Fiyat İstedi',
            5 => 'Ödeme Yaptı',
        ];

        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($maxStagePerFacility as $maxStage) {
            for ($i = 1; $i <= $maxStage; $i++) {
                $counts[$i]++;
            }
        }

        $stages = [];
        foreach ($labels as $i => $label) {
            $prev = $i > 1 ? $counts[$i - 1] : null;
            $stages[] = [
                'label' => $label,
                'count' => $counts[$i],
                'percent_of_total' => $counts[1] > 0 ? round($counts[$i] / $counts[1] * 100) : 0,
                'percent_of_prev' => ($prev !== null && $prev > 0) ? round($counts[$i] / $prev * 100) : null,
            ];
        }

        return $stages;
    }
}
