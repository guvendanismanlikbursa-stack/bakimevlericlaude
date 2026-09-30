<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 27 Eylul 2026: kullanicinin talebi - kurumlari telefonla arayip
// abonelige ikna edecek pazarlamaci icin ayri, dar yetkili bir hesap turu.
// Admin/kurum/aile hesaplarindan tamamen bagimsiz, sadece telefon+sifre
// ile giris yapar ve session'da sadece 'marketer_id' tutulur - MarketerAuth
// middleware'i bu oturumu SADECE CRM rotalarina izin verir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketers');
    }
};
