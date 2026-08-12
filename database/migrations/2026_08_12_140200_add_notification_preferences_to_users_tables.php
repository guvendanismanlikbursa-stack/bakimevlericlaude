<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 12 Agustos 2026: kullanicinin talebi - "hangi olaylar icin e-posta/push
// gelsin secemiyorum, hepsi ya acik ya kapali". Bildirim TURU bazinda
// (ör. yeni_mesaj, yeni_teklif) e-posta/push acik-kapali tercihini JSON
// olarak saklar - bkz. app/helpers.php notify_user() ve
// notification_preference_enabled().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('phone');
        });

        Schema::table('facility_users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });

        Schema::table('facility_users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
