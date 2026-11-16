<?php

namespace App\Support;

use App\Services\GoogleVeriServisi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 15 Kasım 2026: kullanıcının kararı - "bizim sayaçlarımıza güvenmiyorum, admin panelindeki verileri
 * Analytics'e göre dizayn et" + "4 bölüm için ayrı ayrı" + "bütün istatistikleri tek sayfada topla" + grafikler.
 *
 * Ham veri her sabah zamanlanmış görevle (google:veri-yenile --gun=N) çekilip günlük önbelleğe yazılır;
 * sayfa açılışında Google'a gidilmez (ilk çekiş ~40 sn sürebiliyor, paylaşımlı sunucu sayfayı keser).
 * Tüm raporlar site (hostName) boyutuyla çekilir; site seçimi PHP tarafında süzülür, ek istek gerekmez.
 * Bölümler: /kurumlar/{slug} ve /siteler/{slug} sayfaları veritabanındaki kurumun kategorisine göre
 * Yaşlı Bakım / Çocuk / Rehabilitasyon; /doktor, /dr, /uzmanlar adresleri Doktorlar.
 */
class AnalitikPanelVerisi
{
    public const DONEMLER = [7 => 'Son 7 gün', 30 => 'Son 30 gün', 90 => 'Son 3 ay', 365 => 'Son 1 yıl'];

    private const OZET = ['activeUsers', 'newUsers', 'sessions', 'engagedSessions', 'screenPageViews', 'userEngagementDuration'];

    // 16 Kasım 2026: kullanıcının talebi - bakimevibul.com'a Hong Kong/Çin'den gelen kazıyıcı bot ziyaretleri
    // (son 30 günde ~11.800 sahte ziyaretçi, %0,2 ilgi) istatistikleri bozuyordu. Bu iki ülkeden gerçek aile
    // ziyareti olmadığı için tüm Analytics sorgularında hariç tutulur. Ağ ayrıca BotKalkani ile engellendi.
    public const HARIC_ULKELER = ['Hong Kong', 'China'];

    public function __construct(private GoogleVeriServisi $g) {}

    private function rapor(array $istek): array
    {
        return $this->g->rapor($istek + ['dimensionFilter' => ['notExpression' => ['filter' => [
            'fieldName' => 'country', 'inListFilter' => ['values' => self::HARIC_ULKELER]]]]]);
    }

    private static function anahtar(int $gun, ?string $tarih = null): string
    {
        return 'google_ham_'.$gun.'_'.($tarih ?? now()->toDateString());
    }

    /** Google'dan bir dönemin ham verisini çeker ve önbelleğe yazar (zamanlanmış görev / elle yenile). */
    public function cek(int $gun): array
    {
        $bas = now()->subDays($gun)->toDateString();
        $son = now()->subDay()->toDateString();
        $iki = [['startDate' => $bas, 'endDate' => $son], ['startDate' => now()->subDays($gun * 2)->toDateString(), 'endDate' => now()->subDays($gun + 1)->toDateString()]];
        $bir = [['startDate' => $bas, 'endDate' => $son]];
        $m = fn (array $adlar) => array_map(fn ($x) => ['name' => $x], $adlar);
        $hostlu = fn (string $boyut, array $met, int $limit) => $this->rapor([
            'dateRanges' => $bir, 'dimensions' => [['name' => $boyut], ['name' => 'hostName']], 'metrics' => $m($met), 'limit' => $limit,
            'orderBys' => [['metric' => ['metricName' => $met[0]], 'desc' => true]]]);

        $ham = [
            'tarih' => now()->toDateTimeString(), 'bas' => $bas, 'son' => $son,
            'ozet' => $this->rapor(['dateRanges' => $iki, 'dimensions' => [['name' => 'hostName']], 'metrics' => $m(self::OZET), 'limit' => 50]),
            'toplam' => $this->rapor(['dateRanges' => $iki, 'metrics' => $m(self::OZET)]),
            'gunluk' => $this->rapor(['dateRanges' => $bir, 'dimensions' => [['name' => 'date'], ['name' => 'hostName']], 'metrics' => $m(['activeUsers', 'engagedSessions']), 'limit' => 5000]),
            'kanal' => $hostlu('sessionDefaultChannelGroup', ['sessions', 'engagedSessions', 'activeUsers'], 200),
            'kaynak' => $hostlu('sessionSourceMedium', ['sessions', 'engagedSessions', 'activeUsers'], 300),
            'sehir' => $hostlu('city', ['activeUsers', 'engagedSessions'], 500),
            'cihaz' => $hostlu('deviceCategory', ['activeUsers', 'engagedSessions'], 50),
            'olay' => $hostlu('eventName', ['eventCount', 'totalUsers'], 200),
            'sayfa' => $hostlu('pagePath', ['screenPageViews', 'activeUsers'], 15000),
            'sc' => [],
        ];
        $scBas = now()->subDays($gun + 2)->toDateString();
        $scSon = now()->subDays(3)->toDateString();
        $ham['sc_bas'] = $scBas;
        $ham['sc_son'] = $scSon;
        foreach (GoogleVeriServisi::SITELER as $s) {
            $ham['sc'][$s] = [
                'toplam' => $this->g->aramaKonsolu($s, $scBas, $scSon)[0] ?? null,
                'gunluk' => $this->g->aramaKonsolu($s, $scBas, $scSon, ['date'], 400),
                'sorgu' => $this->g->aramaKonsolu($s, $scBas, $scSon, ['query'], 40),
            ];
        }
        Cache::put(self::anahtar($gun), $ham, now()->endOfDay());
        Cache::forever('google_ham_'.$gun.'_son', $ham);   // bugünkü çekiş olmazsa en son başarılı veri gösterilir

        return $ham;
    }

