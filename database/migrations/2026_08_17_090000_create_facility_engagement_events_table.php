<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 17 Agustos 2026: kullanicinin talebi - sahiplenilmemis kurumlarin
// profilinde "size gercekten talep geliyor" kanitini somut rakamlarla
// gostermek (goruntulenme + telefon tiklamasi + WhatsApp tiklamasi, son
// 30 gun). views_count sutunu sadece TOPLAM (omur boyu) sayaç, tarih
// bazli sorgulanamiyor; telefon/WhatsApp tiklamasi icin facility_id'ye
// bagli hicbir kayit yoktu (mevcut whatsapp_clicks tablosu site-geneli
// yuzen destek butonunu izliyor, kuruma ozel degil). Tek, basit bir olay
// gunlugu ile hem gecmise donuk (sunucu cron'una bagimli olmadan) hem de
// aninda dogru 30 gunluk toplam alinabiliyor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_engagement_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // view | phone_click | whatsapp_click
            $table->timestamp('created_at')->useCurrent();

            $table->index(['facility_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_engagement_events');
    }
};
