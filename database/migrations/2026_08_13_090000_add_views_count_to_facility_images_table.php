<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 13 Agustos 2026: kullanicinin talebi - "hangi gorselim daha cok ilgi
// cekiyor goremiyorum". Bir galeri gorseli lightbox'ta acildiginda bu sayac
// artar - bkz. Public\FacilityImageController::markViewed(),
// Facility\DashboardController performans bolumu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_images', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('facility_images', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
