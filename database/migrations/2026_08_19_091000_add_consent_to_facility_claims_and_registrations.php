<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Agustos 2026: kullanicinin bildirdigi gercek eksik - aile kaydinda
// KVKK acik riza onay kutusu (consent_accepted_at/consent_ip) vardi ama
// sahiplenme basvurusunda ve kurum kendi-kaydi basvurusunda YOKTU - kisisel
// veri (ad/e-posta/telefon) onaysiz toplaniyordu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->timestamp('consent_accepted_at')->nullable()->after('applicant_ip');
            $table->string('consent_ip', 45)->nullable()->after('consent_accepted_at');
        });

        Schema::table('facility_registrations', function (Blueprint $table) {
            $table->timestamp('consent_accepted_at')->nullable()->after('applicant_ip');
            $table->string('consent_ip', 45)->nullable()->after('consent_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropColumn(['consent_accepted_at', 'consent_ip']);
        });

        Schema::table('facility_registrations', function (Blueprint $table) {
            $table->dropColumn(['consent_accepted_at', 'consent_ip']);
        });
    }
};
