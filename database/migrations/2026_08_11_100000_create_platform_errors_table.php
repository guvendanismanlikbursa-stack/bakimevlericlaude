<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 11 Agustos 2026: kirik galeri gorseli olayi hicbir yerde kalici bir iz
// birakmiyordu (Sentry/log dosyasi disinda, ki admin bunlari hic gormuyor).
// Bu tablo, otomatik kontrollerin (once sadece galeri saglik kontrolu,
// ileride baska kontroller de kullanabilir) bulduklari sorunlari admin
// panelinde GORUNUR ve KALICI birakmasi icin - "Hatalar" ekrani.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_errors', function (Blueprint $table) {
            $table->id();
            $table->string('source', 60); // ör. 'gallery-health-check'
            $table->string('title', 200); // ör. "Kurum galeri görseli diskte bulunamadı"
            $table->text('message'); // hatanin ne oldugunun okunabilir aciklamasi
            $table->json('context')->nullable(); // ilgili id'ler/ek veri
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_errors');
    }
};
