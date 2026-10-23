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
    public const EDITABLE = ['hero_title', 'hero_subtitle', 'about_text', 'hero_image_id', 'theme', 'sections'];

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
    ];

    protected function casts(): array
    {
        return [
            'facility_id' => 'integer',
            'hero_image_id' => 'integer',
            'sections' => 'array',
            'published_at' => 'datetime',
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
