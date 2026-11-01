<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 1 Kasım 2026: kullanıcının talebi - anormal bot trafiğinde uyarı + admin panelinden engelleme.
return new class extends Migration
{
    public function up(): void
    {
        // Admin panelinden engellenen ağlar (firma/AS numarası ya da tek ağ).
        Schema::create('engelli_aglar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asn')->nullable();
            $table->string('firma', 150);
            $table->string('kapsam', 10)->default('asn'); // asn | ag
            $table->longText('v4')->nullable(); // [[baslangic, bitis], ...]
            $table->longText('v6')->nullable(); // ["2a02:c202::/32", ...]
            $table->string('ekleyen', 100)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index('aktif');
        });
        // Ağ (/24) -> AS numarası ve firma adı (RIPE kayıtlarından, önbellek).
        Schema::create('ag_bilgileri', function (Blueprint $table) {
            $table->id();
            $table->string('ag', 64)->unique();
            $table->unsignedBigInteger('asn')->nullable();
            $table->string('firma', 150)->nullable();
            $table->string('onek', 64)->nullable();
            $table->timestamps();
        });
        // Saatlik gözcünün bugünkü şüpheli ağ özeti.
        Schema::create('supheli_aglar', function (Blueprint $table) {
            $table->id();
            $table->date('tarih');
            $table->string('ag', 64);
            $table->unsignedBigInteger('asn')->nullable();
            $table->string('firma', 150)->nullable();
            $table->unsignedInteger('istek')->default(0);
            $table->unsignedInteger('supheli')->default(0);
            $table->unsignedInteger('bot')->default(0);
            $table->unsignedInteger('insan')->default(0);
            $table->unsignedInteger('engellenen')->default(0);
            $table->unsignedInteger('ip_sayisi')->default(0);
            $table->string('markalar', 100)->nullable();
            $table->string('ornek_ua', 255)->nullable();
            $table->timestamps();
            $table->unique(['tarih', 'ag']);
        });
        // Gönderilen uyarılar (aynı uyarı aynı gün tekrar gönderilmesin).
        Schema::create('bot_alarmlari', function (Blueprint $table) {
            $table->id();
            $table->string('anahtar', 120)->unique();
            $table->string('baslik', 200);
            $table->text('metin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_alarmlari');
        Schema::dropIfExists('supheli_aglar');
        Schema::dropIfExists('ag_bilgileri');
        Schema::dropIfExists('engelli_aglar');
    }
};
