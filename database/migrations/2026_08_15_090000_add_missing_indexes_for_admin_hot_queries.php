<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine yapilan
// veritabani denetiminde bulundu - bu sutunlar admin panelinin HER
// sayfasinda (AppServiceProvider view composer'i - bekleyen sahiplenme/
// bakiye yuklemesi/okunmamis sohbet sayaclari) veya sik kullanilan admin
// akislarinda (WhatsApp davet "siradaki" ekrani) indekssiz filtreleniyordu.
// Su an ~7000 kurum/kucuk yardimci tablolarla fark hissedilmiyor ama veri
// buyudukce (invitation/claim/topup/chat sayisi arttikca) katlanarak
// yavaslar - erken eklemek ucretsiz bir onlem.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->index('unread_by_admin');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->index('ownership_type');
            $table->index('invitation_status');
            // Toplu Fiyat Al / sahiplenme sosyal-kanit sayaci / SEO
            // rehber sayfalarinda birlikte filtrelenen 3'lu - bkz.
            // OfferRequestNotificationService::recipients() ve
            // FacilityClaimController::create().
            $table->index(['city_id', 'facility_category_id', 'is_claimed'], 'facilities_city_category_claimed_index');
        });
    }

    public function down(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropIndex(['unread_by_admin']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex(['ownership_type']);
            $table->dropIndex(['invitation_status']);
            $table->dropIndex('facilities_city_category_claimed_index');
        });
    }
};
