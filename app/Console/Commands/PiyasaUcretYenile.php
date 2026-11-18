<?php

namespace App\Console\Commands;

use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// 18 Kasım 2026: kullanıcının kararı - özel anaokulu/kreş aylık ücret özeti (il/ilçe) her yıl OCAK ve TEMMUZ'da
// kendiliğinden yenilenir. Kaynak: çevrimiçi ilan edilmiş aylık ücretler (okul.com.tr anaokulu listeleri).
// Okul ADI ve tek tek fiyat SAKLANMAZ; yalnız (ilçe, en düşük, en yüksek) tutulur, sitede yalnız özet gösterilir.
// Yavaş çalışır (istekler arası 6-9 sn) ve parça parça ilerler: her çalışmada en fazla --sure saniye,
// ilerleme storage/app/private/piyasa/ham.json'a yazılır, bir sonraki çalışmada kaldığı yerden devam eder.
// Bittiğinde özet storage/app/private/piyasa/ozet.json'a yazılır (AppServiceProvider onu config'e yükler).
// Güvenlik: yeni özet eskisinin yarısından az okul içeriyorsa (site yapısı değişmiş olabilir) ESKİ özet korunur.
class PiyasaUcretYenile extends Command
{
    protected $signature = 'platform:piyasa-ucret-yenile {--sure=240 : Bu çalışmada en fazla kaç saniye} {--yeniden : Bu dönemi baştan topla} {--ozet= : Toplamadan, verilen ham json dosyasından özet çıkar (deneme)}';

    protected $description = 'Kreş/anaokulu aylık ücret özetini (il/ilçe) yeniler - Ocak ve Temmuz';

    public const IL_EN_AZ = 5;

    public const ILCE_EN_AZ = 3;

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0 Safari/537.36';

    public static function klasor(): string
    {
        return storage_path('app/private/piyasa');
    }

