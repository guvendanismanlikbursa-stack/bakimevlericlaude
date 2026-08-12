<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 12 Agustos 2026: kullanicinin talebi - teklif kabul edildikten bir sure
// sonra aileye otomatik "deneyimini paylaş" davet maili/bildirimi
// gonderilecek (bkz. App\Console\Commands\InviteFamiliesToReview). Bu alan
// AYNI talebe iki kez davet gitmesini engeller.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->timestamp('review_invited_at')->nullable()->after('accepted_quote_id');
        });
    }

    public function down(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropColumn('review_invited_at');
        });
    }
};
