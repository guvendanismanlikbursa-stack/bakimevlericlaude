<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - admin her fiziksel ziyaretten
        // sonra tek bir kayit dusurur (tarih + not + istege bagli fotograf),
        // aile bunu kendi panelinde her zaman gorebilir (bkz. helpers.php
        // notify_user() - WhatsApp'a da AYRICA admin elle gonderir, otomatik
        // WhatsApp gonderimi bu platformda yok).
        Schema::create('visit_service_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->date('visited_at');
            $table->text('note');
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_service_reports');
    }
};
