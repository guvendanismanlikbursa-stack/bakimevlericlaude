<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 24 Ekim 2026: hata #255 - bir kurum yetkilisi Google ile girerken Google'ın
// verdiği profil fotoğrafı adresi ~1.400 karakterdi; avatar_url sütunu 255
// karakter olduğu için kayıt patladı ve giriş yapılamadı. Aynı risk aile
// hesaplarında ve canlı destek sohbetinde de var; sütunlar TEXT'e genişletilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_users', fn (Blueprint $t) => $t->text('avatar_url')->nullable()->change());
        Schema::table('family_users', fn (Blueprint $t) => $t->text('avatar_url')->nullable()->change());
        Schema::table('chat_threads', fn (Blueprint $t) => $t->text('guest_avatar_url')->nullable()->change());
    }

    public function down(): void
    {
        // Geri alma uzun adresleri keseceği için yapılmaz.
    }
};
