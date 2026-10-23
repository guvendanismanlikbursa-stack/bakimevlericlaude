<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// 23 Ekim 2026: kullanıcının talebi - doktorun internet sitesi (bkz.
// 2026_10_23_160000 migration). Telefon, adres, branş, klinik doktor
// kaydından okunur; burada yalnızca siteye özel alanlar var. Yalnızca admin
// düzenler. Hasta yorumu hukuki nedenle hiç gösterilmez.
class ExpertWebsite extends Model
{
    public const STATUSES = FacilityWebsite::STATUSES;

    public const THEMES = FacilityWebsite::THEMES;

    // Değişiklik geçmişine yazılan (geri alınabilen) alanlar.
    public const EDITABLE = ['hero_title', 'hero_subtitle', 'about_text', 'ilgi_alanlari', 'calisma_saatleri', 'photo_path', 'theme', 'sections'];

    public const SECTIONS = [
        'hakkimda' => 'Hakkımda',
        'ilgi_alanlari' => 'İlgi alanları',
        'calisma_saatleri' => 'Çalışma saatleri',
        'konum' => 'Konum ve harita',
    ];

    protected $fillable = [
        'expert_id', 'slug', 'status', 'custom_domain', 'hero_title', 'hero_subtitle', 'about_text',
        'ilgi_alanlari', 'calisma_saatleri', 'photo_path', 'theme', 'sections', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'expert_id' => 'integer',
            'sections' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function expert()
    {
        return $this->belongsTo(Expert::class);
    }

    public function revisions()
    {
        return $this->hasMany(ExpertWebsiteRevision::class)->latest('id');
    }

    /**
     * Doktorun site kaydını getirir; yoksa taslak olarak oluşturur.
     */
    public static function forExpert(Expert $expert): self
    {
        return static::firstOrCreate(['expert_id' => $expert->id], [
            'slug' => static::uniqueSlug($expert->name),
            'status' => 'taslak',
            'theme' => 'mavi',
        ]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: 'doktor', 100, '');
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function isLive(): bool
    {
        return $this->status === 'yayinda';
    }

    public function sectionEnabled(string $key): bool
    {
        $sections = $this->sections;

        return ! is_array($sections) || ! array_key_exists($key, $sections) || (bool) $sections[$key];
    }

    public function themeColors(): array
    {
        return self::THEMES[$this->theme] ?? self::THEMES['mavi'];
    }

    public function publicUrl(): string
    {
        if ($this->custom_domain) {
            return 'https://'.$this->custom_domain;
        }
        if (app()->environment('local', 'testing')) {
            return url('/dr/'.$this->slug);
        }

        return 'https://bakimevleri.com/dr/'.$this->slug;
    }

    /** Unvan + ad (ör. "Uzm. Dr. Ayşe Yılmaz"); ad zaten unvanla başlıyorsa tekrar edilmez. */
    public function fullName(): string
    {
        $e = $this->expert;
        $title = trim((string) $e->title);
        $name = trim((string) $e->name);
        if ($title === '' || str_starts_with(mb_strtolower($name), mb_strtolower($title))) {
            return $name;
        }

        return $title.' '.$name;
    }

    public function heroTitle(): string
    {
        return $this->hero_title ?: $this->fullName();
    }

    public function heroSubtitle(): string
    {
        if ($this->hero_subtitle) {
            return $this->hero_subtitle;
        }
        $e = $this->expert;
        $yer = trim(($e->district ? $e->district.', ' : '').($e->city->name ?? ''), ', ');

        return trim(($e->branch_label ?: $e->branch ?: '').($yer ? ' · '.$yer : ''), ' ·');
    }

    /**
     * Hakkımda metni: girilmemişse tarafsız, bilgi veren bir metin üretilir.
     * Doktor kaydındaki açıklama yorumlardan türetildiği için kullanılmaz.
     */
    public function aboutText(): string
    {
        if ($this->about_text) {
            return $this->about_text;
        }
        $e = $this->expert;
        $brans = $e->branch_label ?: $e->branch;
        $yer = trim(($e->district ? $e->district.', ' : '').($e->city->name ?? ''), ', ');
        $cumle = $this->fullName().($brans ? ', '.$brans.' alanında' : '').' hastalarını'
            .($e->clinic_name ? ' '.$e->clinic_name.' bünyesinde' : '')
            .($yer ? ' '.$yer.' konumunda' : '').' kabul etmektedir.';

        return $cumle."\nRandevu ve bilgi için telefonla ulaşabilir ya da aşağıdaki formu doldurabilirsiniz.";
    }

    /** @return array<int, string> */
    public function ilgiAlanlariListesi(): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', (string) $this->ilgi_alanlari))
            ->map(fn ($s) => trim($s, " \t-•*"))->filter()->unique()->take(30)->values()->all();
    }

    /**
     * Değişikliği kaydeder; değişen alanların önceki hali geçmişe yazılır.
     */
    public function applyChanges(array $data, ?int $editorId, ?string $editorName): bool
    {
        $data = array_intersect_key($data, array_flip(self::EDITABLE));
        $this->fill($data);
        $degisen = array_keys(array_intersect_key($this->getDirty(), array_flip(self::EDITABLE)));
        if (! $degisen) {
            return false;
        }
        $before = [];
        foreach ($degisen as $alan) {
            $before[$alan] = $this->getOriginal($alan);
        }
        $this->revisions()->create([
            'editor_id' => $editorId,
            'editor_name' => $editorName,
            'before' => $before,
        ]);
        $this->save();

        return true;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('expert_website_domains'));
        static::deleted(fn () => Cache::forget('expert_website_domains'));
    }

    /**
     * Yayındaki doktor sitelerinin alan adı => id eşlemesi (önbellekli).
     *
     * @return array<string, int>
     */
    public static function liveDomainMap(): array
    {
        return Cache::remember('expert_website_domains', 300, fn () => static::where('status', 'yayinda')
            ->whereNotNull('custom_domain')->pluck('id', 'custom_domain')->all());
    }

    /**
     * Alan adı kurum veya doktor sitelerinden birinde (verilen kayıt hariç) kullanılıyor mu.
     */
    public static function domainTaken(string $domain, string $ownerType, int $ownerId): bool
    {
        return FacilityWebsite::where('custom_domain', $domain)->when($ownerType === 'facility', fn ($q) => $q->whereKeyNot($ownerId))->exists()
            || static::where('custom_domain', $domain)->when($ownerType === 'expert', fn ($q) => $q->whereKeyNot($ownerId))->exists();
    }
}
