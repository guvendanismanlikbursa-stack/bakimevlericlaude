<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'kind', 'name', 'description', 'price', 'bonus_quote_credits',
        'duration_days', 'months', 'badge_label', 'badge_emoji', 'free_months_label',
        'tagline', 'features', 'only_city_slug', 'is_premium', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'months' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 18 Ekim 2026: İyilik Hareketi üyelik paketleri ile mevcut bakiye
    // paketleri aynı tabloda; 'kind' ile ayrılır (bkz. migration).
    public function scopeMembership($query)
    {
        return $query->where('kind', 'membership');
    }

    public function scopeCredit($query)
    {
        return $query->where(fn ($q) => $q->where('kind', 'credit')->orWhereNull('kind'));
    }

    public function isMembership(): bool
    {
        return $this->kind === 'membership';
    }

    /**
     * Facility::subscription_plan alanının mevcut değerleriyle (admin
     * "Abone Kurumlar" ekranı, süre uzatma) uyumlu plan anahtarı.
     */
    public function planKey(): string
    {
        return match ((int) $this->months) {
            12 => 'yearly',
            6 => 'semiannual',
            3 => 'quarterly',
            default => 'monthly',
        };
    }

    /**
     * Pakete özel ek haklar (admin panelinde her satıra bir madde).
     *
     * @return array<int, string>
     */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->features))));
    }

    /**
     * Premium gibi yalnızca belirli bir ilde satılan paketler için.
     */
    public function isAvailableForFacility(?Facility $facility): bool
    {
        if (! $this->only_city_slug) {
            return true;
        }

        return $facility && $facility->city && $facility->city->slug === $this->only_city_slug;
    }

    public function walletTopups()
    {
        return $this->hasMany(WalletTopup::class);
    }
}
