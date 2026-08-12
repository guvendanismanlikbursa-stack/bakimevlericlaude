<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 21 Temmuz 2026: WalletTopupController::approve() bonus kredi miktarini
// subscription_packages.bonus_quote_credits'ten CANLI okuyordu - admin
// kurum odeme yaptiktan/pending kayit olustuktan SONRA paketi duzenlerse
// (veya sonradan silerse, hic soft-delete olmadigi icin) kurum vaat edilenden
// farkli (veya sifir) kredi alabiliyordu, sessizce. amount (fiyat) zaten
// olusturma aninda satira yazilip donduruluyordu (dogru); bonus_quote_credits
// icin de ayni "satin alma aninda dondur" deseni burada uygulaniyor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->unsignedInteger('bonus_quote_credits_snapshot')->nullable()->after('subscription_package_id');
            $table->string('package_name_snapshot')->nullable()->after('bonus_quote_credits_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->dropColumn(['bonus_quote_credits_snapshot', 'package_name_snapshot']);
        });
    }
};
