<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 29 Eylul 2026: kullanicinin talebi - okul.com.tr'deki gibi, zorunlu KVKK
// acik riza kutusuna EK olarak ISTEGE BAGLI ayri bir "Pazarlama Izni"
// (ticari elektronik ileti) kutusu eklenir. consent_accepted_at ile AYNI
// desen (ne zaman onaylandi, NULL = hic onaylanmadi/geri cekildi).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->timestamp('marketing_consent_at')->nullable()->after('consent_ip');
        });
        Schema::table('facility_registrations', function (Blueprint $table) {
            $table->timestamp('marketing_consent_at')->nullable()->after('consent_ip');
        });
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->timestamp('marketing_consent_at')->nullable()->after('consent_ip');
        });
    }

    public function down(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->dropColumn('marketing_consent_at');
        });
        Schema::table('facility_registrations', function (Blueprint $table) {
            $table->dropColumn('marketing_consent_at');
        });
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropColumn('marketing_consent_at');
        });
    }
};
