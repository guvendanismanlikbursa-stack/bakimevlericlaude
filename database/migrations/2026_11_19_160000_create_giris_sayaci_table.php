<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Kasım 2026: kullanıcının kararı - kendi çerezsiz giriş sayacımız (Analytics'in göremediği girişler için).
// Saat (Türkiye) + site + kaynak başına toplam; kişi/IP/çerez tutulmaz.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giris_sayaci', function (Blueprint $table) {
            $table->id();
            $table->char('saat', 10);          // YmdH, Türkiye saati
            $table->string('site', 40);        // bakimevleri.com / bakimevibul.com / bakimeviara.com
            $table->string('kaynak', 10);      // arama / dogrudan / diger
            $table->unsignedInteger('adet')->default(0);
            $table->unique(['saat', 'site', 'kaynak']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giris_sayaci');
    }
};
