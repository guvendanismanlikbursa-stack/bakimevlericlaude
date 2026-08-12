<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 12 Agustos 2026: kullanicinin talebi - kurum performans paneli sadece
// "su anki toplam sayi"yi gosteriyordu, "gecen aya gore nasilim" gibi bir
// trend/karsilastirma yoktu. Bu tablo her gece bir kurumun o GUNKU
// gorunum/favori/teklif/kabul sayilarini anlik goruntu (snapshot) olarak
// saklar - bkz. App\Console\Commands\SnapshotFacilityDailyStats,
// Facility\DashboardController performans bolumu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('offer_requests_count')->default(0);
            $table->unsignedInteger('quotes_sent_count')->default(0);
            $table->unsignedInteger('quotes_accepted_count')->default(0);
            $table->timestamps();

            $table->unique(['facility_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_daily_stats');
    }
};
