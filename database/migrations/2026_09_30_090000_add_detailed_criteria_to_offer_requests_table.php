<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Eylul 2026: kullanicinin talebi - "Ucret/Teklif Bilgisi Al" formuna
// aile talebini daha ayrintili yakalayan 4 yeni alan (yas, ihtiyac tarihi,
// butce araligi) eklendi. Amac: mevcut, calisan forma kucuk ve risksiz bir
// ek yaparak GERCEK talep verisi biriktirmeye baslamak - bkz. bu tarihli
// sohbette konusulan "Aile Talep Havuzu" fikrinin kucuk/dogrulanabilir ilk
// adimi (buyuk sistemi simdi kurmuyoruz, once veri var mi gorecegiz).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('patient_age')->nullable()->after('patient_name');
            $table->date('needed_from')->nullable()->after('patient_age');
            $table->decimal('budget_min', 10, 2)->nullable()->after('needed_from');
            $table->decimal('budget_max', 10, 2)->nullable()->after('budget_min');
        });
    }

    public function down(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropColumn(['patient_age', 'needed_from', 'budget_min', 'budget_max']);
        });
    }
};
