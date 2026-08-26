<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - cocuk bakim/kres-anaokulu
        // kurumlarinda yas grubuna gore (0-1/1-2/2-3/3-4/4-6 yas) ayri fiyat
        // araligi. facility_room_types (yasli bakim icin oda tipi) ile AYNI
        // desen - bkz. o migration'daki ayni tarihli yorum (JSON degil ayri
        // tablo, ileride "0-1 yas grubu X-Y TL araliginda olan kurumlari
        // filtrele" gibi bir arama ozelligi icin).
        Schema::create('facility_age_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('age_group');
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['facility_id', 'age_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_age_groups');
    }
};
