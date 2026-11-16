<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = url('/sitemap.xml');
        // 26 Ekim 2026: yedek (bekleme) sunucusu - hiçbir sayfa taranmasın.
        if (config('app.bekleme')) {
            return response("User-agent: *
Disallow: /
", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        // 16 Kasım 2026: istenmeyen tarayıcı botları (ayrıca BotKalkani ile 403 alırlar).
        $istenmeyen = [];
        foreach (['ElevenlabsCrawler', 'SemrushBot', 'SiteAuditBot', 'PetalBot'] as $bot) {
            array_push($istenmeyen, 'User-agent: '.$bot, 'Disallow: /', '');
        }

        $lines = [
            ...$istenmeyen,
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /aile/panel',
            'Disallow: /aile/bildirimler',
            'Disallow: /aile/mesajlar',
            'Disallow: /kurum-panel/panel',
            'Disallow: /kurum-panel/profil',
            'Disallow: /kurum-panel/bakiyem',
            'Disallow: /kurum-panel/bildirimler',
            'Disallow: /kurum-panel/sorular',
            'Disallow: /kurum-panel/sifre-degistir',
            'Disallow: /kurum-panel/paketler',
            'Disallow: /kurum-panel/talep',
            '',
            'Sitemap: '.$sitemapUrl,
        ];

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
