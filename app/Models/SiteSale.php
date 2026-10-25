<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 25 Ekim 2026: kurum / doktor sitesi satış kaydı (bkz. Admin\SiteSaleController).
class SiteSale extends Model
{
    public const TURLER = ['kurum' => 'Kurum', 'doktor' => 'Doktor'];

    public const PLANLAR = ['standart' => 'Standart Site', 'profesyonel' => 'Profesyonel Site'];

    public const ODEME = ['odendi' => 'Ödendi', 'kismi' => 'Kısmen ödendi', 'bekliyor' => 'Ödeme bekleniyor'];

    protected $fillable = ['owner_type', 'owner_id', 'owner_name', 'plan', 'baslangic', 'bitis', 'ucret', 'odeme', 'marketer_id', 'admin_id', 'notlar'];

    protected function casts(): array
    {
        return ['baslangic' => 'date', 'bitis' => 'date', 'ucret' => 'decimal:2'];
    }

    public function marketer()
    {
        return $this->belongsTo(Marketer::class)->withTrashed();
    }

    /** Kurum ya da doktor kaydı (silinmiş olsa da). */
    public function owner()
    {
        return $this->owner_type === 'doktor'
            ? Expert::withTrashed()->find($this->owner_id)
            : Facility::withTrashed()->find($this->owner_id);
    }

    public function aktifMi(): bool
    {
        return ! $this->baslangic->isFuture() && ($this->bitis === null || ! $this->bitis->copy()->endOfDay()->isPast());
    }

    /** Admin'de düzenleme bağlantısı (kurum/doktor sitesi sayfası). */
    public function siteDuzenleUrl(): string
    {
        return $this->owner_type === 'doktor'
            ? route('admin.experts.website.edit', $this->owner_id)
            : route('admin.facility-websites.edit', $this->owner_id);
    }
}
