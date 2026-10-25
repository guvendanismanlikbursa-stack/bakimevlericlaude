<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Ekim 2026: kullanıcının talebi - Profesyonel sitede tasarımı admin seçer:
// "klasik" (mevcut) ya da "hareketli". Varsayılan klasik; mevcut siteler değişmez.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['facility_websites', 'expert_websites'] as $tablo) {
            if (! Schema::hasColumn($tablo, 'pro_tasarim')) {
                Schema::table($tablo, function (Blueprint $t) {
                    $t->string('pro_tasarim', 20)->default('klasik');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['facility_websites', 'expert_websites'] as $tablo) {
            if (Schema::hasColumn($tablo, 'pro_tasarim')) {
                Schema::table($tablo, fn (Blueprint $t) => $t->dropColumn('pro_tasarim'));
            }
        }
    }
};