    /**
     * 16 Kasım 2026: kullanıcının talebi - "günlük giriş sayıları da admin panelinde gösterilsin".
     * Son 24 saatin (saat saat) ziyaretleri, 3 site ayrı ayrı. Küçük bir sorgu; saatlik görevle yenilenir.
     * Hong Kong/Çin bot olarak ayrı sayılır, gerçek rakamlara katılmaz.
     */
    public function cekSon24(): array
    {
        $met = ['totalUsers', 'sessions', 'engagedSessions', 'screenPageViews', 'userEngagementDuration'];
        $satirlar = $this->g->rapor(['dateRanges' => [['startDate' => '2daysAgo', 'endDate' => 'today']],
            'dimensions' => [['name' => 'dateHour'], ['name' => 'hostName'], ['name' => 'country']],
            'metrics' => array_map(fn ($m) => ['name' => $m], $met), 'limit' => 10000]);
        $sinir = now()->subHours(23)->format('YmdH');
        $bugun = now()->format('Ymd');
        $gaDilim = config('services.google_analytics.timezone') ?: 'America/Los_Angeles';
        $saatler = [];
        for ($i = 23; $i >= 0; $i--) {
            $saatler[now()->subHours($i)->format('YmdH')] = 0;
        }
        $siteler = [];
        foreach (GoogleVeriServisi::SITELER as $s) {
            $siteler[$s] = ['kisi' => 0, 'oturum' => 0, 'etk' => 0, 'sayfa' => 0, 'sure' => 0, 'bugun' => 0, 'bot' => 0, 'saat' => $saatler];
        }
        foreach ($satirlar as $r) {
            [$saat, $host, $ulke] = $r;
            $saat = \Illuminate\Support\Carbon::createFromFormat('YmdH', $saat, $gaDilim)->setTimezone(config('app.timezone'))->format('YmdH');
            $h = self::host($host);
            if (! $h || $saat < $sinir) {
                continue;
            }
            if (in_array($ulke, self::HARIC_ULKELER, true)) {
                $siteler[$h]['bot'] += $r[3];

                continue;
            }
            $siteler[$h]['kisi'] += $r[3];
            $siteler[$h]['oturum'] += $r[4];
            $siteler[$h]['etk'] += $r[5];
            $siteler[$h]['sayfa'] += $r[6];
            $siteler[$h]['sure'] += $r[7];
            if (str_starts_with($saat, $bugun)) {
                $siteler[$h]['bugun'] += $r[3];
            }
            if (isset($siteler[$h]['saat'][$saat])) {
                $siteler[$h]['saat'][$saat] += $r[3];
            }
        }
        $veri = ['tarih' => now()->toDateTimeString(), 'siteler' => $siteler];
        Cache::forever('google_son24', $veri);

        return $veri;
    }

