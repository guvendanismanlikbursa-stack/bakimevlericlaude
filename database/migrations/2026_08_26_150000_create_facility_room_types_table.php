<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - yasli bakim/huzurevi
        // kurumlarinda oda tipine gore (tek kisilik/2 kisilik/3 kisilik/
        // paylasimli) ayri fiyat araligi. Kullanicinin acik tercihi uzerine
        // JSON alan DEGIL, ayri bir tablo: ileride "2 kisilik odasi X-Y TL
        // araliginda olan kurumlari filtrele" gibi bir arama ozelligi
        // eklenebilsin diye (bu platformun asil isi zaten aile-kurum
        // eslestirme/filtreleme - bkz. price-guide sayfalari). Mevcut
        // facilities.price_min/price_max (genel aralik, mevcut arama/
        // filtreleme akislarinda kullanilan) KORUNUR, bu tablo EK bir
        // detay katmanidir.
        Schema::create('facility_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('room_type');
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['facility_id', 'room_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_room_types');
    }
};
