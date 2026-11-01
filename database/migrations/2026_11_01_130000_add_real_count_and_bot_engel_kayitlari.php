<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 1 Kasım 2026: kullanıcının talebi - "Sitelere Giriş" sayacı botlarla şişiyordu.
// real_count: sayfadaki küçük betiği çalıştıran (gerçek tarayıcı) ziyaretçiler; eski count (bot dahil) korunur.
// bot_engel_kayitlari: bot kalkanının engellediği istekler (admin panelde görünür).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_visits', 'real_count')) {
            Schema::table('site_visits', function (Blueprint $table) {
                $table->unsignedInteger('real_count')->default(0)->after('count');
            });
        }
        if (! Schema::hasTable('bot_engel_kayitlari')) {
            Schema::create('bot_engel_kayitlari', function (Blueprint $table) {
                $table->id();
                $table->date('tarih');
                $table->string('marka', 20)->default('');
                $table->string('anahtar', 64);
                $table->string('neden', 20);
                $table->unsignedInteger('sayi')->default(0);
                $table->timestamp('son_gorulme')->nullable();
                $table->timestamps();
                $table->unique(['tarih', 'marka', 'anahtar', 'neden']);
                $table->index('tarih');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_engel_kayitlari');
        if (Schema::hasColumn('site_visits', 'real_count')) {
            Schema::table('site_visits', fn (Blueprint $table) => $table->dropColumn('real_count'));
        }
    }
};
