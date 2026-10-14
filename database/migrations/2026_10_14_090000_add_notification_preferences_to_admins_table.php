<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 14 Ekim 2026: kullanıcının talebi - "admin tarafındaki mantık
    // çatışmalarının hepsini düzelt". Family/FacilityUser'da zaten var olan
    // bildirim tercih sistemi (bkz. 2026_08_12_140200 migration) admin için
    // hiç yoktu - admin hiçbir bildirim türünü kapatamıyordu. Aynı kolon,
    // aynı JSON yapısı (notification_channel_enabled() helper'ı zaten
    // $notifiable->notification_preferences'i tip bağımsız okuyor).
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
