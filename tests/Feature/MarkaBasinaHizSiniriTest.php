<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// 27 Ekim 2026: 3 marka tek sunucuda tek kopya çalıştığından giriş deneme
// sayacı marka adresi + IP ile tutulur; bir markadaki sınır diğerini etkilemez.
class MarkaBasinaHizSiniriTest extends TestCase
{
    use RefreshDatabase;

    public function test_giris_siniri_marka_basina_ayri_sayilir(): void
    {
        $limit = (int) config('platform.throttle.auth-attempt');
        $veri = ['login' => 'yok@ornek.com', 'password' => 'yanlis-sifre'];

        for ($i = 0; $i < $limit; $i++) {
            $this->post('https://bakimevibul.com/kurum-panel/giris', $veri)->assertStatus(302);
        }
        $this->post('https://bakimevibul.com/kurum-panel/giris', $veri)->assertStatus(429);

        // Aynı IP, farklı marka: sayaç ayrı olduğu için engellenmemeli.
        $this->post('https://bakimeviara.com/kurum-panel/giris', $veri)->assertStatus(302);
    }
}
