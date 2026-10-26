<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// 7 Ekim 2026: "Özel Doktorlar" (doktorlar) - Facility'den BAGIMSIZ, ayri
// bir model. Bkz. migration yorumu - mevcut kurum sistemine dokunmadan
// ayri insa edilir.
class Expert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'title', 'branch', 'branch_label', 'city_id', 'district',
        'address', 'lat', 'lng', 'phone', 'phone_type', 'clinic_name', 'description',
        'google_maps_url', 'rating', 'review_count', 'reviews', 'supporter_until',
        'website_status', 'website_url', 'website_checked_at',
        'source', 'source_payload', 'is_published', 'is_claimed',
        'marketing_status', 'marketing_status_at', 'marketing_brochure_requested', 'marketing_callback_at',
    ];

    protected function casts(): array
    {
        return [
            'supporter_until' => 'date',
            'website_checked_at' => 'datetime',
            'marketing_status_at' => 'datetime',
            'marketing_callback_at' => 'datetime',
            'marketing_brochure_requested' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
            'rating' => 'float',
            'review_count' => 'integer',
            'reviews' => 'array',
            'source_payload' => 'array',
            'is_published' => 'boolean',
            'is_claimed' => 'boolean',
        ];
    }

    /**
     * 22 Ekim 2026: İyilik Hareketi Destekçisi doktor mu (bitiş tarihi bugün
     * veya sonrası). Yalnızca etiket gösterir, sıralamayı etkilemez.
     */
    /**
     * 22 Ekim 2026: doktorun Google Haritalar kaydındaki web adresinden site
     * durumunu çıkarır (Facility::WEBSITE_STATUSES ile aynı değerler).
     * Rehber siteleri (Doktortakvimi, Doktorsitesi vb.) ve çalıştığı
     * hastanenin doktor sayfası "rehber", Instagram/WhatsApp vb. "sosyal"
     * sayılır; boş adres "yok". Bilgi hiç tutulmamışsa null döner.
     *
     * @return array{0: ?string, 1: ?string} [durum, adres]
     */
    public static function classifyWebsite(?string $url, bool $fieldKnown = true): array
    {
        if (! $fieldKnown) {
            return [null, null];
        }
        // Alan 500 karakter; çok uzun (ör. kodlanmış Türkçe karakterli) adresler kesilir.
        $url = mb_substr(trim((string) $url), 0, 500);
        if ($url === '') {
            return ['yok', null];
        }
        $host = strtolower((string) parse_url(str_contains($url, '://') ? $url : 'http://'.$url, PHP_URL_HOST));
        $has = fn (array $keys) => (bool) array_filter($keys, fn ($k) => str_contains($host, $k));
        if ($has(['instagram.com', 'facebook.com', 'wa.me', 'whatsapp', 'linktr.ee', 'youtube.com', 'tiktok.com', 'twitter.com', 'linkedin.com']) || $host === 'x.com') {
            return ['sosyal', $url];
        }
        if ($has(['doktorsitesi.com', 'doktortakvimi.com', 'docplanner', 'sporlife.net', 'dishek.org', 'tikla.com.tr', 'tavsiyeediyorum', 'cybo.com', 'firmasec.com', 'hekimbul', 'eniyidoktor',
            'hastane', 'hospital', 'medicana', 'acibadem', 'medicalpark', 'memorial', 'livhospital', 'florence', 'anadolusaglik'])) {
            return ['rehber', $url];
        }

        return ['var', $url];
    }

    public function scopeWebsiteFilter($query, ?string $value)
    {
        return match ($value) {
            'eksik' => $query->whereIn('website_status', Facility::WEBSITE_MISSING_STATUSES),
            'bilinmiyor' => $query->whereNull('website_status'),
            'yok', 'sosyal', 'rehber', 'var' => $query->where('website_status', $value),
            default => $query,
        };
    }

    /**
     * 25 Ekim 2026: Doktorlar sayfası kart sayacı (bkz. expert_card_stats).
     * $idler verilirse hepsi için birden artırılır (listelenme).
     */
    /**
     * 26 Ekim 2026: kullanıcının talebi - doktor sayfası girişleri, kurum sayfalarıyla aynı mantıkla:
     * her açılış 'view' (bot ve tekrar dahil); bot değilse ve aynı tarayıcı / bağlantı bu doktoru
     * son 24 saatte açmadıysa ayrıca 'real_view' (tekil gerçek kişi). Panelde bölüm sayaçları bunu kullanır.
     */
    public static function girisKaydet(self $expert, \Illuminate\Http\Request $request): void
    {
        try {
            $tablo = \Illuminate\Support\Facades\DB::table('expert_engagement_events');
            $tablo->insert(['expert_id' => $expert->id, 'type' => 'view', 'created_at' => now()]);
            if (is_bot_user_agent($request->userAgent())) {
                return;
            }
            $anahtar = 'real_view_dr_'.$expert->id;
            $son = session($anahtar);
            $ipHash = hash('sha256', $request->ip().config('app.key'));
            $yeni = (! $son || now()->diffInHours($son) >= 24)
                && ! \Illuminate\Support\Facades\DB::table('expert_engagement_events')->where('expert_id', $expert->id)->where('type', 'real_view')
                    ->where('ip_hash', $ipHash)->where('created_at', '>=', now()->subDay())->exists();
            if ($yeni) {
                \Illuminate\Support\Facades\DB::table('expert_engagement_events')->insert(['expert_id' => $expert->id, 'type' => 'real_view', 'ip_hash' => $ipHash, 'created_at' => now()]);
            }
            session([$anahtar => now()]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Doktor giriş kaydı yazılamadı: '.$e->getMessage(), ['expert_id' => $expert->id]);
        }
    }

    public static function recordCardStat(array $idler, string $alan): void
    {
        $idler = array_values(array_unique(array_filter(array_map('intval', $idler))));
        if (! $idler || ! in_array($alan, ['listelenme', 'acilma', 'arama'], true)) {
            return;
        }
        try {
            $gun = now()->toDateString();
            \Illuminate\Support\Facades\DB::table('expert_card_stats')->insertOrIgnore(array_map(fn ($id) => ['expert_id' => $id, 'gun' => $gun], $idler));
            \Illuminate\Support\Facades\DB::table('expert_card_stats')->whereIn('expert_id', $idler)->where('gun', $gun)->increment($alan);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Son 30 günlük kart sayaçları, doktor id => {listelenme, acilma, arama}.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function cardStats30(array $idler)
    {
        return \Illuminate\Support\Facades\DB::table('expert_card_stats')
            ->whereIn('expert_id', array_values(array_filter($idler)) ?: [0])
            ->where('gun', '>=', now()->subDays(29)->toDateString())
            ->groupBy('expert_id')
            ->selectRaw('expert_id, SUM(listelenme) listelenme, SUM(acilma) acilma, SUM(arama) arama')
            ->get()->keyBy('expert_id');
    }

    public function isSupporter(): bool
    {
        return $this->supporter_until !== null && ! $this->supporter_until->copy()->endOfDay()->isPast();
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // 25 Ekim 2026: pazarlamacı CRM görüşme notları (en yeni üstte).
    public function marketingNotes()
    {
        return $this->hasMany(ExpertMarketingNote::class)->latest();
    }

    // 7 Ekim 2026: kullanicinin talebi - karttaki yuvarlak rozette rastgele
    // bir bas harf yerine, doktorun unvanindan ("Prof. Dr.", "Uzm. Dr." vb.)
    // kisa bir kisaltma gosterilsin - daha "sik" ve bilgilendirici. Akademik
    // unvanlar (Prof./Doç.) en onde kontrol edilir (en belirleyici olan),
    // sonra Op./Uzm./Dt./Dr. - hicbiri eslesmezse (ör. "Çocuk Hekimi" gibi
    // standart bir kisaltmasi olmayan unvanlar) isim bas harfine duser.
    //
    // ONEMLI: mb_strtoupper() PHP'de Turkce'ye ozel degil - "i" harfini
    // Turkce "İ" degil duz ASCII "I"ya cevirir (ör. "Hekimi" -> "HEKIMI",
    // "HEKİMİ" DEGIL). Karsilastirma dizeleri bu yuzden BILEREK duz ASCII
    // "I" ile yazilmistir (ör. 'HEKIM', 'DR') - Turkce "İ/ı" varyantiyla
    // yazilsaydi hicbir zaman eslesmezdi.
    public function titleAbbreviation(): string
    {
        $t = mb_strtoupper((string) $this->title, 'UTF-8');

        // 25 Ekim 2026: kullanıcının bildirdiği sorun - bazı kayıtlarda unvan ayrı alana değil
        // ismin içine yazılmış ("Acar Familycare Clinic Dr. Buğra ACAR", "... | Doç. Dr. Belgin ...");
        // unvan alanı boş olunca rozette ismin ilk harfi ("A") çıkıyordu. Unvan boşsa isimden,
        // yalnızca tam kelime olarak geçen unvan kısaltmalarına bakılır (kayıt değişmez).
        if (trim($t) === '') {
            $ad = mb_strtoupper((string) $this->name, 'UTF-8');
            $kelime = fn (string $k) => (bool) preg_match('/(?<!\p{L})'.$k.'(?!\p{L})/u', $ad);

            return match (true) {
                $kelime('PROF\.?') => 'PROF',
                $kelime('DOÇ\.?') => 'DOÇ',
                $kelime('OP\.') => 'OP',
                $kelime('UZM\.?') => 'UZM',
                $kelime('DT\.') => 'DT',
                $kelime('DR\.?') => 'DR',
                (bool) preg_match('/D[İI]Ş\s+HEK[İI]M/u', $ad) => 'DT',
                default => mb_substr((string) $this->name, 0, 1, 'UTF-8'),
            };
        }

        return match (true) {
            str_contains($t, 'PROF') => 'PROF',
            str_contains($t, 'DOÇ') => 'DOÇ',
            str_contains($t, 'OP') => 'OP',
            str_contains($t, 'UZM') => 'UZM',
            str_contains($t, 'DT') => 'DT',
            str_contains($t, 'DR') || str_contains($t, 'HEKIM') => 'DR',
            default => mb_substr((string) $this->name, 0, 1, 'UTF-8'),
        };
    }

    // 7 Ekim 2026: kullanicinin talebi - "her rütbe farklı renklerde olsun
    // proflar hepsi aynı renk uzm farklı bir renk dr ler farklı renk gibi".
    // titleAbbreviation() ile AYNI kisaltmaya gore renk secilir ki ikisi
    // birbirinden sapmasin (ör. "UZM" hem rozette hem renkte tutarli olsun).
    public function titleRankColor(): string
    {
        return match ($this->titleAbbreviation()) {
            'PROF' => '#7c3aed',
            'DOÇ' => '#4f46e5',
            'OP' => '#ea580c',
            'UZM' => '#2563eb',
            'DT' => '#0891b2',
            'DR' => '#0d9488',
            default => '#64748b',
        };
    }
}
