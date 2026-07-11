<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

// Bakim: Bu host paylasimli cPanel oldugu icin cron'un dogrudan "php artisan
// schedule:run" calistirmasi guvenilir degil (CLI PHP binary yolu belirsiz/
// tutarsiz olabiliyor). Bunun yerine cPanel cron'u sadece bu URL'i curl ile
// tetikler; gercek calisma zaten guvenilir calistigi kanitlanmis web sunucusu
// (LiteSpeed/PHP-FPM) uzerinden olur. token, bu ucun herkese acik/tahmin
// edilebilir olmamasi icin config('platform.cron_secret') (.env > CRON_SECRET)
// ile karsilastirilir.
class CronRunnerController extends Controller
{
    public function run(Request $request): Response
    {
        $secret = (string) config('platform.cron_secret');

        if ($secret === '' || ! hash_equals($secret, (string) $request->query('token'))) {
            abort(403);
        }

        Artisan::call('schedule:run');

        return response(Artisan::output(), 200)->header('Content-Type', 'text/plain');
    }
}
