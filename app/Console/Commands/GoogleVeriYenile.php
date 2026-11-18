<?php

namespace App\Console\Commands;

use App\Services\GoogleVeriServisi;
use App\Support\AnalitikPanelVerisi;
use Illuminate\Console\Command;

// 15 Kasım 2026: İstatistikler sayfasının Analytics / Search Console verisini her sabah önbelleğe çeker
// (sayfa açılışında Google'a gidilmez). Dönemler ayrı ayrı zamanlanır ki web-cron uzun işi kesmesin.
class GoogleVeriYenile extends Command
{
    protected $signature = 'google:veri-yenile {--gun=* : 7, 30, 90, 365 (boşsa hepsi)} {--son24 : Yalnızca son 24 saat verisini yenile} {--seo : Yalnızca SEO fırsatları listesini yenile}';

    protected $description = 'Google Analytics ve Search Console verisini İstatistikler sayfası için önbelleğe çeker';

    public function handle(GoogleVeriServisi $g, AnalitikPanelVerisi $v): int
    {
        if (! $g->hazirMi()) {
            $this->warn('GA4_PROPERTY_ID ya da hizmet hesabı dosyası yok, atlandı.');

            return self::SUCCESS;
        }
        @set_time_limit(300);
        // 16 Kasım 2026: son 24 saat kartı her saat yenilenir.
        if ($this->option('son24')) {
            $v->cekSon24();
            $this->info('son 24 saat: tamam');

            return self::SUCCESS;
        }
        // 18 Kasım 2026: SEO fırsatları (Search Console aranan kelime + sayfa) haftada bir ayrı görevle yenilenir.
        if ($this->option('seo')) {
            app(\App\Support\SeoFirsatlari::class)->cek();
            $this->info('seo fırsatları: tamam');

            return self::SUCCESS;
        }
        $gunler = array_map('intval', $this->option('gun') ?: array_keys(AnalitikPanelVerisi::DONEMLER));
        foreach ($gunler as $gun) {
            if (! isset(AnalitikPanelVerisi::DONEMLER[$gun])) {
                continue;
            }
            $t = microtime(true);
            $v->cek($gun);
            $this->info("$gun gün: tamam (".round(microtime(true) - $t, 1).' sn)');
        }

        return self::SUCCESS;
    }
}
