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

    /**
     * GA'nın "dateHour" değerini (YYYYMMDDHH) Türkiye saatine çevirir.
     * 17 Kasım 2026: Analytics mülkünün saat dilimi değişikliği 17 Kasım'dan itibaren işledi.
     * - 16 Kasım dahil öncesi: ABD Pasifik saatiyle geliyor.
     * - 17 Kasım'dan sonrası: Türkiye saatiyle geliyor.
     * Kanıt: 17 Kasım 15:30'da GA'nın en son saati "1712" idi; ABD Pasifik olsaydı Türkiye'de 23:00 olurdu, yani gelecek.
     * Ham veride 1623'ten (Pasifik = TR 17 Kasım 10:00) sonra doğrudan 1711 geliyor, aradaki boşluk gerçek değil.
     * Tümü Pasifik sayıldığında son 24 saat grafiğinde 11:00-21:00 arası sahte bir boşluk oluşuyordu.
     */
    public static function gaSaati(string $dateHour): \Illuminate\Support\Carbon
    {
        $degisim = (string) config('services.google_analytics.timezone_degisim', '2026111700');
        $dilim = $dateHour >= $degisim ? config('app.timezone') : (config('services.google_analytics.timezone') ?: config('app.timezone'));

        return \Illuminate\Support\Carbon::createFromFormat('YmdH', $dateHour, $dilim)->setTimezone(config('app.timezone'));
    }

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
            // 16 Kasım 2026: GA saatleri fiilen ABD Pasifik saatinde (bkz. config/services.php) → günlük grafik saat saat çekilip
            // Türkiye gününe göre toplanır (hazirla()).
            // 18 Kasım 2026 (istatistik denetimi): eskiden saatlik "kişi" toplanıyordu - aynı kişi gün içinde birkaç saatte
            // gelince birkaç kez sayılıyor, grafik toplamı üstteki Ziyaretçi kartını aşıyordu. Artık ZİYARET (oturum) sayılır;
            // oturum saatlere bölünmez, toplamı doğrudur ve üstteki "Ziyaret" kartıyla tutar.
            // Saatlik veride saat sınırını geçen ziyaret iki saatte de sayılıyordu (7 günde kart 1.942, grafik 2.063);
            // bu yüzden günlük veri doğrudan Google'ın günlük kırılımından alınır, toplamı kartla aynı aralıktır.
            'gunluk' => $this->rapor(['dateRanges' => $bir,
                'dimensions' => [['name' => 'date'], ['name' => 'hostName']], 'metrics' => $m(['sessions', 'engagedSessions']), 'limit' => 100000]),
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
        $satirlar = $this->g->rapor(['dateRanges' => [['startDate' => '3daysAgo', 'endDate' => 'today']],
            'dimensions' => [['name' => 'dateHour'], ['name' => 'hostName'], ['name' => 'country']],
            'metrics' => array_map(fn ($m) => ['name' => $m], $met), 'limit' => 20000]);
        // GA saatleri Türkiye saatine çevrilir (bkz. gaSaati). Analytics veriyi saatler sonra işler;
        // pencere "şimdi"ye değil Google'ın işlediği en son saate göre kurulur.
        foreach ($satirlar as $i => $r) {
            $satirlar[$i][0] = self::gaSaati($r[0])->format('YmdH');
        }
        $sonSaat = null;
        foreach ($satirlar as $r) {
            if (self::host($r[1]) && ($sonSaat === null || $r[0] > $sonSaat)) {
                $sonSaat = $r[0];
            }
        }
        // 18 Kasım 2026: pencere her zaman şu anki saatte biter; Google'ın henüz işlemediği saatler
        // grafikte "henüz işlenmedi" olarak gösterilir (önceden hiç çizilmiyordu, veri donmuş sanılıyordu).
        $bitis = now()->startOfHour();
        $saatler = [];
        for ($i = 23; $i >= 0; $i--) {
            $saatler[$bitis->copy()->subHours($i)->format('YmdH')] = 0;
        }
        $ilk = array_key_first($saatler);
        $siteler = [];
        foreach (GoogleVeriServisi::SITELER as $s) {
            $siteler[$s] = ['kisi' => 0, 'oturum' => 0, 'etk' => 0, 'sayfa' => 0, 'sure' => 0, 'bot' => 0, 'saat' => $saatler];
        }
        foreach ($satirlar as $r) {
            [$saat, $host, $ulke] = $r;
            $h = self::host($host);
            if (! $h || $saat < $ilk || $saat > $sonSaat) {
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
            $siteler[$h]['saat'][$saat] += $r[3];
        }
        // 18 Kasım 2026: kullanıcının talebi - Google aramasından gelen tıklamalar (Search Console) da saat saat;
        // Google'ın kendi Search Console ekranındaki rakamla aynı kaynaktır. Hata olursa kart yalnız Analytics ile çizilir.
        $scEksik = null;
        $scHam = [];
        $scVar = false;
        foreach ($siteler as $h => $x) {
            $siteler[$h] += ['tik' => null, 'gos' => null, 'tik_saat' => $saatler];
            try {
                $sc = $this->g->aramaKonsoluSaatlik($h, now()->subDays(3)->toDateString(), now()->toDateString());
            } catch (\Throwable $e) {
                report($e);

                continue;
            }
            $scVar = true;
            $scEksik = $sc['eksik'] && (! $scEksik || $sc['eksik'] < $scEksik) ? $sc['eksik'] : $scEksik;
            $siteler[$h]['tik'] = 0;
            $siteler[$h]['gos'] = 0;
            $siteler[$h]['sc_ham'] = $sc['saatler'];
            $scHam[$h] = $sc['saatler'];   // grafik için (sc_ham aşağıda siliniyor)
            foreach ($sc['saatler'] as $k => $v) {
                if (array_key_exists($k, $saatler)) {
                    $siteler[$h]['tik_saat'][$k] = $v['tik'];
                    $siteler[$h]['tik'] += $v['tik'];
                    $siteler[$h]['gos'] += $v['gos'];
                }
            }
            $siteler[$h]['tik_son'] = $sc['saatler'] ? max(array_map('strval', array_keys($sc['saatler']))) : null;
        }
        $scSon = null;
        foreach ($siteler as $x) {
            if (! empty($x['tik_son']) && ($scSon === null || $x['tik_son'] > $scSon)) {
                $scSon = $x['tik_son'];
            }
        }
        // 18 Kasım 2026: kullanıcının şikâyeti - "Google'dan 35 tıklama, siteye giren 54 kişi, nasıl oluyor?".
        // Gerçek sebep: (1) iki sayı farklı saatleri kapsıyordu (Analytics 2 saat daha ileri), (2) Analytics sayısı
        // Google dışı girişleri de (Bing/Yandex, doğrudan, yapay zekâ) içeriyordu. Artık kart sayıları İKİ kaynağın da
        // yayınladığı son saate kadar (ortak pencere) hesaplanır ve siteye girişler kaynağına göre ayrılır.
        // Ölçüm (18 Kasım 2026, canlı veri): Search Console'un KESİNLEŞTİRDİĞİ saatlerde iki kaynak birebir tutuyor
        // (ör. 30 tıklama / 29 Google girişi, 52 / 52). Fark yalnız Google'ın hâlâ saydığı son saatlerden geliyordu;
        // bu yüzden kart sayıları Search Console'un kesinleştirdiği son saate kadar hesaplanır.
        $scKesinSon = $scEksik ? \Illuminate\Support\Carbon::createFromFormat('YmdH', $scEksik, config('app.timezone'))->subHour()->format('YmdH') : $scSon;
        // 19 Kasım 2026 düzeltmesi: Search Console bazen "kesinleşmemiş ilk saat"i 1 gün öncesi olarak veriyor
        // (ör. 18.11 11:00). O saat 24 saatlik pencerenin başından önceyse kart sayıları 0'a düşüyordu.
        // Kesin saat pencere içinde değilse Google'ın yayınladığı son saat kullanılır.
        $ortakKesin = $scKesinSon && (string) $scKesinSon >= (string) $ilk;
        $ortakSon = $sonSaat ? min(array_filter([$sonSaat, $scSon, $ortakKesin ? $scKesinSon : null])) : null;
        if ($ortakSon && (string) $ortakSon < (string) $ilk) {
            $ortakSon = null;   // ortak pencere boş: kartlar eski (tam 24 saat) sayılara döner
        }
        $kanalSatir = [];
        try {
            $kanalSatir = $this->g->rapor(['dateRanges' => [['startDate' => '3daysAgo', 'endDate' => 'today']],
                'dimensions' => [['name' => 'dateHour'], ['name' => 'hostName'], ['name' => 'country'], ['name' => 'sessionDefaultChannelGroup']],
                'metrics' => [['name' => 'sessions']], 'limit' => 20000]);
        } catch (\Throwable $e) {
            report($e);
        }
        foreach ($siteler as $h => $x) {
            $siteler[$h]['o_tik'] = $x['tik'] === null ? null : array_sum(array_filter($x['tik_saat'], fn ($k) => (string) $k <= (string) $ortakSon, ARRAY_FILTER_USE_KEY));
            $siteler[$h]['o_giris'] = 0;
            $siteler[$h]['o_kanal'] = ['arama' => 0, 'dogrudan' => 0, 'diger' => 0];
        }
        foreach ($kanalSatir as $r) {
            [$saat, $host, $ulke, $kanal, $oturum] = $r;
            $saat = self::gaSaati($saat)->format('YmdH');
            $h = self::host($host);
            if (! $h || $saat < $ilk || ! $ortakSon || $saat > $ortakSon || in_array($ulke, self::HARIC_ULKELER, true)) {
                continue;
            }
            $tur = $kanal === 'Organic Search' ? 'arama' : ($kanal === 'Direct' ? 'dogrudan' : 'diger');
            $siteler[$h]['o_giris'] += (int) $oturum;
            $siteler[$h]['o_kanal'][$tur] += (int) $oturum;
        }
        // 20 Kasım 2026: kullanıcının şikâyeti - "Search Console 192 diyor, bizimki 143; neden farklı?".
        // Ölçüm (20.11 15:23 canlı): Search Console ekranındaki "24 saat" = Google'ın yayınladığı son saat dahil
        // geriye 23 saat (19.11 13:00 - 20.11 11:00 toplamı tam 192 tıklama / 13.581 görünme, ekranla birebir).
        // Kartın büyük rakamları artık bu pencereden hesaplanır; siteye giriş de aynı saatler için kendi sayacımızdan.
        $sc24Son = $scSon;
        $sc24Bas = $scSon ? \Illuminate\Support\Carbon::createFromFormat('YmdH', (string) $scSon, config('app.timezone'))->subHours(22)->format('YmdH') : null;
        foreach ($siteler as $h => $x) {
            $siteler[$h]['sc24'] = null;
            if ($sc24Son && isset($x['sc_ham'])) {
                $t = ['tik' => 0, 'gos' => 0];
                foreach ($x['sc_ham'] as $k => $v) {
                    if ((string) $k >= $sc24Bas && (string) $k <= (string) $sc24Son) {
                        $t['tik'] += $v['tik'];
                        $t['gos'] += $v['gos'];
                    }
                }
                $siteler[$h]['sc24'] = $t;
            }
            unset($siteler[$h]['sc_ham']);
        }
        // 19 Kasım 2026: kendi çerezsiz giriş sayacımız, kartlarla aynı saatler için.
        $sayacIlk = null;
        try {
            $sayacIlk = \App\Support\GirisSayaci::ilkSaat();
            $sayac = $ortakSon ? \App\Support\GirisSayaci::aralik((string) $ilk, (string) $ortakSon) : [];
            $sayac24 = $sc24Son ? \App\Support\GirisSayaci::aralik((string) $sc24Bas, (string) $sc24Son) : [];
            foreach ($siteler as $h => $x) {
                $siteler[$h]['s_giris'] = $sayac[$h] ?? ['toplam' => 0, 'arama' => 0, 'dogrudan' => 0, 'diger' => 0];
                $siteler[$h]['s24_giris'] = $sayac24[$h] ?? ['toplam' => 0, 'arama' => 0, 'dogrudan' => 0, 'diger' => 0];
            }
        } catch (\Throwable $e) {
            report($e);
        }
        // 24 Kasım 2026: kullanıcının talebi - "son 24 saati göstersin grafikler". Grafikler artık şu anki saate kadar
        // boş ("henüz gelmedi") sütunlarla değil, Google'ın gönderdiği son saatten geriye tam 24 saat çizilir:
        // arama grafiği Search Console'un son saatinden, giriş grafiği Analytics'in son saatinden. Kart sayıları değişmez.
        $grafikPencere = function (?string $son): array {
            $p = [];
            if ($son) {
                $b = \Illuminate\Support\Carbon::createFromFormat('YmdH', $son, config('app.timezone'));
                for ($i = 23; $i >= 0; $i--) {
                    $p[$b->copy()->subHours($i)->format('YmdH')] = 0;
                }
            }

            return $p;
        };
        $gaPencere = $grafikPencere($sonSaat ? (string) $sonSaat : null);
        $scPencere = $grafikPencere($scSon ? (string) $scSon : null);
        foreach ($siteler as $h => $x) {
            $siteler[$h]['g_saat'] = $gaPencere;
            $siteler[$h]['g_tik'] = $scPencere;
            $siteler[$h]['g_gos'] = $scPencere;
            $siteler[$h]['g_sira'] = $scPencere;   // saatlik ortalama sıra (24 Kasım 2026)
            $siteler[$h]['g_etk'] = $gaPencere;    // saatlik etkileşimli ziyaret (24 Kasım 2026)
            foreach ($scHam[$h] ?? [] as $k => $v) {
                if (array_key_exists((string) $k, $scPencere)) {
                    $siteler[$h]['g_tik'][(string) $k] = $v['tik'];
                    $siteler[$h]['g_gos'][(string) $k] = $v['gos'];
                    $siteler[$h]['g_sira'][(string) $k] = round((float) ($v['sira'] ?? 0), 1);
                }
            }
        }
        foreach ($satirlar as $r) {
            [$saat, $host, $ulke] = $r;
            $h = self::host($host);
            if ($h && array_key_exists((string) $saat, $gaPencere) && ! in_array($ulke, self::HARIC_ULKELER, true)) {
                $siteler[$h]['g_saat'][(string) $saat] += $r[3];
                $siteler[$h]['g_etk'][(string) $saat] += $r[5];
            }
        }
        $veri = ['tarih' => now()->toDateTimeString(), 'bas' => $ilk, 'sayac_ilk' => $sayacIlk, 'ortak_son' => $ortakSon, 'ortak_kesin' => $ortakKesin, 'sc24_bas' => $sc24Bas, 'sc24_son' => $sc24Son, 'kanal_var' => $kanalSatir !== [], 'sc_var' => $scVar, 'sc_son' => $scSon, 'sc_eksik' => $scEksik, 'son' => $sonSaat ?? $bitis->format('YmdH'),
            'gecikme' => $sonSaat ? max(0, (int) floor(\Illuminate\Support\Carbon::createFromFormat('YmdH', $sonSaat, config('app.timezone'))->addHour()->diffInHours(now(), true))) : null, 'siteler' => $siteler];
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

    /**
     * 24 Kasım 2026: kullanıcının talebi - "sitenin durumunu günlük, haftalık, aylık, 3 aylık ve yıllık anlık görebilmek",
     * Search Console'daki Performans grafiği gibi çizgi grafik, 3 site ayrı çizgi. Yeni Google sorgusu yok: 1 yıllık
     * önbellekteki günlük tıklama (Search Console) ve günlük ziyaret (Analytics) dilimlenir; 24 saat son24'ten gelir.
     */
    public function durumGrafigi(?array $son24): ?array
    {
        $ham = $this->ham(365);
        if (! $ham) {
            return null;
        }
        $siteler = GoogleVeriServisi::SITELER;
        $tarihler = function (string $bas, string $son): array {
            $l = [];
            for ($t = \Illuminate\Support\Carbon::parse($bas); $t->toDateString() <= $son; $t->addDay()) {
                $l[] = $t->toDateString();
            }

            return $l;
        };
        // Günlük ham değerler: [tarih][site] = [tik, gos, sira*gos] / [oturum, etk]
        $sc = [];
        foreach ($siteler as $s) {
            foreach ($ham['sc'][$s]['gunluk'] ?? [] as $r) {
                $sc[$r['keys'][0]][$s] = [(int) ($r['clicks'] ?? 0), (int) ($r['impressions'] ?? 0), (float) ($r['position'] ?? 0) * (int) ($r['impressions'] ?? 0)];
            }
        }
        $ga = [];
        foreach ($ham['gunluk'] ?? [] as [$tarih, $h, $oturum, $etk]) {
            if (! $h = self::host($h)) {
                continue;
            }
            $g = strlen((string) $tarih) === 10 ? self::gaSaati((string) $tarih)->toDateString() : substr($tarih, 0, 4).'-'.substr($tarih, 4, 2).'-'.substr($tarih, 6, 2);
            $ga[$g][$h][0] = ($ga[$g][$h][0] ?? 0) + (int) $oturum;
            $ga[$g][$h][1] = ($ga[$g][$h][1] ?? 0) + (int) $etk;
        }
        // Bir dönem için ölçü: günlük seri + dönem toplamı (oran ve sıra toplanmaz, dönem ortalaması alınır)
        $olcu = function (array $gunler, array $kaynak, string $m, ?string $bicim) use ($siteler): array {
            $seri = [];
            $toplam = [];
            foreach ($siteler as $s) {
                $tt = [0, 0, 0];
                foreach ($gunler as $g) {
                    $v = $kaynak[$g][$s] ?? [0, 0, 0];
                    $tt = [$tt[0] + $v[0], $tt[1] + ($v[1] ?? 0), $tt[2] + ($v[2] ?? 0)];
                    $seri[$s][] = match ($m) {
                        'tik', 'giris' => $v[0],
                        'gos', 'etk' => $v[1] ?? 0,
                        'ctr' => $v[1] ? round($v[0] / $v[1] * 100, 2) : null,
                        'sira' => $v[1] ? round($v[2] / $v[1], 1) : null,
                    };
                }
                $toplam[$s] = match ($m) {
                    'tik', 'giris' => $tt[0],
                    'gos', 'etk' => $tt[1],
                    'ctr' => $tt[1] ? round($tt[0] / $tt[1] * 100, 2) : null,
                    'sira' => $tt[1] ? round($tt[2] / $tt[1], 1) : null,
                };
            }

            if ($bicim === null) {   // saatlik: etiket/aralık çağıran tarafından yazılır
                return ['seri' => $seri, 'toplam' => $toplam];
            }

            return ['etiket' => array_map(fn ($g) => \Illuminate\Support\Carbon::parse($g)->format($bicim), $gunler),
                'aralik' => $gunler ? \Illuminate\Support\Carbon::parse($gunler[0])->format('d.m.Y').' – '.\Illuminate\Support\Carbon::parse(end($gunler))->format('d.m.Y') : '',
                'seri' => $seri, 'toplam' => $toplam];
        };
        $scGun = $tarihler($ham['sc_bas'], $ham['sc_son']);
        $gaGun = $tarihler($ham['bas'], $ham['son']);
        $donem = [];
        foreach ([7, 30, 90, 180, 365] as $n) {   // 24 Kasım 2026: 6 ay eklendi (kullanıcının talebi)
            $b = $n > 90 ? 'd.m.Y' : 'd.m';
            foreach (['tik', 'gos', 'ctr', 'sira'] as $m) {
                $donem[$n][$m] = $olcu(array_slice($scGun, -$n), $sc, $m, $b);
            }
            foreach (['giris', 'etk'] as $m) {
                $donem[$n][$m] = $olcu(array_slice($gaGun, -$n), $ga, $m, $b);
            }
        }
        // Son 24 saat: saat saat (yukarıdaki saatlik grafiklerle aynı pencere). Analytics saatlik dizisi kişi sayar.
        // 24 Kasım 2026: kullanıcının şikâyeti - 24 saatte oran / sıra / etkileşimli ziyaret düğmeleri pasifti; artık
        // saatlik tıklama-gösterimden oran, Google'ın saatlik sırasından (gösterimle ağırlıklı) sıra, Analytics'ten etkileşim.
        $x = $son24['siteler'] ?? [];
        $saatSeri = function (string $anahtar, string $sabit) use ($x, $siteler): ?array {
            $ilk = $x[$siteler[0]][$anahtar] ?? null;
            if (! $ilk) {
                return null;
            }
            $saatler = array_map('strval', array_keys($ilk));
            $kaynak = [];
            foreach ($siteler as $s) {
                foreach ($saatler as $k) {
                    $kaynak[$k][$s] = match ($sabit) {
                        'sc' => [(int) ($x[$s]['g_tik'][$k] ?? 0), (int) ($x[$s]['g_gos'][$k] ?? 0), (float) ($x[$s]['g_sira'][$k] ?? 0) * (int) ($x[$s]['g_gos'][$k] ?? 0)],
                        'ga' => [(int) ($x[$s]['g_saat'][$k] ?? 0), (int) ($x[$s]['g_etk'][$k] ?? 0)],
                    };
                }
            }

            return [$saatler, $kaynak];
        };
        $saatOlculer = ['tik' => ['g_tik', 'sc'], 'gos' => ['g_gos', 'sc'], 'ctr' => ['g_gos', 'sc'], 'sira' => ['g_sira', 'sc'], 'giris' => ['g_saat', 'ga'], 'etk' => ['g_etk', 'ga']];
        foreach ($saatOlculer as $m => [$anahtar, $tur]) {
            if (! $s = $saatSeri($anahtar, $tur)) {
                continue;
            }
            [$saatler, $kaynak] = $s;
            $o = $olcu($saatler, $kaynak, $m, null);
            $o['etiket'] = array_map(fn ($k) => substr($k, 8, 2).':00', $saatler);
            $o['aralik'] = \Illuminate\Support\Carbon::createFromFormat('YmdH', $saatler[0])->format('d.m H:00').' – '.\Illuminate\Support\Carbon::createFromFormat('YmdH', end($saatler))->addHour()->format('d.m H:00');
            $donem[1][$m] = $o;
        }
        ksort($donem);

        return $donem;
    }

    /** Önbellekteki veri (yoksa en son başarılı veri; o da yoksa null). */
    public function ham(int $gun): ?array
    {
        return Cache::get(self::anahtar($gun)) ?? Cache::get('google_ham_'.$gun.'_son');
    }

    public static function host(string $h): ?string
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
            if (strlen($tarih) === 10) {
                $t = self::gaSaati($tarih)->toDateString();
                if ($t < $ham['bas'] || $t > $ham['son']) {
                    continue;
                }
            } else {
                $t = substr($tarih, 0, 4).'-'.substr($tarih, 4, 2).'-'.substr($tarih, 6, 2);
            }
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
                // 18 Kasım 2026 (denetim): birden çok sitede çıkan kelimede sıra, en iyi site değil gösterime göre ağırlıklı ortalama.
                $sorgular[$q]['sira_top'] = ($sorgular[$q]['sira_top'] ?? 0) + $r['position'] * $r['impressions'];
                $sorgular[$q]['sira'] = $sorgular[$q]['gosterim'] > 0 ? $sorgular[$q]['sira_top'] / $sorgular[$q]['gosterim'] : $r['position'];
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
