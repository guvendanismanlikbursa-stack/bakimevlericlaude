<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 27 Eylul 2026: kullanicinin talebi - CRM listesindeki (bkz. Marketer\
// CrmController) sekmeler/filtreler icin hizli erisim. Gercek not gecmisi
// facility_marketing_notes'ta, burada sadece EN SON durumun ozeti tutulur
// (invitation_status ile ayni desen, bkz. FacilityInvitationController).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('marketing_status')->nullable()->after('subscription_amount');
            $table->timestamp('marketing_status_at')->nullable()->after('marketing_status');
            $table->boolean('marketing_brochure_requested')->nullable()->after('marketing_status_at');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['marketing_status', 'marketing_status_at', 'marketing_brochure_requested']);
        });
    }
};
