<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 22 Ekim 2026: kullanıcının talebi - kurumlarda olduğu gibi (bkz.
// 2026_10_19_180000) doktorların da internet sitesi durumu admin panelinde
// görünsün ve filtrelensin. Durumlar Facility::WEBSITE_STATUSES ile aynı.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->string('website_status', 16)->nullable()->index()->after('google_maps_url');
            $table->string('website_url', 500)->nullable()->after('website_status');
            $table->timestamp('website_checked_at')->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['website_status', 'website_url', 'website_checked_at']);
        });
    }
};
