<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'kind', 'name', 'description', 'price', 'list_price', 'bonus_quote_credits',
        'duration_days', 'months', 'badge_label', 'badge_emoji', 'free_months_label',
        'tagline', 'features', 'only_city_slug', 'is_premium', 'is_active', 'sort_order',
        'audience', 'closing_text', 'photo_limit', 'can_upload_video',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'list_price' => 'float',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'months' => 'integer',
            'photo_limit' => 'integer',
            'can_upload_video' => 'boolean',
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

    /**
     * 18 Ekim 2026: liste fiyatı girilmiş ve şimdiki fiyattan yüksekse
     * lansman indirimi gösterilir.
     */
    public function hasLaunchDiscount(): bool
    {
        return $this->list_price && $this->list_price > $this->price;
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
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->features)));

        return array_values(array_filter($lines, fn ($l) => ! str_starts_with($l, '## ')));
    }

    /**
     * 22 Ekim 2026: kullanıcının talebi - paket ayrıntıları bölümler halinde.
     * features alanında "## Başlık" satırı yeni bölüm başlatır; diğer
     * satırlar "Madde adı: açıklama" biçimindedir (iki nokta yoksa tamamı
     * madde adıdır).
     *
     * @return array<int, array{title: string, items: array<int, array{name: string, text: string}>}>
     */
    public function featureGroups(): array
    {
        $groups = [];
        $current = null;
        foreach (preg_split('/\r\n|\r|\n/', (string) $this->features) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '## ')) {
                $groups[] = ['title' => trim(substr($line, 3)), 'items' => []];
                $current = array_key_last($groups);
                continue;
            }
            if ($current === null) {
                $groups[] = ['title' => '', 'items' => []];
                $current = array_key_last($groups);
            }
            [$name, $text] = array_pad(array_map('trim', explode(':', $line, 2)), 2, '');
            $groups[$current]['items'][] = ['name' => $name, 'text' => $text];
        }

        return $groups;
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
