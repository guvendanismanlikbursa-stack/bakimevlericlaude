<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 22 Eylul 2026: kullanicinin talebi - sahiplenilmemis/anlasmali-olmayan
// ("ön kayıtlı") kurumlar icin aylik/yillik ucretli "abonelik" katmani.
// Odeme yapan kurum admin uzerinden telefon/WhatsApp butonlarini ve
// icerik guncellemelerini talep eder - abone_paid_until tarihi tek
// referans kaynagidir, gosterim mantigi bu tarihe canli bakar (ayrica
// "aktif" diye bir alan tutulmuyor, senkron hatasi riski olmasin diye).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false)->after('is_broker_managed');
            $table->string('subscription_plan')->nullable()->after('is_subscribed');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_plan');
            $table->date('subscription_paid_until')->nullable()->after('subscription_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['is_subscribed', 'subscription_plan', 'subscription_started_at', 'subscription_paid_until']);
        });
    }
};
