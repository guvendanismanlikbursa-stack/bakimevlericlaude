<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 25 Ekim 2026: pazarlamacı CRM'inde doktor başına biriken arama notu geçmişi
// (kurumlardaki FacilityMarketingNote ile aynı alanlar + ilgilendiği paket).
class ExpertMarketingNote extends Model
{
    protected $fillable = ['expert_id', 'marketer_id', 'admin_id', 'status', 'contact_name', 'contact_role', 'website_status', 'site_offer_interest', 'package_interest', 'note', 'brochure_requested', 'callback_at'];

    // Doktorlara özel paket seçenekleri (sorular kurumlarla aynı, yalnızca bu farklı).
    public const PACKAGES = [
        'destekci' => 'İyilik Hareketi Destekçisi (1.200 ₺/yıl)',
        'standart_site' => 'Standart Site',
        'profesyonel_site' => 'Profesyonel Site',
    ];

    protected function casts(): array
    {
        return ['brochure_requested' => 'boolean', 'callback_at' => 'datetime'];
    }

    public function contactLabel(): ?string
    {
        $role = FacilityMarketingNote::CONTACT_ROLES[$this->contact_role] ?? null;
        if (! $this->contact_name && ! $role) {
            return null;
        }

        return trim(($this->contact_name ?: 'İsimsiz').($role ? " ({$role})" : ''));
    }

    public function expert()
    {
        return $this->belongsTo(Expert::class)->withTrashed();
    }

    public function marketer()
    {
        return $this->belongsTo(Marketer::class)->withTrashed();
    }

    /** 30 Ekim 2026: admin CRM'inden yazılan not (marketer_id boş, admin_id dolu). */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
