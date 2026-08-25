<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Agustos 2026: kullanicinin talebi - kendi kisisel "aracilik" (bazi
// Bursa kurumlariyla anlasip aileleri yerlestirip komisyon alma) isini
// takip edebilecegi kucuk bir CRM. facilities tablosuna dokunmadan, sadece
// hangi kurumlarin "anlasmali" oldugunu isaretleyen bir alan + her aile-
// kurum yonlendirmesini (asama, ucret, odeme durumu) tutan ayri bir tablo.
// Herkese acik hicbir sayfayi/akisi etkilemez, sadece admin panelde yeni
// bir bolum olarak calisir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('is_broker_managed')->default(false)->after('is_claimed');
        });

        Schema::create('broker_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('family_name');
            $table->string('family_phone')->nullable();
            $table->string('status')->default('yonlendirildi');
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->string('fee_status')->default('bekliyor');
            $table->date('referred_at');
            $table->date('placed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broker_referrals');
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('is_broker_managed');
        });
    }
};