    /** Son 24 saat verisi; 70 dakikadan eskiyse (ya da hiç yoksa) Google'dan tazelenir, hata olursa eldeki gösterilir. */
    public function son24(): ?array
    {
        $veri = Cache::get('google_son24');
        if (! $veri || now()->diffInMinutes(\Illuminate\Support\Carbon::parse($veri['tarih'])->toImmutable(), true) > 70) {
            try {
                $veri = $this->cekSon24();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $veri;
    }

    /** Önbellekteki veri (yoksa en son başarılı veri; o da yoksa null). */
    public function ham(int $gun): ?array
    {
        return Cache::get(self::anahtar($gun)) ?? Cache::get('google_ham_'.$gun.'_son');
    }

    private static function host(string $h): ?string
    {
        $h = preg_replace('/^www\./', '', $h);

        return in_array($h, GoogleVeriServisi::SITELER, true) ? $h : null;
    }

    /** Ham veriyi seçilen site / il / bölüme göre sayfanın kullanacağı biçime getirir. */
    public function hazirla(array $ham, string $site, ?int $ilId, ?string $bolumSec): array
    {
        $uygun = fn (?string $h) => $h && ($site === 'hepsi' || $h === $site);

        // Özet: site site + seçime göre toplam (hepsi ise tekil kişi sayısı ayrı sorgudan gelir)
        $ozet = [];
        foreach ($ham['ozet'] as $r) {
            if (! $h = self::host($r[0])) {
                continue;
            }
            $d = $r[1] === 'date_range_0' ? 'simdi' : 'once';
            foreach (self::OZET as $i => $met) {
                $ozet[$h][$d][$met] = ($ozet[$h][$d][$met] ?? 0) + $r[$i + 2];
            }
        }
        if ($site === 'hepsi') {
            $toplam = ['simdi' => [], 'once' => []];
            foreach ($ham['toplam'] as $r) {
                foreach (self::OZET as $i => $met) {
                    $toplam[$r[0] === 'date_range_0' ? 'simdi' : 'once'][$met] = $r[$i + 1];
                }
            }
        } else {
            $toplam = $ozet[$site] ?? ['simdi' => [], 'once' => []];
        }

        // Günlük seri (site site) - grafik
        $gunluk = [];
        foreach ($ham['gunluk'] as [$tarih, $h, $kisi, $etk]) {
            if (! $h = self::host($h)) {
                continue;
            }
            $t = substr($tarih, 0, 4).'-'.substr($tarih, 4, 2).'-'.substr($tarih, 6, 2);
            $gunluk[$t][$h]['kisi'] = ($gunluk[$t][$h]['kisi'] ?? 0) + $kisi;
            $gunluk[$t][$h]['etk'] = ($gunluk[$t][$h]['etk'] ?? 0) + $etk;
        }
        ksort($gunluk);

        // Tek boyutlu tablolar: seçili site(ler) toplanır
        $topla = function (array $satirlar, int $adet) use ($uygun) {
            $s = [];
            foreach ($satirlar as $r) {
                if (! $uygun(self::host($r[1]))) {
                    continue;
                }
                foreach (array_slice($r, 2) as $i => $v) {
                    $s[$r[0]][$i] = ($s[$r[0]][$i] ?? 0) + $v;
                }
            }
            uasort($s, fn ($a, $b) => $b[0] <=> $a[0]);

            return array_slice($s, 0, $adet, true);
        };
        $kanallar = $topla($ham['kanal'], 20);
        $kaynaklar = $topla($ham['kaynak'], 15);
        $sehirler = $topla($ham['sehir'], 15);
        $cihazlar = $topla($ham['cihaz'], 5);
        $olaylar = $topla($ham['olay'], 25);
        $sayfalar = $topla($ham['sayfa'], 100000);
        [$bolumler, $turler, $kurumlar, $enCokSayfa, $dallar, $doktorlar] = $this->sayfalariAyir($sayfalar, $ilId, $bolumSec);

        // Search Console
        $sc = [];
        $scGunluk = [];
        $sorgular = [];
        foreach ($ham['sc'] as $s => $v) {
            if ($site !== 'hepsi' && $s !== $site) {
                continue;
            }
            $sc[$s] = $v['toplam'];
            foreach ($v['gunluk'] as $r) {
                $scGunluk[$r['keys'][0]]['tiklama'] = ($scGunluk[$r['keys'][0]]['tiklama'] ?? 0) + $r['clicks'];
                $scGunluk[$r['keys'][0]]['gosterim'] = ($scGunluk[$r['keys'][0]]['gosterim'] ?? 0) + $r['impressions'];
                $scGunluk[$r['keys'][0]]['site'][$s] = $r['clicks'];   // grafikte site site tıklama çizgisi
            }
            foreach ($v['sorgu'] as $r) {
                $q = $r['keys'][0];
                $sorgular[$q]['tiklama'] = ($sorgular[$q]['tiklama'] ?? 0) + $r['clicks'];
                $sorgular[$q]['gosterim'] = ($sorgular[$q]['gosterim'] ?? 0) + $r['impressions'];
                $sorgular[$q]['sira'] = min($sorgular[$q]['sira'] ?? 999, $r['position']);
            }
        }
        ksort($scGunluk);
        uasort($sorgular, fn ($a, $b) => $b['tiklama'] <=> $a['tiklama'] ?: $b['gosterim'] <=> $a['gosterim']);

        return compact('ozet', 'toplam', 'gunluk', 'kanallar', 'kaynaklar', 'sehirler', 'cihazlar', 'olaylar',
            'bolumler', 'turler', 'kurumlar', 'enCokSayfa', 'dallar', 'doktorlar', 'sc', 'scGunluk') + ['sorgular' => array_slice($sorgular, 0, 30, true)];
    }

    private function sayfalariAyir(array $sayfalar, ?int $ilId, ?string $bolumSec): array
    {
        $bolumler = [];
        foreach (service_sections() as $s) {
            $bolumler[$s['slug']] = ['baslik' => $s['title'], 'goruntulenme' => 0, 'kisi' => 0];
        }
        $bolumler['doktorlar'] = ['baslik' => 'Doktorlar', 'goruntulenme' => 0, 'kisi' => 0];
        $kurumSayfa = [];
        $doktorSayfa = [];   // 15 Kasım 2026: doktor detay sayfaları (uzmanlık alanı + en çok bakılan doktorlar)
        $enCok = [];
        foreach ($sayfalar as $yol => [$gor, $kisi]) {
            $yol = rtrim((string) strtok((string) $yol, '?'), '/') ?: '/';
            $enCok[$yol] = [($enCok[$yol][0] ?? 0) + $gor, ($enCok[$yol][1] ?? 0) + $kisi];
            if (preg_match('#^/(kurumlar|siteler)/([A-Za-z0-9-]+)#', $yol, $mm)) {
                $kurumSayfa[$mm[2]][0] = ($kurumSayfa[$mm[2]][0] ?? 0) + $gor;
                $kurumSayfa[$mm[2]][1] = ($kurumSayfa[$mm[2]][1] ?? 0) + $kisi;
            } elseif (preg_match('#^/(doktor|dr|uzmanlar)(/|$)#', $yol)) {
                $bolumler['doktorlar']['goruntulenme'] += $gor;
                $bolumler['doktorlar']['kisi'] += $kisi;
                if (preg_match('#^/(doktor|dr)/([A-Za-z0-9-]+)#', $yol, $dm)) {
                    $anahtar = $dm[1].':'.$dm[2];   // /doktor/{uzman slug}, /dr/{doktor sitesi slug}
                    $doktorSayfa[$anahtar][0] = ($doktorSayfa[$anahtar][0] ?? 0) + $gor;
                    $doktorSayfa[$anahtar][1] = ($doktorSayfa[$anahtar][1] ?? 0) + $kisi;
                }
            }
        }
        uasort($enCok, fn ($a, $b) => $b[0] <=> $a[0]);
        $kayit = collect();
        foreach (array_chunk(array_keys($kurumSayfa), 500) as $parca) {
            $kayit = $kayit->merge(DB::table('facilities')->leftJoin('facility_categories as k', 'k.id', '=', 'facilities.facility_category_id')
                ->leftJoin('cities', 'cities.id', '=', 'facilities.city_id')->whereIn('facilities.slug', $parca)
                ->get(['facilities.id', 'facilities.slug', 'facilities.name', 'facilities.city_id', 'cities.name as il', 'k.name as tur', 'k.brand_scope']));
        }
        $turler = [];
        $kurumlar = [];
        foreach ($kayit->unique('slug') as $f) {
            [$gor, $kisi] = $kurumSayfa[$f->slug];
            $bolum = service_section_for_scope((string) $f->brand_scope);
            if ($bolum) {
                $bolumler[$bolum['slug']]['goruntulenme'] += $gor;
                $bolumler[$bolum['slug']]['kisi'] += $kisi;
            }
            $tur = $f->tur ?? 'Diğer';
            $turler[$tur] = ($turler[$tur] ?? 0) + $gor;
            if (($ilId && (int) $f->city_id !== $ilId) || ($bolumSec && ($bolum['slug'] ?? null) !== $bolumSec)) {
                continue;
            }
            $kurumlar[] = ['id' => $f->id, 'ad' => $f->name, 'il' => $f->il, 'tur' => $tur, 'goruntulenme' => $gor, 'kisi' => $kisi];
        }
        arsort($turler);
        usort($kurumlar, fn ($a, $b) => $b['goruntulenme'] <=> $a['goruntulenme']);

        // Doktorlar: uzmanlık alanına göre ilgi + en çok bakılan doktorlar (il filtresi ortak)
        $uzmanSlug = [];
        $siteSlug = [];
        foreach (array_keys($doktorSayfa) as $a) {
            [$tur, $sl] = explode(':', $a, 2);
            $tur === 'doktor' ? $uzmanSlug[] = $sl : $siteSlug[] = $sl;
        }
        $kolon = ['experts.id', 'experts.name', 'experts.title', 'experts.branch_label', 'experts.city_id', 'cities.name as il'];
        $uzmanToplam = [];   // uzman id => [görüntülenme, kişi, kayıt]
        foreach (array_chunk($uzmanSlug, 500) as $parca) {
            foreach (DB::table('experts')->leftJoin('cities', 'cities.id', '=', 'experts.city_id')->whereIn('experts.slug', $parca)->get(array_merge($kolon, ['experts.slug as anahtar'])) as $e) {
                [$g, $k] = $doktorSayfa['doktor:'.$e->anahtar];
                $uzmanToplam[$e->id] = [($uzmanToplam[$e->id][0] ?? 0) + $g, ($uzmanToplam[$e->id][1] ?? 0) + $k, $e];
            }
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('expert_websites')) {
            foreach (array_chunk($siteSlug, 500) as $parca) {
                foreach (DB::table('expert_websites as w')->join('experts', 'experts.id', '=', 'w.expert_id')->leftJoin('cities', 'cities.id', '=', 'experts.city_id')
                    ->whereIn('w.slug', $parca)->get(array_merge($kolon, ['w.slug as anahtar'])) as $e) {
                    [$g, $k] = $doktorSayfa['dr:'.$e->anahtar];
                    $uzmanToplam[$e->id] = [($uzmanToplam[$e->id][0] ?? 0) + $g, ($uzmanToplam[$e->id][1] ?? 0) + $k, $e];
                }
            }
        }
        $dallar = [];
        $doktorlar = [];
        foreach ($uzmanToplam as [$gor, $kisi, $e]) {
            $dal = $e->branch_label ?: 'Diğer';
            $dallar[$dal] = ($dallar[$dal] ?? 0) + $gor;
            if ($ilId && (int) $e->city_id !== $ilId) {
                continue;
            }
            $doktorlar[] = ['id' => $e->id, 'ad' => trim(($e->title ? $e->title.' ' : '').$e->name), 'il' => $e->il, 'dal' => $dal, 'goruntulenme' => $gor, 'kisi' => $kisi];
        }
        arsort($dallar);
        // Türe göre ilgi grafiğinde doktorlar da bir satır (doktor detay sayfaları toplamı)
        if ($dallar) {
            $turler['Doktorlar (tüm uzmanlık alanları)'] = array_sum($dallar);
            arsort($turler);
        }
        usort($doktorlar, fn ($a, $b) => $b['goruntulenme'] <=> $a['goruntulenme']);

        return [$bolumler, $turler, array_slice($kurumlar, 0, 25), array_slice($enCok, 0, 25, true), array_slice($dallar, 0, 15, true), array_slice($doktorlar, 0, 25)];
    }
}
