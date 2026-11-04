<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 4 Kasım 2026: kayıt/sahiplenme formlarında konum sayfa açılınca değil, form gönderilirken sorulur.
class KonumGonderirkenTest extends TestCase
{
    use RefreshDatabase;

    public function test_formlarda_konum_yalnizca_gonderirken_sorulur(): void
    {
        $city = City::firstOrCreate(['slug' => 'istanbul'], ['name' => 'Istanbul']);
        $cat = FacilityCategory::create(['name' => 'Yasli Bakim Evi', 'slug' => 'yasli-bakim-evi', 'brand_scope' => 'yasli-bakim']);
        $f = Facility::create(['name' => 'Konum Kurum', 'slug' => 'konum-kurum', 'city_id' => $city->id, 'facility_category_id' => $cat->id, 'district' => 'Merkez', 'address' => 'Adres', 'is_published' => true, 'is_claimed' => false, 'services' => ['bakim']]);

        $sayfalar = ['/kurumlar/'.$f->slug.'/sahiplen' => 'js-claim-lat', '/kurum-kaydi' => 'signup_lat', '/aile/kayit' => 'signup_lat'];
        foreach ($sayfalar as $adres => $alan) {
            $html = $this->get($adres)->assertOk()->getContent();
            $this->assertStringContainsString('soruldu = true', $html, $adres);
            $this->assertStringNotContainsString('requestLocation', $html, $adres);
            $this->assertSame(1, substr_count($html, 'navigator.geolocation.getCurrentPosition(function (p)'), $adres);
            // Sayfa yüklenirken çalışan (form gönderimine bağlı olmayan) konum isteği kalmamalı.
            preg_match_all('/<script>(.*?)<\/script>/s', $html, $m);
            foreach ($m[1] as $js) {
                if (str_contains($js, 'getCurrentPosition') && ! str_contains($js, "addEventListener('click'")) {
                    $this->assertStringContainsString("form.addEventListener('submit'", $js, $adres.' sayfa açılınca konum istiyor');
                }
            }
            file_put_contents(sys_get_temp_dir().'/konum_'.md5($adres).'.js', implode("\n;\n", $m[1]));
        }
    }
}