    public function handle(): int
    {
        if ($dosya = $this->option('ozet')) {
            $ozet = $this->ozetCikar(json_decode((string) file_get_contents($dosya), true) ?: [], now()->format('Y-m'));
            $iller = [];
            foreach ($ozet['iller'] as $s => $x) {
                $iller[$s] = array_diff_key($x, ['ilceler' => 1]) + ['ilce_sayisi' => count($x['ilceler'])];
            }
            $this->line(json_encode(['genel' => $ozet['genel'], 'iller' => $iller], JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        @set_time_limit((int) $this->option('sure') + 120);
        $bitis = microtime(true) + (int) $this->option('sure');
        $donem = now()->format('Y').'-'.(now()->month <= 6 ? '01' : '07');
        @mkdir(self::klasor(), 0775, true);
        $hamYol = self::klasor().'/ham.json';
        $durum = is_file($hamYol) ? (json_decode((string) file_get_contents($hamYol), true) ?: []) : [];
        if (($durum['donem'] ?? null) !== $donem || $this->option('yeniden')) {
            $durum = ['donem' => $donem, 'bitti' => false, 'iller' => []];
        }
        if ($durum['bitti']) {
            $this->info($donem.' dönemi zaten tamamlandı.');

            return self::SUCCESS;
        }

        $kaydet = function () use (&$durum, $hamYol) {
            file_put_contents($hamYol, json_encode($durum, JSON_UNESCAPED_UNICODE));
        };
        foreach (City::query()->orderBy('slug')->get(['slug', 'name']) as $il) {
            $k = $durum['iller'][$il->slug] ?? ['ad' => $il->name, 'sayfalar' => [], 'bitti' => false];
            if ($k['bitti']) {
                continue;
            }
            $sayfa = count($k['sayfalar']) + 1;
            while (true) {
                if (microtime(true) > $bitis) {
                    $durum['iller'][$il->slug] = $k;
                    $kaydet();
                    $this->info('Süre doldu, sonraki çalışmada devam edecek ('.$il->slug.' sayfa '.$sayfa.').');

                    return self::SUCCESS;
                }
                [$kod, $html] = $this->getir('https://okul.com.tr/anaokulu/'.$il->slug.($sayfa > 1 ? '?page='.$sayfa : ''));
                if ($kod === 404) {
                    $k['yok'] = true;
                    $k['bitti'] = true;
                    break;
                }
                if ($kod !== 200 || $html === '') {
                    // Ağ / sunucu sorunu: kaydet, bir sonraki çalışmada tekrar dener.
                    $durum['iller'][$il->slug] = $k;
                    $kaydet();
                    $this->warn($il->slug.' sayfa '.$sayfa.': yanıt alınamadı ('.$kod.'), sonra tekrar denenecek.');

                    return self::SUCCESS;
                }
                $kartlar = $this->kartlar($html);
                if ($sayfa === 1) {
                    preg_match_all('/[?&]page=(\d+)/', $html, $m);
                    $k['son_sayfa'] = $m[1] ? max(array_map('intval', $m[1])) : 1;
                }
                $k['sayfalar'][] = $kartlar;
                $fiyatli = count(array_filter($kartlar, fn ($x) => $x[1]));
                $this->line($il->slug.' '.$sayfa.'/'.$k['son_sayfa'].' kart '.count($kartlar).' fiyatlı '.$fiyatli);
                $sayfa++;
                $durum['iller'][$il->slug] = $k;
                $kaydet();
                usleep(random_int(6000, 9000) * 1000);
                // Fiyatı yalnız doğrulanmış profiller gösteriyor ve onlar ilk sayfalarda: fiyatlı kart kalmayınca il biter.
                if ($sayfa > $k['son_sayfa'] || $fiyatli === 0) {
                    $k['bitti'] = true;
                    break;
                }
            }
            $durum['iller'][$il->slug] = $k;
            $kaydet();
        }

        $durum['bitti'] = true;
        $kaydet();
        $ozet = $this->ozetCikar($durum['iller'], $donem);
        $eski = (int) (config('piyasa_ucretleri.genel.n') ?: 0);
        $yeni = (int) ($ozet['genel']['n'] ?? 0);
        if ($yeni < max(1, $eski / 2)) {
            report(new \RuntimeException('Kreş ücret özeti yenilenemedi: yeni toplamda '.$yeni.' okul var (önceki '.$eski.'). Kaynak sitenin yapısı değişmiş olabilir; eski özet korundu.'));
            $this->error('Yeni özette çok az okul var ('.$yeni.' / önceki '.$eski.'), eski özet korundu.');

            return self::SUCCESS;
        }
        $yol = self::klasor().'/ozet.json';
        file_put_contents($yol.'.yeni', json_encode($ozet, JSON_UNESCAPED_UNICODE));
        rename($yol.'.yeni', $yol);
        $this->info('Özet yazıldı: '.count($ozet['iller']).' il, '.$yeni.' okul.');

        return self::SUCCESS;
    }

    /** @return array{0:int,1:string} */
    private function getir(string $url): array
    {
        try {
            $r = Http::withHeaders(['User-Agent' => self::UA, 'Accept-Language' => 'tr-TR,tr;q=0.9'])->timeout(40)->get($url);

            return [$r->status(), $r->successful() ? $r->body() : ''];
        } catch (\Throwable $e) {
            return [0, ''];
        }
    }

    private static function tl(string $x): int
    {
        return (int) str_replace('.', '', $x);
    }

    /** Liste sayfasındaki her okul kartından yalnız [ilçe, en düşük, en yüksek] (okul adı alınmaz). */
    public function kartlar(string $html): array
    {
        $t = preg_replace('#<(script|style)[^>]*>.*?</\1>#s', '', $html);
        $t = preg_replace('/\s+/u', ' ', html_entity_decode(preg_replace('/<[^>]+>/', ' ', $t), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $i = mb_strpos($t, ' bulundu.');
        $t = $i > 0 ? mb_substr($t, $i) : $t;
        $out = [];
        $parcalar = explode('Hemen İncele', $t);
        array_pop($parcalar);
        foreach ($parcalar as $p) {
            if (! preg_match('#([A-ZÇĞİÖŞÜ][^/]{1,30}?) / [^/]{1,60}? Mah\. Seçildi#u', $p, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $parca = explode(' Karşılaştır ', $m[1][0]);
            $ilce = trim(end($parca));
            $kalan = substr($p, $m[0][1] + strlen($m[0][0]));
            if (preg_match('/(\d{1,3}(?:\.\d{3})+) TL(?: - (\d{1,3}(?:\.\d{3})+) TL)?/u', $kalan, $f)) {
                $a = self::tl($f[1]);
                $out[] = [$ilce, $a, ! empty($f[2]) ? self::tl($f[2]) : $a];
            } else {
                $out[] = [$ilce, null, null];
            }
        }

        return $out;
    }

    private static function sade(string $s): string
    {
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s;
        }

        return trim(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $s)));
    }

    private static function yuvarla(float $x): int
    {
        return (int) (round($x / 500, 0, PHP_ROUND_HALF_EVEN) * 500);
    }

    /** Ortalama, medyan ve çeyrekler (Python statistics.quantiles(n=4) ile aynı hesap). */
    private static function ozet(array $fiyatlar): array
    {
        $orta = array_map(fn ($f) => ($f[0] + $f[1]) / 2, $fiyatlar);
        sort($orta);
        $n = count($orta);
        $medyan = $n % 2 ? $orta[intdiv($n, 2)] : ($orta[$n / 2 - 1] + $orta[$n / 2]) / 2;
        if ($n >= 4) {
            $q = [];
            foreach ([1, 3] as $i) {
                $j = max(1, min($n - 1, intdiv($i * ($n + 1), 4)));
                $d = $i * ($n + 1) - $j * 4;
                $q[$i] = ($orta[$j - 1] * (4 - $d) + $orta[$j] * $d) / 4;
            }
        } else {
            $q = [1 => $orta[0], 3 => $orta[$n - 1]];
        }

        return ['n' => $n, 'ort' => self::yuvarla(array_sum($orta) / $n), 'medyan' => self::yuvarla($medyan),
            'alt' => self::yuvarla($q[1]), 'ust' => self::yuvarla($q[3]),
            'en_az' => (int) min(array_column($fiyatlar, 0)), 'en_cok' => (int) max(array_column($fiyatlar, 1))];
    }

    public function ozetCikar(array $iller, string $donem): array
    {
        $sonuc = ['tarih' => $donem, 'genel' => null, 'iller' => []];
        $hepsi = [];
        $adlar = City::query()->pluck('name', 'slug');
        foreach ($iller as $slug => $k) {
            if (! empty($k['yok'])) {
                continue;
            }
            $ilceAdlari = districts_for_city((string) ($adlar[$slug] ?? $k['ad'] ?? ''));
            usort($ilceAdlari, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            $ilF = [];
            $ilceF = [];
            foreach ($k['sayfalar'] ?? [] as $kartlar) {
                foreach ($kartlar as [$ilceHam, $a, $b]) {
                    // Aşırı uç değerler (yazım hatası olasılığı) dışarıda: aylık 3.000 TL altı / 150.000 TL üstü
                    if (! $a || $a < 3000 || $b > 150000) {
                        continue;
                    }
                    $ilF[] = [$a, $b];
                    $s = self::sade((string) $ilceHam);
                    if (str_ends_with($s, 'eyüp')) {
                        $s = 'eyüpsultan';   // eski adı
                    }
                    foreach ($ilceAdlari as $ad) {
                        if (str_ends_with($s, self::sade($ad))) {
                            $ilceF[$ad][] = [$a, $b];
                            break;
                        }
                    }
                }
            }
            if (count($ilF) < self::IL_EN_AZ) {
                continue;
            }
            $hepsi = array_merge($hepsi, $ilF);
            $o = self::ozet($ilF);
            ksort($ilceF);
            $o['ilceler'] = [];
            foreach ($ilceF as $ad => $f) {
                if (count($f) >= self::ILCE_EN_AZ) {
                    $o['ilceler'][$ad] = self::ozet($f);
                }
            }
            $sonuc['iller'][$slug] = $o;
        }
        $sonuc['genel'] = $hepsi ? self::ozet($hepsi) : null;

        return $sonuc;
    }
}
