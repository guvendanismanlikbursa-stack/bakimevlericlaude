<?php

namespace App\Support;

use App\Http\Middleware\BotKalkani;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * 1 Kasım 2026: kullanıcının talebi - veri kazıyan botlar için uyarı ve admin panelinden engelleme.
 * Ağ bilgisi (AS numarası, firma, adres blokları) RIPE'ın herkese açık kayıtlarından (stat.ripe.net) alınır.
 */
class BotGozcu
{
    /** Engellenmesi siteyi bozacak ağlar: arama motorları, önizleme servisleri ve Türkiye'deki internet sağlayıcıları. */
    public const KORUMALI = [
        15169 => 'Google (Googlebot)',
        8075 => 'Microsoft (Bing)',
        13238 => 'Yandex',
        714 => 'Apple',
        6185 => 'Apple',
        32934 => 'Meta (Facebook / WhatsApp önizleme)',
        13335 => 'Cloudflare (WARP kullanan gerçek kişiler)',
        9121 => 'Türk Telekom',
        47331 => 'Türk Telekom',
        20978 => 'Türk Telekom Mobil',
        16135 => 'Turkcell',
        34984 => 'Turkcell Superonline',
        15897 => 'Vodafone Türkiye',
        8386 => 'Vodafone Net',
        12735 => 'TurkNet',
    ];

    private const ONBELLEK = 'bot_kalkani:db_aglar:v1';

