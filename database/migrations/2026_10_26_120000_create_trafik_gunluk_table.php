<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 26 Ekim 2026: kullanıcının talebi - sitelere gelen trafiğin günlük / haftalık / aylık / 3-6 aylık /
// yıllık olarak ziyaretçi türüne göre (gerçek kişi, şüpheli tarayıcı, Googlebot, Bing, yapay zekâ
// botları, diğer botlar) tutulması. Sunucu erişim kayıtları kısa süre saklandığı için her gün özetlenir.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trafik_gunluk')) {
            Schema::create('trafik_gunluk', function (Blueprint $table) {
                $table->id();
                $table->date('tarih');
                $table->string('marka', 30);
                $table->string('tur', 20);
                $table->unsignedInteger('istek')->default(0);
                $table->unsignedInteger('ip')->default(0);
                $table->timestamps();
                $table->unique(['tarih', 'marka', 'tur']);
                $table->index('tarih');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trafik_gunluk');
    }
};
