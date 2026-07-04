<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Aile e-posta dogrulamasi: veri kalitesi ve guvenlik icin. Dogrulanmamis
// hesap engellenmez (talep toplama hedefini dusurmemek icin), sadece
// panelde hatirlatma gosterilir ve admin panelde durum goruntulenir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