    /** Admin panelinden eklenmiş etkin engeller, kalkanın kullandığı biçimde (önbellekli). */
    public static function dbListesi(): array
    {
        try {
            return Cache::rememberForever(self::ONBELLEK, function () {
                return DB::table('engelli_aglar')->where('aktif', true)->get()->map(fn ($r) => [
                    'ad' => $r->firma.($r->asn ? ' (AS'.$r->asn.')' : ''),
                    'v4' => json_decode((string) $r->v4, true) ?: [],
                    'v6' => json_decode((string) $r->v6, true) ?: [],
                ])->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function onbellegiTemizle(): void
    {
        Cache::forget(self::ONBELLEK);
    }

    /** Ağın (/24) AS numarası ve firması; önce kayıtlı bilgi, yoksa RIPE'tan sorulur. */
    public static function agBilgisi(string $ag, string $ornekIp, bool $sorgula = true): array
    {
        $kayit = DB::table('ag_bilgileri')->where('ag', $ag)->first();
        if ($kayit && \Illuminate\Support\Carbon::parse($kayit->updated_at)->gt(now()->subDays(30))) {
            return ['asn' => $kayit->asn ? (int) $kayit->asn : null, 'firma' => $kayit->firma, 'onek' => $kayit->onek];
        }
        if (! $sorgula) {
            return ['asn' => null, 'firma' => null, 'onek' => null];
        }
        $bilgi = self::ipBilgisi($ornekIp);
        DB::table('ag_bilgileri')->updateOrInsert(['ag' => $ag], $bilgi + ['created_at' => now(), 'updated_at' => now()]);

        return $bilgi;
    }

    /** RIPE: IP'nin bağlı olduğu AS numarası, adres bloğu ve firma adı. */
    public static function ipBilgisi(string $ip): array
    {
        $bilgi = ['asn' => null, 'firma' => null, 'onek' => null];
        try {
            $ni = Http::timeout(8)->get('https://stat.ripe.net/data/network-info/data.json', ['resource' => $ip])->json('data');
            $bilgi['asn'] = isset($ni['asns'][0]) ? (int) $ni['asns'][0] : null;
            $bilgi['onek'] = $ni['prefix'] ?? null;
            if ($bilgi['asn']) {
                $bilgi['firma'] = self::firmaAdi($bilgi['asn']);
            }
        } catch (\Throwable $e) {
            // bilgi alınamadıysa boş döner; bir sonraki saatte tekrar denenir
        }

        return $bilgi;
    }

    public static function firmaAdi(int $asn): ?string
    {
        try {
            $ad = Http::timeout(8)->get('https://stat.ripe.net/data/as-overview/data.json', ['resource' => 'AS'.$asn])->json('data.holder');

            return $ad ? mb_substr((string) $ad, 0, 150) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** AS numarasının duyurduğu tüm adres blokları: ['v4' => [[bas, son], ...], 'v6' => [cidr, ...]]. */
    public static function asnBloklari(int $asn): array
    {
        $onekler = Http::timeout(15)->get('https://stat.ripe.net/data/announced-prefixes/data.json', ['resource' => 'AS'.$asn])->throw()->json('data.prefixes') ?: [];
        $v4 = [];
        $v6 = [];
        foreach ($onekler as $o) {
            $cidr = (string) ($o['prefix'] ?? '');
            if (str_contains($cidr, ':')) {
                $v6[$cidr] = true;
            } elseif ($r = self::v4Aralik($cidr)) {
                $v4[] = $r;
            }
        }
        usort($v4, fn ($a, $b) => $a[0] <=> $b[0]);
        $birlesik = [];
        foreach ($v4 as [$bas, $son]) {
            $n = count($birlesik);
            if ($n && $bas <= $birlesik[$n - 1][1] + 1) {
                $birlesik[$n - 1][1] = max($birlesik[$n - 1][1], $son);
            } else {
                $birlesik[] = [$bas, $son];
            }
        }

        return ['v4' => $birlesik, 'v6' => array_keys($v6)];
    }

    public static function v4Aralik(string $cidr): ?array
    {
        [$net, $bit] = array_pad(explode('/', $cidr), 2, 32);
        $n = ip2long($net);
        if ($n === false || ! is_numeric($bit) || $bit < 0 || $bit > 32) {
            return null;
        }
        $boyut = 2 ** (32 - (int) $bit);
        $bas = $n - ($n % $boyut);

        return [$bas, $bas + $boyut - 1];
    }

    /**
     * Engel ekler. $asn verilirse firmanın tüm blokları, yoksa yalnızca $ag (/24 veya /64) engellenir.
     * Korumalı ağlar engellenemez. Dönen: [başarılı mı, mesaj].
     */
    public static function engelle(?int $asn, ?string $ag, ?string $firma, string $ekleyen): array
    {
        if ($asn && isset(self::KORUMALI[$asn])) {
            return [false, self::KORUMALI[$asn].' engellenemez: gerçek ziyaretçiler veya arama motorları bu ağdan gelir.'];
        }
        if ($asn && DB::table('engelli_aglar')->where('aktif', true)->where('asn', $asn)->exists()) {
            return [false, 'AS'.$asn.' zaten engelli.'];
        }
        if ($asn) {
            $bloklar = self::asnBloklari($asn);
            if (! $bloklar['v4'] && ! $bloklar['v6']) {
                return [false, 'AS'.$asn.' için adres bloğu bulunamadı.'];
            }
            $firma = $firma ?: (self::firmaAdi($asn) ?: 'AS'.$asn);
            $kapsam = 'asn';
        } elseif ($ag && ($r = self::v4Aralik($ag))) {
            $bloklar = ['v4' => [$r], 'v6' => []];
            $kapsam = 'ag';
            $firma = $ag.($firma ? ' · '.$firma : '');
        } elseif ($ag && str_contains($ag, ':')) {
            $bloklar = ['v4' => [], 'v6' => [$ag]];
            $kapsam = 'ag';
            $firma = $ag.($firma ? ' · '.$firma : '');
        } else {
            return [false, 'Engellenecek ağ anlaşılamadı.'];
        }
        DB::table('engelli_aglar')->insert([
            'asn' => $kapsam === 'asn' ? $asn : null, 'firma' => mb_substr($firma, 0, 150), 'kapsam' => $kapsam,
            'v4' => json_encode($bloklar['v4']), 'v6' => json_encode($bloklar['v6']),
            'ekleyen' => mb_substr($ekleyen, 0, 100), 'aktif' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        self::onbellegiTemizle();

        return [true, $firma.' engellendi ('.count($bloklar['v4']).' adres bloğu). Etkisi hemen başlar.'];
    }

    /** Saatlik gözcü: bugünkü şüpheli ağları kaydeder ve gerekirse adminlere uyarı gönderir. */
    public static function calistir(?\Closure $yaz = null): array
    {
        $yaz ??= fn ($m) => null;
        $tarih = now()->toDateString();
        $aglar = TrafikAnaliz::aglar($tarih);
        uasort($aglar, fn ($a, $b) => ($b['supheli'] + $b['bot']) <=> ($a['supheli'] + $a['bot']));
        $sorgu = 0;
        $kaydedilen = 0;
        foreach (array_slice($aglar, 0, 60, true) as $ag => $d) {
            if ($d['supheli'] + $d['bot'] < 200) {
                break;
            }
            $bilgi = self::agBilgisi($ag, $d['ornek_ip'], $sorgu < 25);
            $sorgu++;
            DB::table('supheli_aglar')->updateOrInsert(['tarih' => $tarih, 'ag' => $ag], [
                'asn' => $bilgi['asn'], 'firma' => $bilgi['firma'], 'istek' => $d['istek'], 'supheli' => $d['supheli'], 'bot' => $d['bot'],
                'insan' => $d['insan'], 'engellenen' => $d['engellenen'], 'ip_sayisi' => $d['ip'], 'markalar' => implode(',', $d['markalar']),
                'ornek_ua' => mb_substr($d['ua'], 0, 255), 'updated_at' => now(), 'created_at' => now(),
            ]);
            $kaydedilen++;
        }
        $yaz("şüpheli ağ kaydı: $kaydedilen");

        $uyarilar = [];
        // 1) Sitelere giriş sayısı olağan dışı (bot dahil sayaç, önceki 7 günün ortalamasının 2 katı).
        foreach (config('brands.brands') as $slug => $marka) {
            $bugun = (int) DB::table('site_visits')->where('brand', $slug)->whereDate('visit_date', $tarih)->value('count');
            $ort = (float) DB::table('site_visits')->where('brand', $slug)->whereBetween('visit_date', [now()->subDays(7)->toDateString(), now()->subDay()->toDateString()])->avg('count');
            if ($bugun >= 2000 && $ort > 0 && $bugun >= 2 * $ort) {
                $uyarilar["marka:$slug:$tarih"] = [
                    'Olağan dışı trafik: '.($marka['name'] ?? $slug),
                    sprintf('Bugün şu ana kadar %s giriş var; son 7 gün ortalaması %s. Büyük kısmı bot olabilir. Şüpheli ağları görmek ve engellemek için dokunun.', number_format($bugun, 0, ',', '.'), number_format($ort, 0, ',', '.')),
                ];
            }
        }
        // 2) Tek bir firmadan (AS) engellenmemiş yoğun şüpheli istek.
        $engelliAsn = DB::table('engelli_aglar')->where('aktif', true)->whereNotNull('asn')->pluck('asn')->map(fn ($a) => (int) $a)->all();
        // Kalıcı listedeki (config/bot_kalkani.php) firmalar da zaten engelli sayılır.
        foreach ((array) config('bot_kalkani.engelli_aglar', []) as $g) {
            if (preg_match('/\(AS(\d+)\)/', (string) ($g['ad'] ?? ''), $m)) {
                $engelliAsn[] = (int) $m[1];
            }
        }
        $gruplar = DB::table('supheli_aglar')->where('tarih', $tarih)->whereNotNull('asn')
            ->selectRaw('asn, max(firma) as firma, sum(supheli + bot) as sayi, sum(engellenen) as engellenen, sum(ip_sayisi) as ip, count(*) as ag')
            ->groupBy('asn')->get();
        foreach ($gruplar as $g) {
            $acik = (int) $g->sayi - (int) $g->engellenen;
            if ($acik >= 1500 && ! isset(self::KORUMALI[(int) $g->asn]) && ! in_array((int) $g->asn, $engelliAsn, true)) {
                $uyarilar["asn:{$g->asn}:$tarih"] = [
                    'Şüpheli bot: '.($g->firma ?: 'AS'.$g->asn),
                    sprintf('%s (AS%s) bugün %s şüpheli sayfa isteği yaptı (%s ağ, %s farklı IP). Engellemek için dokunun.', $g->firma ?: 'Bilinmeyen firma', $g->asn, number_format($acik, 0, ',', '.'), $g->ag, $g->ip),
                ];
            }
        }
        $gonderilen = [];
        foreach ($uyarilar as $anahtar => [$baslik, $metin]) {
            if (DB::table('bot_alarmlari')->where('anahtar', $anahtar)->exists()) {
                continue;
            }
            DB::table('bot_alarmlari')->insert(['anahtar' => $anahtar, 'baslik' => $baslik, 'metin' => $metin, 'created_at' => now(), 'updated_at' => now()]);
            \App\Models\Admin::all()->each(function ($admin) use ($baslik, $metin) {
                try {
                    notify_user($admin, 'bot_alarm', $baslik, $metin, []);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Bot uyarısı gönderilemedi: '.$e->getMessage());
                }
            });
            $gonderilen[] = $baslik;
            $yaz("UYARI: $baslik");
        }

        return $gonderilen;
    }
}
