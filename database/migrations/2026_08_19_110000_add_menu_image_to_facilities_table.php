<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Agustos 2026: kullanicinin talebi - "kurum panellerine yemek listesi
// bolumu, kurum yetkilisi haftalik yemek listesinin gorselini yuklesin,
// kullanicilar goruntuleyip buyutebilsin". Galeri (facility_images) gibi
// coklu degil, tek bir gorsel (her hafta ustune yazilir) oldugu icin ayri
// bir tablo yerine facilities uzerinde iki kolon yeterli.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('menu_image_path')->nullable()->after('cover_image');
            $table->timestamp('menu_image_updated_at')->nullable()->after('menu_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['menu_image_path', 'menu_image_updated_at']);
        });
    }
};
