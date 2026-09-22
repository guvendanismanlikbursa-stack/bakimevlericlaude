<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 22 Eylul 2026: kullanicinin talebi - abonelik ucreti kurum bazinda
// DEGISKEN (sabit aylik/yillik fiyat degil), bu yuzden her kurumun kendi
// ucretini tutan ayri bir alan gerekiyor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->decimal('subscription_amount', 10, 2)->nullable()->after('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('subscription_amount');
        });
    }
};
