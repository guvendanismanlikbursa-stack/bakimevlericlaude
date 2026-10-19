<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Ekim 2026: kullanıcının talebi - "kurumlara internet sitesi yapma"
// fikrinin talep görüp görmeyeceğini ölçmek için pazarlamacı görüşmesinde
// iki soru: kurumun web sitesi var mı, site teklifine ilgi gösterdi mi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_marketing_notes', function (Blueprint $table) {
            $table->string('website_status', 20)->nullable()->after('contact_role');
            $table->string('site_offer_interest', 20)->nullable()->after('website_status');
        });
    }

    public function down(): void
    {
        Schema::table('facility_marketing_notes', function (Blueprint $table) {
            $table->dropColumn(['website_status', 'site_offer_interest']);
        });
    }
};
