<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Google ile devam et" ile giris yapan misafirin profil fotografi (Google
// profile scope'unda zaten dahil, ek izin gerektirmez) - admin panelinde
// ve widget'ta gosterilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->string('guest_avatar_url')->nullable()->after('guest_age');
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn('guest_avatar_url');
        });
    }
};
