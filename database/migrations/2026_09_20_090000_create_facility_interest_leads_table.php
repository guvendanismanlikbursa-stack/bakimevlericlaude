<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 20 Eylul 2026: kullanicinin talebi - on kayitli (sahiplenilmemis VE
// anlasmali olmayan) kurumlarda artik dogrudan telefon/WhatsApp butonu
// YOK, tek bir "Bilgi ve Fiyat Talep Et" formu var. Bu, OfferRequest
// tablosunu BILEREK kullanmiyor - OfferRequestController::store()
// Facility::acceptsFamilyRequests() scope'unu ZORUNLU kiliyor (sahiplenilmemis
// kurumlar icin 404 doner), bu kurali gevsetmek anlasmali/sahiplenilmis
// kurumlar icin kurulmus mevcut teklif/kota/mesajlasma akisini (kurumun
// gercekten cevap verebilecegi bir hesabi var varsayimi) bozardi. Bu
// yeni, ayri, kucuk tablo SADECE admin'in kurumlari anlasmali olmaya
// ikna etmek icin kullanacagi "ilgi verisi" - kuruma hicbir bildirim
// gitmez, hicbir hesap/panel etkilenmez.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_interest_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('brand', 30);
            $table->string('full_name', 120);
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->string('message', 1000)->nullable();
            $table->string('status', 20)->default('yeni');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_interest_leads');
    }
};
