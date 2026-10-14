<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 13 Ekim 2026: kullanicinin talebi - "Karar Sihirbazi / Bakim
        // Danismani doldurulunca, bu talep havuzuna yalnizca premium
        // (is_subscribed) kurumlar erisebilmeli ve aileye teklif verebilmeli".
        // Daha once bu form TAMAMEN gecici/anlik calisiyordu (GET + canlı
        // sorgu, hicbir sey kaydedilmiyordu) - artik aile telefon/isim
        // birakirsa (iletisime gecebilmek icin ZORUNLU, FamilyNeedSurvey'deki
        // "telefon/adres yoksa kayit etme" ile AYNI mantik) kalici kaydedilir.
        Schema::create('care_advisor_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone');
            $table->unsignedInteger('patient_age')->nullable();
            $table->string('gender')->nullable();
            $table->string('condition')->nullable();
            $table->foreignId('city_id')->constrained('cities');
            $table->foreignId('facility_category_id')->nullable()->constrained('facility_categories')->nullOnDelete();
            $table->unsignedInteger('budget_max')->nullable();
            $table->json('concerns')->nullable();
            $table->string('brand')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_advisor_leads');
    }
};
