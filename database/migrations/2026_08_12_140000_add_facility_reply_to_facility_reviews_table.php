<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 12 Agustos 2026: kullanicinin talebi - kurum yetkilisi kendi kurumuna
// yazilan yorumlara kamuya acik cevap verebilsin (itibar yonetimi) -
// bkz. Facility\ReviewController::reply().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->text('facility_reply')->nullable()->after('body');
            $table->timestamp('facility_replied_at')->nullable()->after('facility_reply');
        });
    }

    public function down(): void
    {
        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->dropColumn(['facility_reply', 'facility_replied_at']);
        });
    }
};
