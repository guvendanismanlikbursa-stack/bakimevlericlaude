<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// 23 Ekim 2026: kullanıcının talebi - kurumun internet sitesi (bkz.
// 2026_10_23_120000 migration). Fotoğraflar, hizmetler, menü, telefon, adres
// kurum kaydından okunur; burada yalnızca siteye özel alanlar var.
class FacilityWebsite extends Model
{
    public const STATUSES = ['taslak' => 'Taslak (yayında değil)', 'yayinda' => 'Yayında'];

    // Kurumun ve adminin değiştirebildiği alanlar (değişiklik geçmişine yazılır).
    /** 25 Ekim 2026: Profesyonel sitenin tasarımı - admin kurumla/doktorla görüşüp seçer. */
    public const PRO_TASARIMLAR = ['klasik' => 'Klasik', 'hareketli' => '✨ Hareketli'];

    public const EDITABLE = ['hero_title', 'hero_subtitle', 'about_text', 'hero_image_id', 'theme', 'sections',
        'logo_path', 'brand_color', 'font_style', 'video_url', 'stats', 'values_list', 'team', 'faqs', 'announcements'];

    // 23 Ekim 2026: "Profesyonel Site" paketi (bkz. 2026_10_23_200000 migration).
    public const PLANS = ['standart' => 'Hazır site', 'profesyonel' => 'Profesyonel Site'];

    public const FONT_STYLES = ['zarif' => 'Zarif (klasik, tırnaklı başlıklar)', 'modern' => 'Modern (sade, güçlü başlıklar)'];

    // Profesyonel sitenin iç sayfaları (adres => menü adı). İçeriği olmayan sayfa menüde çıkmaz.
    public const PRO_PAGES = [
        'hakkimizda' => 'Hakkımızda',
        'hizmetler' => 'Hizmetler',
        'galeri' => 'Galeri',
        'ekibimiz' => 'Ekibimiz',
        'duyurular' => 'Duyurular',
        'sss' => 'S.S.S.',
        'iletisim' => 'İletişim',
    ];

    // Tekrarlanan içerik gruplarının en fazla satır sayısı.
    public const LIMITS = ['stats' => 4, 'values_list' => 6, 'team' => 12, 'faqs' => 12, 'announcements' => 10];

    public const THEMES = [
        'yesil' => ['ad' => 'Yeşil', 'ana' => '#0d6e6a', 'koyu' => '#0a4f4c', 'acik' => '#e3f2f0'],
        'mavi' => ['ad' => 'Mavi', 'ana' => '#1d5fa8', 'koyu' => '#14457c', 'acik' => '#e5eef9'],
        'mor' => ['ad' => 'Mor', 'ana' => '#6d3fa0', 'koyu' => '#4f2c76', 'acik' => '#f0e9f8'],
        'turuncu' => ['ad' => 'Turuncu', 'ana' => '#b85a1c', 'koyu' => '#8a4114', 'acik' => '#fbeee4'],
    ];

    public const SECTIONS = [
        'hakkimizda' => 'Hakkımızda',
        'hizmetler' => 'Hizmetlerimiz',
        'bos_yer' => 'Boş yer / kontenjan bilgisi',
        'galeri' => 'Fotoğraf galerisi',
        'menu' => 'Yemek menüsü',
        'yorumlar' => 'Aile yorumları',
        'konum' => 'Konum ve harita',
    ];

    protected $fillable = [
        'facility_id', 'slug', 'status', 'custom_domain', 'hero_title', 'hero_subtitle', 'about_text',
        'hero_image_id', 'theme', 'sections', 'published_at',
        'plan', 'pro_until', 'pro_yayina_aldi', 'pro_tasarim', 'logo_path', 'brand_color', 'font_style', 'video_url', 'stats', 'values_list', 'team', 'faqs', 'announcements',
    ];

