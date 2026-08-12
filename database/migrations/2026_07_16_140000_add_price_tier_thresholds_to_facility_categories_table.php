<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// 16 Temmuz 2026: fiyat segmenti (Ekonomik/Standart/Premium/Ultra Premium)
// esikleri su ana kadar TEK global deger setiydi (Settings tablosu,
// price_tier_*). Kurum turleri (kres ile rehabilitasyon merkezi gibi) çok
// farkli fiyat olceklerinde oldugu icin esikler artik kurum kategorisi
// bazinda ayarlanabiliyor. Mevcut global degerler (veya config varsayilani)
// TUM kategorilere baslangic degeri olarak kopyalanir - deploy sonrasi
// hicbir kurumun segment rozeti degismez, admin isterse kategori bazinda
// ince ayar yapar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_categories', function (Blueprint $table) {
            $table->unsignedInteger('price_tier_standart_min')->nullable()->after('brand_scope');
            $table->unsignedInteger('price_tier_premium_min')->nullable()->after('price_tier_standart_min');
            $table->unsignedInteger('price_tier_ultra_min')->nullable()->after('price_tier_premium_min');
        });

        $defaults = [
            'standart_min' => (int) (DB::table('settings')->where('key', 'price_tier_standart_min')->value('value') ?? 15000),
            'premium_min' => (int) (DB::table('settings')->where('key', 'price_tier_premium_min')->value('value') ?? 30000),
            'ultra_min' => (int) (DB::table('settings')->where('key', 'price_tier_ultra_min')->value('value') ?? 50000),
        ];

        DB::table('facility_categories')->update([
            'price_tier_standart_min' => $defaults['standart_min'],
            'price_tier_premium_min' => $defaults['premium_min'],
            'price_tier_ultra_min' => $defaults['ultra_min'],
        ]);
    }

    public function down(): void
    {
        Schema::table('facility_categories', function (Blueprint $table) {
            $table->dropColumn(['price_tier_standart_min', 'price_tier_premium_min', 'price_tier_ultra_min']);
        });
    }
};
