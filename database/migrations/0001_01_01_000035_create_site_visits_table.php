<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admin panelde "sitelere giris sayilari" icin gunluk, marka bazli sayac.
// Her (brand, visit_date) kombinasyonu icin tek satir tutulur ve upsert ile
// artirilir; boylece trafik ne olursa olsun tablo satir sayisi patlamaz
// (gun basina marka basina en fazla 1 satir).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 40);
            $table->date('visit_date');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['brand', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
