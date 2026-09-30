<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Eylul 2026: kullanicinin talebi - haftalik sayacin yani sira, AYIN
// BASINDAN BERI biriken (daha buyuk/etkileyici) bir AYLIK sayac da eklenir.
// Ana/buyuk rakam artik AYLIK, haftalik kucuk bir "trend" rozeti olarak
// yaninda kalir (bkz. Facility::monthlyDisplayViewsCount()/displayViewsCount()).
// boosted_views_count (mevcut sutun) ARTIK AYLIK kapsamda (her ay basi
// sifirlanir), haftalik icin AYRI bir sutun (boosted_views_count_weekly)
// eklendi - ayni "eklenen goruntulenme" ikisine de AYNI ANDA yazilir
// (bkz. Console\Commands\BoostFacilityViews), ama farkli takvimlerde sifirlanirlar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedInteger('boosted_views_count_weekly')->default(0)->after('boosted_views_count');
            $table->unsignedInteger('views_count_at_month_start')->default(0)->after('views_count_at_week_start');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['boosted_views_count_weekly', 'views_count_at_month_start']);
        });
    }
};
