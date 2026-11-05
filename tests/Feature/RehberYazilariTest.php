<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ContentPage;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

// 5 Kasım 2026: rehber yazıları - konu sekmeleri, kapak, sayfalama, ileri tarihli yazının gizlenmesi.
class RehberYazilariTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function yazi(string $slug, array $ek = []): ContentPage
    {
        return ContentPage::create(array_merge([
            'brand' => 'bakimevibul', 'type' => 'guide', 'slug' => $slug, 'title' => 'Başlık '.$slug, 'summary' => 'Özet '.$slug,
            'body' => '<p>Metin '.$slug.'</p>', 'category' => 'evde-bakim',
        ], $ek));
    }

    public function test_liste_konu_sekmeleri_kapak_ve_ileri_tarihli_yazi(): void
    {
        $this->yazi('yasli-bakim-a', ['cover_path' => 'images/rehber/bakimevibul/yasli-bakim-a.jpg']);
        $this->yazi('yasli-bakim-b', ['category' => 'huzurevi-ve-bakimevi']);
        $this->yazi('yasli-bakim-c');
        $this->yazi('yasli-bakim-gelecek', ['published_at' => now()->addDays(10)]);
        $this->yazi('yasli-bakim-baska-site', ['brand' => 'bakimeviara', 'category' => 'ozel-egitim']);

        $this->get('/site/bakimevibul/bakim-rehberi')->assertOk()
            ->assertSee('Başlık yasli-bakim-a')->assertSee('Başlık yasli-bakim-b')
            ->assertSee('images/rehber/bakimevibul/yasli-bakim-a.jpg', false)
            ->assertSee('Evde Bakım')->assertDontSee('Başlık yasli-bakim-gelecek')->assertDontSee('Başlık yasli-bakim-baska-site');
        $this->get('/site/bakimevibul/bakim-rehberi?kategori=huzurevi-ve-bakimevi')->assertOk()
            ->assertSee('Başlık yasli-bakim-b')->assertDontSee('Başlık yasli-bakim-a');

        $this->get('/site/bakimevibul/sayfa/yasli-bakim-a')->assertOk()->assertSee('Aynı konudaki diğer yazılar', false)->assertSee('Başlık yasli-bakim-c')->assertDontSee('Başlık yasli-bakim-gelecek');
        $this->get('/site/bakimevibul/sayfa/yasli-bakim-gelecek')->assertNotFound();
    }

    public function test_sayfalama_12_yazi(): void
    {
        foreach (range(1, 14) as $i) {
            $this->yazi('yasli-bakim-s'.$i);
        }
        $this->get('/site/bakimevibul/bakim-rehberi')->assertOk()->assertSee('page=2', false);
        $this->get('/site/bakimevibul/bakim-rehberi?page=2')->assertOk()->assertSee('Sayfa 2', false);
    }

    public function test_yazi_sayfasi_paylasim_gorseli_ve_yol_bilgisi(): void
    {
        $this->yazi('yasli-bakim-k', ['cover_path' => 'images/rehber/bakimevibul/yasli-bakim-k.jpg']);

        $this->get('/site/bakimevibul/sayfa/yasli-bakim-k')->assertOk()
            ->assertSee('property="og:image" content="'.asset('images/rehber/bakimevibul/yasli-bakim-k.jpg').'"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"name":"Bakım Rehberi"', false)
            ->assertSee('"name":"Evde Bakım"', false);
    }

    public function test_kurum_sayfasinda_ilgili_yazilar_dogru_siteye_baglanir(): void
    {
        $sehir = City::firstOrCreate(['slug' => 'istanbul'], ['name' => 'Istanbul']);
        $tur = FacilityCategory::create(['name' => 'Huzurevi', 'slug' => 'huzurevi', 'brand_scope' => 'yasli-bakim']);
        Facility::create(['name' => 'Deneme Huzurevi', 'slug' => 'deneme-huzurevi', 'city_id' => $sehir->id, 'facility_category_id' => $tur->id,
            'district' => 'Merkez', 'address' => 'Adres', 'phone' => '02120000000', 'description' => 'Aciklama', 'capacity' => 20,
            'services' => ['bakim'], 'is_published' => true, 'is_claimed' => false, 'balance' => 0]);
        $this->yazi('yasli-bakim-secim', ['category' => 'huzurevi-ve-bakimevi', 'cover_path' => 'images/rehber/bakimevibul/yasli-bakim-secim.jpg']);
        $this->yazi('yasli-bakim-kapaksiz', ['category' => 'haklar-ve-destekler']);
        $this->yazi('yasli-bakim-ileri', ['category' => 'aile-ve-bakim-veren', 'cover_path' => 'x.jpg', 'published_at' => now()->addDays(3)]);

        // Aynı sitede iç bağlantı; kapaksız ve ileri tarihli yazı gösterilmez.
        $this->get('/site/bakimevibul/kurumlar/deneme-huzurevi')->assertOk()
            ->assertSee('Karar Verirken Yardımcı Yazılar')->assertSee('Başlık yasli-bakim-secim')
            ->assertSee('/sayfa/yasli-bakim-secim', false)
            ->assertDontSee('Başlık yasli-bakim-kapaksiz')->assertDontSee('Başlık yasli-bakim-ileri');
        // Başka sitede açılan kurum sayfası, yazının bulunduğu siteye bağlanır.
        Cache::flush();
        $this->get('/site/bakimevleri/kurumlar/deneme-huzurevi')->assertOk()
            ->assertSee('https://bakimevibul.com/sayfa/yasli-bakim-secim', false);
    }
}
