<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 29 Ekim 2026: kullanıcının talebi - demo site linki (WhatsApp ile gönderilen,
// giriş gerektirmeyen, 30 gün geçerli önizleme anahtarı). Bkz. HasDemoLink.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['expert_websites', 'facility_websites'] as $tablo) {
            Schema::table($tablo, function (Blueprint $table) use ($tablo) {
                if (! Schema::hasColumn($tablo, 'demo_token')) {
                    $table->string('demo_token', 64)->nullable();
                    $table->timestamp('demo_until')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['expert_websites', 'facility_websites'] as $tablo) {
            Schema::table($tablo, function (Blueprint $table) {
                $table->dropColumn(['demo_token', 'demo_until']);
            });
        }
    }
};
