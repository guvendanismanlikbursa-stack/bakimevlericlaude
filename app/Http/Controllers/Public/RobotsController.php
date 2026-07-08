<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = url('/sitemap.xml');

        $lines = [
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
