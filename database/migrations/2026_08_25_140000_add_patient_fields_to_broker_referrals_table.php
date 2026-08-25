<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Agustos 2026: kullanicinin talebi - ucretsiz ziyaret hizmeti icin
// (Guven Bakim Hizmetleri sehir hastanesi ekibi tarafindan yapilacak),
// KIME gidilecegi belli olmali. family_name/family_phone zaten var olan
// "basvuruyu yapan aile/iletisim kisisi" alanlari - hasta/sakin AYRI bir
// kisi, bu yuzden ayri alanlar gerekiyor. wants_visit aile bunu istemeyebilir
// diye ayri bir tercih (varsayilan degeri YOK - her kayitta acikca sorulmali).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broker_referrals', function (Blueprint $table) {
            $table->string('patient_name')->nullable()->after('family_phone');
            $table->unsignedTinyInteger('patient_age')->nullable()->after('patient_name');
            $table->string('patient_mobility')->nullable()->after('patient_age');
            $table->boolean('wants_visit')->nullable()->after('patient_mobility');
        });
    }

    public function down(): void
    {
        Schema::table('broker_referrals', function (Blueprint $table) {
            $table->dropColumn(['patient_name', 'patient_age', 'patient_mobility', 'wants_visit']);
        });
    }
};
