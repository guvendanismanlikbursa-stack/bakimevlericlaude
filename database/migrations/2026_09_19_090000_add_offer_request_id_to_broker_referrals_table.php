<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Eylul 2026: kullanicinin bildirdigi gercek mantik hatasi - sitenin
// kendi urettigi gercek talepler (OfferRequest, "Hemen Ucret Sor") ile
// komisyon takip edilen "Yonlendirmeler" (BrokerReferral) tablosu arasinda
// HICBIR baglanti yoktu. Bir aile siteden anlasmali bir kuruma talep
// gonderip teklifini kabul ettiginde, bu hicbir zaman otomatik olarak
// Yonlendirmeler'e dusmuyordu - admin hatirlayip elle girmezse o
// yerlesim/komisyon hic takip edilmiyordu. Bu sutun, OfferRequestNotification
// Service::notifyQuoteAccepted() icinde otomatik olusturulan kayitla
// bu iki sistemi baglar. nullable: eski/elle girilmis kayitlarda yok.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broker_referrals', function (Blueprint $table) {
            $table->foreignId('offer_request_id')->nullable()->unique()->after('facility_id')
                ->constrained('offer_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('broker_referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_request_id');
        });
    }
};
