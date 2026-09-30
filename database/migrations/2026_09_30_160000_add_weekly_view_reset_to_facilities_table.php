<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Eylul 2026: kullanicinin talebi - gosterilen goruntulenme sayaci
// artik TUM ZAMANLAR degil, "bu haftaki" bir sayac gibi calisir ve her
// Pazar aksami sifirlanir (bkz. Console\Commands\ResetWeeklyViewCounters).
// GERCEK views_count sutunu (ic analitik/lifetime) HICBIR ZAMAN sifirlanmaz
// - bu yeni sutun sadece "hafta basindaki views_count neydi" bilgisini
// tutar, haftalik GERCEK goruntulenme buradan (views_count - bu deger)
// hesaplanir (bkz. Facility::displayViewsCount()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedInteger('views_count_at_week_start')->default(0)->after('view_boost_daily_added');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('views_count_at_week_start');
        });
    }
};