    protected function casts(): array
    {
        return [
            'facility_id' => 'integer',
            'hero_image_id' => 'integer',
            'sections' => 'array',
            'published_at' => 'datetime',
            'pro_until' => 'date',
            'pro_yayina_aldi' => 'boolean',
            'stats' => 'array',
            'values_list' => 'array',
            'team' => 'array',
            'faqs' => 'array',
            'announcements' => 'array',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function revisions()
    {
        return $this->hasMany(FacilityWebsiteRevision::class)->latest('id');
    }

    /**
     * Kurumun site kaydını getirir; yoksa taslak olarak oluşturur.
     */
    public static function forFacility(Facility $facility): self
    {
        return static::firstOrCreate(['facility_id' => $facility->id], [
            'slug' => static::uniqueSlug($facility->name),
            'status' => 'taslak',
            'theme' => 'yesil',
        ]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: 'kurum', 100, '');
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public static function normalizeDomain(?string $domain): ?string
    {
        $d = strtolower(trim((string) $domain));
        $d = preg_replace('#^https?://#', '', $d);
        $d = preg_replace('#^www\.#', '', $d);
        $d = rtrim(explode('/', $d)[0], '.');

        return $d !== '' ? $d : null;
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

    /**
     * Profesyonel şablonun renkleri: kurumun kendi rengi varsa ondan koyu ve
     * açık tonlar türetilir; yoksa seçili hazır tema kullanılır.
     *
     * @return array{ana: string, koyu: string, acik: string}
     */
    public function proColors(): array
    {
        $hex = $this->brand_color && preg_match('/^#[0-9a-f]{6}$/i', $this->brand_color) ? strtolower($this->brand_color) : null;
        if (! $hex) {
            $t = $this->themeColors();

            return ['ana' => $t['ana'], 'koyu' => $t['koyu'], 'acik' => $t['acik']];
        }

        return ['ana' => $hex, 'koyu' => static::mixHex($hex, '#000000', 0.32), 'acik' => static::mixHex($hex, '#ffffff', 0.9)];
    }

    public static function mixHex(string $a, string $b, float $oran): string
    {
        $pa = sscanf(ltrim($a, '#'), '%02x%02x%02x');
        $pb = sscanf(ltrim($b, '#'), '%02x%02x%02x');
        $c = [];
        for ($i = 0; $i < 3; $i++) {
            $c[] = (int) round($pa[$i] + ($pb[$i] - $pa[$i]) * $oran);
        }

        return sprintf('#%02x%02x%02x', ...$c);
    }

    /** YouTube adresinden gömülebilir (çerezsiz) adres; değilse null. */
    public function videoEmbedUrl(): ?string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0';
        }

        return null;
    }

    /** Dolu satırlar (boş satırlar atılır). */
    public function listOf(string $alan): array
    {
        return array_values(array_filter((array) ($this->{$alan} ?? []), fn ($r) => is_array($r) && array_filter($r, fn ($v) => trim((string) $v) !== '')));
    }

    /**
     * Ziyaret / WhatsApp / arama / form sayacını bugün için bir artırır.
     */
    public function recordStat(string $alan): void
    {
        if (! in_array($alan, ['ziyaret', 'whatsapp', 'arama', 'form'], true)) {
            return;
        }
        try {
            $gun = now()->toDateString();
            \Illuminate\Support\Facades\DB::table('facility_website_stats')->insertOrIgnore([
                'facility_website_id' => $this->id, 'gun' => $gun,
            ]);
            \Illuminate\Support\Facades\DB::table('facility_website_stats')
                ->where('facility_website_id', $this->id)->where('gun', $gun)->increment($alan);
        } catch (\Throwable $e) {
            // Sayaç hatası sitenin açılmasını engellemez.
            report($e);
        }
    }

    /**
     * 23 Ekim 2026: kullanıcının talebi - admin ana sayfası ve Kurum Siteleri
     * sayfası için ayrıntılı özet: paket/durum sayıları, son 30 gün toplamı,
     * en çok ziyaret alan siteler.
     */
    public static function summary(): array
    {
        $bugun = now()->toDateString();
        $proAktif = fn ($q) => $q->where('plan', 'profesyonel')->where(fn ($w) => $w->whereNull('pro_until')->orWhere('pro_until', '>=', $bugun));
        $stat = \Illuminate\Support\Facades\DB::table('facility_website_stats')->where('gun', '>=', now()->subDays(29)->toDateString());
        $toplam = (clone $stat)->selectRaw('COALESCE(SUM(ziyaret),0) ziyaret, COALESCE(SUM(whatsapp),0) whatsapp, COALESCE(SUM(arama),0) arama, COALESCE(SUM(form),0) form')->first();
        $enCok = (clone $stat)->join('facility_websites as fw', 'fw.id', '=', 'facility_website_stats.facility_website_id')
            ->join('facilities as f', 'f.id', '=', 'fw.facility_id')
            ->groupBy('fw.facility_id', 'f.name')
            ->selectRaw('fw.facility_id, f.name as ad, SUM(ziyaret) ziyaret, SUM(whatsapp) whatsapp, SUM(arama) arama, SUM(form) form')
            ->orderByDesc('ziyaret')->limit(5)->get();

        return [
            'pro_aktif' => $proAktif(static::query())->count(),
            'standart_yayinda' => static::where('status', 'yayinda')->where(fn ($q) => $q->where('plan', '!=', 'profesyonel')->orWhereNull('plan')->orWhere('pro_until', '<', $bugun))->count(),
            'taslak' => static::where('status', 'taslak')->count(),
            'pro_bitiyor' => static::where('plan', 'profesyonel')->whereBetween('pro_until', [$bugun, now()->addDays(30)->toDateString()])->count(),
            'pro_dolmus' => static::where('plan', 'profesyonel')->where('pro_until', '<', $bugun)->count(),
            'son30' => ['ziyaret' => (int) $toplam->ziyaret, 'whatsapp' => (int) $toplam->whatsapp, 'arama' => (int) $toplam->arama, 'form' => (int) $toplam->form],
            'en_cok' => $enCok->filter(fn ($s) => (int) $s->ziyaret > 0)->values(),
        ];
    }

    /**
     * Rapor: son 30 gün toplamı ve son 6 ayın aylık toplamları.
     *
     * @return array{son30: array<string,int>, aylar: array<int, array<string,mixed>>}
     */
    public function statsReport(): array
    {
        $tablo = \Illuminate\Support\Facades\DB::table('facility_website_stats')->where('facility_website_id', $this->id);
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

    public function sectionEnabled(string $key): bool
    {
        $sections = $this->sections;
        if (is_array($sections) && array_key_exists($key, $sections)) {
            return (bool) $sections[$key];
        }
        // 23 Ekim 2026: kullanıcının talebi - sahiplenilmemiş kurumlarda menü
        // görseli bizim koyduğumuz örnek menü olabilir; bölüm kapalı başlar,
        // admin veya kurum açıkça işaretlerse görünür.
        if ($key === 'menu') {
            $f = $this->facility;

            return $f && ($f->is_claimed || $f->is_broker_managed);
        }

        return true;
    }

    public function themeColors(): array
    {
        return self::THEMES[$this->theme] ?? self::THEMES['yesil'];
    }

    /**
     * Sitenin herkese açık adresi: alan adı varsa o, yoksa
     * bakimevleri.com/siteler/{slug} (site her zaman ana markada yayınlanır).
     */
    public function publicUrl(): string
    {
        if ($this->custom_domain) {
            return 'https://'.$this->custom_domain;
        }
        if (app()->environment('local', 'testing')) {
            return url('/siteler/'.$this->slug);
        }

        return 'https://bakimevleri.com/siteler/'.$this->slug;
    }

    public function heroTitle(): string
    {
        return $this->hero_title ?: (string) $this->facility->name;
    }

    public function heroSubtitle(): string
    {
        if ($this->hero_subtitle) {
            return $this->hero_subtitle;
        }
        $f = $this->facility;
        $yer = trim(($f->district ? $f->district.', ' : '').($f->city->name ?? ''), ', ');

        return trim(($f->category->name ?? '').($yer ? ' · '.$yer : ''), ' ·');
    }

    public function aboutText(): string
    {
        return (string) ($this->about_text ?: $this->facility->description);
    }

    public static function isDemoPath(?string $path): bool
    {
        return str_starts_with((string) $path, 'facilities/demo/');
    }

    /**
     * 23 Ekim 2026: kullanıcının talebi - sitede gösterilecek fotoğraflar.
     * Önce kurumun gerçek fotoğrafları; hiç yoksa örnek (demo) görseller
     * "temsili" işaretiyle kullanılır.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: bool} [fotoğraflar, temsili mi]
     */
    public static function siteGallery(Facility $facility): array
    {
        $facility->loadMissing('images');
        $gercek = $facility->images->reject(fn ($img) => static::isDemoPath($img->path))->values();
        if ($gercek->isNotEmpty()) {
            return [$gercek, false];
        }

        return [$facility->images->values(), $facility->images->isNotEmpty()];
    }

    /** Sitede gösterilecek yorumlar (Google ≥4★ ve onaylı platform yorumları). */
    public static function siteReviews(Facility $facility): \Illuminate\Support\Collection
    {
        $facility->loadMissing(['googleReviews', 'approvedReviews']);

        return $facility->googleReviews
            ->filter(fn ($r) => (int) $r->rating >= 4 && mb_strlen(trim((string) $r->body)) >= 20)
            ->map(fn ($r) => ['ad' => $r->author_name, 'puan' => (int) $r->rating, 'metin' => $r->body])
            ->concat($facility->approvedReviews->map(fn ($r) => ['ad' => $r->reviewer_name ?: 'Aile', 'puan' => (int) ($r->rating ?: 5), 'metin' => (string) $r->body]))
            ->filter(fn ($r) => trim((string) $r['metin']) !== '')
            ->take(6)->values();
    }

    /** Boş yer / kontenjan metni; bilgi girilmemişse null. */
    public static function vacancyText(Facility $f): ?string
    {
        if ($f->usesGenderSplitVacancy()) {
            $parca = [];
            if ($f->vacancy_male !== null) {
                $parca[] = 'Erkek: '.($f->vacancy_male ? 'Yer var' : 'Dolu');
            }
            if ($f->vacancy_female !== null) {
                $parca[] = 'Kadın: '.($f->vacancy_female ? 'Yer var' : 'Dolu');
            }

            return $parca ? implode(' · ', $parca) : null;
        }
        if ($f->vacancy_general !== null) {
            return $f->vacancy_general ? 'Şu an yer var' : 'Şu an dolu';
        }

        return null;
    }

    /**
     * 23 Ekim 2026: kullanıcının talebi - formda her bölümün yanında, sitede
     * görünüp görünmeyeceği ve nedeni yazar (işaretli olup verisi olmayan
     * bölüm sitede çıkmaz).
     *
     * @return array<string, array{0: bool, 1: string}>
     */
    public function sectionStatus(): array
    {
        $f = $this->facility;
        [$galeri, $temsili] = static::siteGallery($f);
        $hizmet = collect($f->services ?? [])->filter()->count();
        $yorum = static::siteReviews($f)->count();

        return [
            'hakkimizda' => trim($this->aboutText()) !== '' ? [true, 'Tanıtım yazısı var'] : [false, 'Tanıtım yazısı yok, görünmez'],
            'hizmetler' => $hizmet ? [true, $hizmet.' hizmet'] : [false, 'Profilde hizmet seçilmemiş, görünmez'],
            'bos_yer' => static::vacancyText($f) ? [true, 'Kontenjan bilgisi var'] : [false, 'Kontenjan bilgisi girilmemiş, görünmez'],
            'galeri' => $galeri->isEmpty() ? [false, 'Fotoğraf yok, görünmez']
                : ($temsili ? [true, 'Gerçek fotoğraf yok: '.$galeri->count().' örnek görsel "Temsili görseldir" notuyla gösterilir'] : [true, $galeri->count().' fotoğraf']),
            'menu' => ! $f->menu_image_path ? [false, 'Menü görseli yok, görünmez']
                : (($f->is_claimed || $f->is_broker_managed) ? [true, 'Menü görseli var'] : [true, 'Menü görseli var (örnek menü olabilir; kontrol edip işaretleyin)']),
            'yorumlar' => $yorum ? [true, $yorum.' yorum'] : [false, 'Uygun yorum yok, görünmez'],
            'konum' => ($f->lat && $f->lng) ? [true, 'Harita konumu var'] : [false, 'Harita konumu yok, görünmez'],
        ];
    }

    /**
     * Değişikliği kaydeder; önceki hali değişiklik geçmişine yazılır.
     */
    public function applyChanges(array $data, string $editorType, ?int $editorId, ?string $editorName): bool
    {
        $data = array_intersect_key($data, array_flip(self::EDITABLE));
        $this->fill($data);
        $degisen = array_keys(array_intersect_key($this->getDirty(), array_flip(self::EDITABLE)));
        if (! $degisen) {
            return false;
        }
        // Geçmişe yalnızca değişen alanların önceki değeri yazılır; geri alma da yalnızca onları geri getirir.
        $before = [];
        foreach ($degisen as $alan) {
            $before[$alan] = $this->getOriginal($alan);
        }
        $this->revisions()->create([
            'editor_type' => $editorType,
            'editor_id' => $editorId,
            'editor_name' => $editorName,
            'before' => $before,
        ]);
        $this->save();

        return true;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('facility_website_domains'));
        static::deleted(fn () => Cache::forget('facility_website_domains'));
    }

    /**
     * Yayındaki sitelerin alan adı => id eşlemesi (alan adı yönlendirmesi için önbellekli).
     *
     * @return array<string, int>
     */
    public static function liveDomainMap(): array
    {
        return Cache::remember('facility_website_domains', 300, fn () => static::where('status', 'yayinda')
            ->whereNotNull('custom_domain')->pluck('id', 'custom_domain')->all());
    }
}
