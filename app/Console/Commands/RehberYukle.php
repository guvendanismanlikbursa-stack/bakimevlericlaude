<?php

namespace App\Console\Commands;

use App\Models\ContentPage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * 5 Kasım 2026: rehber yazılarını dosyalardan yükler (her siteye 100 yazı).
 *  - Liste: database/rehber/makaleler.php  [site => [slug => [kategori, başlık, özet]]]
 *  - Metin: database/rehber/{site}/{slug}.html
 *  - Kapak: public/images/rehber/{site}/{slug}.jpg (varsa)
 * Var olan yazının metnine dokunulmaz (panelden yapılan düzeltme korunur); --guncelle verilirse metin de yenilenir.
 * --baslangic=YYYY-MM-DD --haftalik=N: YENİ eklenen yazılar o tarihten itibaren haftada N tane yayına açılacak şekilde tarihlenir.
 */
class RehberYukle extends Command
{
    protected $signature = 'rehber:yukle
        {--site= : Yalnız bu site (bakimevleri / bakimevibul / bakimeviara)}
        {--guncelle : Var olan yazıların başlık/özet/metnini de dosyadan yenile}
        {--baslangic= : Yeni yazıların ilk yayın günü (YYYY-MM-DD)}
        {--haftalik=0 : Haftada kaç yeni yazı yayına açılsın (0 = hepsi hemen)}
        {--dry-run : Hiçbir şey yazma}';

    protected $description = 'Rehber yazılarını database/rehber dosyalarından yükler';

    public function handle(): int
    {
        $liste = require database_path('rehber/makaleler.php');
        $siteler = $this->option('site') ? [$this->option('site')] : array_keys($liste);
        $haftalik = max(0, (int) $this->option('haftalik'));
        $baslangic = $this->option('baslangic') ? Carbon::parse($this->option('baslangic'))->setTime(9, 0) : now();
        $sira = 0;
        $sayac = ['yeni' => 0, 'guncellendi' => 0, 'kapak' => 0, 'atlandi' => 0];

        foreach ($siteler as $site) {
            $kategoriler = config('rehber.kategoriler.'.$site);
            if (! $kategoriler || ! isset($liste[$site])) {
                $this->error("Bilinmeyen site: {$site}");

                return self::FAILURE;
            }
            foreach ($liste[$site] as $slug => [$kategori, $baslik, $ozet]) {
                $dosya = database_path("rehber/{$site}/{$slug}.html");
                if (! isset($kategoriler[$kategori]) || ! is_file($dosya)) {
                    $this->warn("Atlandı ({$site}/{$slug}): ".(! is_file($dosya) ? 'metin dosyası yok' : 'tanımsız kategori '.$kategori));
                    $sayac['atlandi']++;

                    continue;
                }
                $govde = trim(file_get_contents($dosya));
                $kapak = is_file(public_path("images/rehber/{$site}/{$slug}.jpg")) ? "images/rehber/{$site}/{$slug}.jpg" : null;
                $sayfa = ContentPage::where('brand', $site)->where('slug', $slug)->first();

                if (! $sayfa) {
                    $yayin = $haftalik > 0 ? $baslangic->copy()->addDays(intdiv($sira, $haftalik) * 7 + intdiv(($sira % $haftalik) * 7, $haftalik)) : null;
                    $sira++;
                    if (! $this->option('dry-run')) {
                        ContentPage::create(['brand' => $site, 'type' => 'guide', 'slug' => $slug, 'title' => $baslik, 'summary' => $ozet,
                            'body' => $govde, 'category' => $kategori, 'cover_path' => $kapak, 'published_at' => $yayin]);
                    }
                    $sayac['yeni']++;

                    continue;
                }
                $degisen = ['category' => $kategori];
                if ($kapak && $sayfa->cover_path !== $kapak) {
                    $degisen['cover_path'] = $kapak;
                    $sayac['kapak']++;
                }
                if ($this->option('guncelle')) {
                    $degisen += ['title' => $baslik, 'summary' => $ozet, 'body' => $govde];
                }
                $sayfa->fill($degisen);
                if ($sayfa->isDirty()) {
                    $sayac['guncellendi']++;
                    if (! $this->option('dry-run')) {
                        $sayfa->save();
                    }
                }
            }
        }

        foreach ($sayac as $k => $v) {
            $this->line(str_pad($k, 14).$v);
        }
        $this->info($this->option('dry-run') ? 'DENEME: yazılmadı.' : 'Tamamlandı.');

        return self::SUCCESS;
    }
}
