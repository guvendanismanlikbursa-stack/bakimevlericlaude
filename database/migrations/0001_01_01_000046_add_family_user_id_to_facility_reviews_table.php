<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Yorum, sadece bu kurumdan daha once ucret/teklif talebi gondermis bir aile
// hesabina baglanabilir; bu yuzden yorumu kimin yazdigini takip etmemiz gerekiyor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->foreignId('family_user_id')->nullable()->after('facility_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_user_id');
        });
    }
};
