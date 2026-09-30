<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Eylul 2026: kullanicinin talebi - "kuruma yollandi / beklemede / sil"
// (bkz. yeni Admin\OfferAndInterestLeadController ayni tarihli yorum).
// Her iki talep turu (Teklif Talebi + Kurum Ilgi Talebi) icin AYNI basit
// alan: admin WhatsApp'tan kuruma iletince bu doldurulur, boS ise
// "beklemede" sayilir. Ayri bir "status" enum'u yerine tek bir timestamp -
// hem "gonderildi mi" hem "ne zaman gonderildi" bilgisini tek alanda tutar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->timestamp('sent_to_facility_at')->nullable()->after('status');
        });
        Schema::table('facility_interest_leads', function (Blueprint $table) {
            $table->timestamp('sent_to_facility_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropColumn('sent_to_facility_at');
        });
        Schema::table('facility_interest_leads', function (Blueprint $table) {
            $table->dropColumn('sent_to_facility_at');
        });
    }
};
