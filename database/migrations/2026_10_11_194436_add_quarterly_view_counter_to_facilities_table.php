<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 11 Ekim 2026: kullanicinin talebi - admin/satis-firsatlari sayfasinda
// "son 90 gun" secenegi istendi. Gercek kayan (rolling) 90 gunluk pencere
// icin GUNLUK bir gorulme gecmisi tablosu gerekir ki bu veritabaninda hic
// yoktu - geriye donuk olusturulamaz. Bunun yerine, haftalik/aylik sayaclarla
// (views_count_at_week_start/views_count_at_month_start) AYNI mantikla
// "bu ceyrek" (takvim ceyregi, 3 ayda bir sifirlanir) sayaci eklenir -
// bugunden itibaren birikmeye baslar, zamanla anlamli hale gelir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count_at_quarter_start')->default(0)->after('views_count_at_month_start');
        });

        // Bugunden itibaren saymaya baslasin diye mevcut tum kurumlarda
        // ceyrek baslangici = su anki gercek views_count.
        DB::table('facilities')->update(['views_count_at_quarter_start' => DB::raw('views_count')]);
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('views_count_at_quarter_start');
        });
    }
};
