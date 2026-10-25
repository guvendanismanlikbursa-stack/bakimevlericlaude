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
    public const EDITABLE = ['hero_title', 'hero_subtitle', 'about_text', 'ilgi_alanlari', 'calisma_saatleri', 'photo_path', 'theme', 'sections',
        'logo_path', 'brand_color', 'font_style', 'video_url', 'stats', 'faqs', 'announcements'];

    // 25 Ekim 2026: Profesyonel Site paketi (bkz. 2026_10_25_150000 migration).
    public const PLANS = FacilityWebsite::PLANS;

    public const FONT_STYLES = FacilityWebsite::FONT_STYLES;

    public const LIMITS = ['stats' => 4, 'faqs' => 12, 'announcements' => 10];

    public const SECTIONS = [
        'hakkimda' => 'Hakkımda',
        'ilgi_alanlari' => 'İlgi alanları',
        'calisma_saatleri' => 'Çalışma saatleri',
        'konum' => 'Konum ve harita',
    ];

    /** 25 Ekim 2026: Profesyonel sitenin tasarımı - admin kurumla/doktorla görüşüp seçer. */
    public const PRO_TASARIMLAR = ['klasik' => 'Klasik', 'hareketli' => '✨ Hareketli'];

    protected $fillable = [
        'expert_id', 'slug', 'status', 'custom_domain', 'hero_title', 'hero_subtitle', 'about_text',
        'ilgi_alanlari', 'calisma_saatleri', 'photo_path', 'theme', 'sections', 'published_at',
        'plan', 'pro_until', 'pro_yayina_aldi', 'pro_tasarim', 'logo_path', 'brand_color', 'font_style', 'video_url', 'stats', 'faqs', 'announcements',
    ];

    protected function casts(): array
    {
        return [
            'expert_id' => 'integer',
            'sections' => 'array',
            'published_at' => 'datetime',
            'pro_until' => 'date',
            'pro_yayina_aldi' => 'boolean',
            'stats' => 'array',
            'faqs' => 'array',
            'announcements' => 'array',
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

    /** Profesyonel Site paketi etkin mi (bitiş tarihi boşsa süresiz). */
    public function isPro(): bool
    {
        return $this->plan === 'profesyonel'
            && ($this->pro_until === null || ! $this->pro_until->copy()->endOfDay()->isPast());
    }

    /** @return array{ana: string, koyu: string, acik: string} */
    public function proColors(): array
    {
        $hex = $this->brand_color && preg_match('/^#[0-9a-f]{6}$/i', $this->brand_color) ? strtolower($this->brand_color) : null;
        if (! $hex) {
            $t = $this->themeColors();

            return ['ana' => $t['ana'], 'koyu' => $t['koyu'], 'acik' => $t['acik']];
        }

        return ['ana' => $hex, 'koyu' => FacilityWebsite::mixHex($hex, '#000000', 0.32), 'acik' => FacilityWebsite::mixHex($hex, '#ffffff', 0.9)];
    }

    public function videoEmbedUrl(): ?string
    {
        return (new FacilityWebsite(['video_url' => $this->video_url]))->videoEmbedUrl();
    }

    /** Dolu satırlar (boş satırlar atılır). */
    public function listOf(string $alan): array
    {
        return array_values(array_filter((array) ($this->{$alan} ?? []), fn ($r) => is_array($r) && array_filter($r, fn ($v) => trim((string) $v) !== '')));
    }

    /** 25 Ekim 2026: ziyaret / WhatsApp / arama / form sayacını bugün için bir artırır. */
    public function recordStat(string $alan): void
    {
        if (! in_array($alan, ['ziyaret', 'whatsapp', 'arama', 'form'], true)) {
            return;
        }
        try {
            $gun = now()->toDateString();
            \Illuminate\Support\Facades\DB::table('expert_website_stats')->insertOrIgnore(['expert_website_id' => $this->id, 'gun' => $gun]);
            \Illuminate\Support\Facades\DB::table('expert_website_stats')->where('expert_website_id', $this->id)->where('gun', $gun)->increment($alan);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Son 30 gün toplamı ve son 6 ayın aylık toplamları (kurum raporuyla aynı biçim). */
    public function statsReport(): array
    {
        $tablo = \Illuminate\Support\Facades\DB::table('expert_website_stats')->where('expert_website_id', $this->id);
        $alanlar = ['ziyaret', 'whatsapp', 'arama', 'form'];
        $son30 = (clone $tablo)->where('gun', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('COALESCE(SUM(ziyaret),0) ziyaret, COALESCE(SUM(whatsapp),0) whatsapp, COALESCE(SUM(arama),0) arama, COALESCE(SUM(form),0) form')->first();
        $satirlar = (clone $tablo)->where('gun', '>=', now()->startOfMonth()->subMonths(5)->toDateString())->get();
        $aylar = [];
        for ($i = 0; $i < 6; $i++) {
            $ay = now()->startOfMonth()->subMonths($i);
            $buAy = $satirlar->filter(fn ($s) => substr((string) $s->gun, 0, 7) === $ay->format('Y-m'));
            $kayit = ['ay' => $ay->locale('tr')->translatedFormat('F Y')];
            foreach ($alanlar as $a) {
                $kayit[$a] = (int) $buAy->sum($a);
            }
            $aylar[] = $kayit;
        }

        return ['son30' => collect($alanlar)->mapWithKeys(fn ($a) => [$a => (int) ($son30->{$a} ?? 0)])->all(), 'aylar' => $aylar];
    }

    /**
     * 25 Ekim 2026: admin ana sayfası / Doktor Siteleri özeti.
     */
    public static function summary(): array
    {
        $bugun = now()->toDateString();
        $stat = \Illuminate\Support\Facades\DB::table('expert_website_stats')->where('gun', '>=', now()->subDays(29)->toDateString());
        $toplam = (clone $stat)->selectRaw('COALESCE(SUM(ziyaret),0) ziyaret, COALESCE(SUM(whatsapp),0) whatsapp, COALESCE(SUM(arama),0) arama, COALESCE(SUM(form),0) form')->first();
        $enCok = (clone $stat)->join('expert_websites as ew', 'ew.id', '=', 'expert_website_stats.expert_website_id')
            ->join('experts as e', 'e.id', '=', 'ew.expert_id')
            ->groupBy('ew.expert_id', 'e.name')
            ->selectRaw('ew.expert_id, e.name as ad, SUM(ziyaret) ziyaret, SUM(whatsapp) whatsapp, SUM(arama) arama, SUM(form) form')
            ->orderByDesc('ziyaret')->limit(5)->get()->filter(fn ($s) => (int) $s->ziyaret > 0)->values();

        return [
            'son30' => ['ziyaret' => (int) $toplam->ziyaret, 'whatsapp' => (int) $toplam->whatsapp, 'arama' => (int) $toplam->arama, 'form' => (int) $toplam->form],
            'en_cok' => $enCok,
            'pro_aktif' => static::where('plan', 'profesyonel')->where(fn ($w) => $w->whereNull('pro_until')->orWhere('pro_until', '>=', $bugun))->count(),
            'standart_yayinda' => static::where('status', 'yayinda')->where(fn ($q) => $q->where('plan', '!=', 'profesyonel')->orWhereNull('plan')->orWhere('pro_until', '<', $bugun))->count(),
            'taslak' => static::where('status', 'taslak')->count(),
            'pro_bitiyor' => static::where('plan', 'profesyonel')->whereBetween('pro_until', [$bugun, now()->addDays(30)->toDateString()])->count(),
            'pro_dolmus' => static::where('plan', 'profesyonel')->where('pro_until', '<', $bugun)->count(),
        ];
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

    /** 25 Ekim 2026: arama sonucu başlığı - "Nilüfer Çocuk Doktoru – Uzm. Dr. Selin Arı | Bursa". */
    public function seoBaslik(): string
    {
        $e = $this->expert;
        $anahtar = trim(($e->district ?: ($e->city->name ?? '')).' '.($e->branch_label ?: $e->branch ?: 'Doktor'));

        return $anahtar.' – '.$this->fullName().($e->district && $e->city ? ' | '.$e->city->name : '');
    }

    public function seoAciklama(): string
    {
        $e = $this->expert;
        $yer = trim(($e->district ? $e->district.'/' : '').($e->city->name ?? ''), '/');
        $giris = $this->fullName().($yer ? ', '.$yer.' bölgesinde' : '').($e->branch ? ' '.$e->branch.($e->branch_label ? ' ('.$e->branch_label.')' : '').' alanında' : '').' muayene hizmeti veriyor.';
        $ek = trim(preg_replace('/\s+/', ' ', $this->aboutText()));

        return \Illuminate\Support\Str::limit(trim($giris.' '.$ek.' Randevu ve iletişim bilgileri.'), 158);
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
