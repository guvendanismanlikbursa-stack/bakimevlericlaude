<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - ucretli "bakim takip ziyareti"
        // hizmeti (aile, kurumda yatan yakinina periyodik ziyaret+rapor talep
        // eder). SADECE yasli-bakim bolumunde ve SADECE bu anahtar admin
        // tarafindan acik birakilmis kurumlarda gosterilir - kurumun ONAYI
        // olmadan uculu bir ziyaretci gonderilmesi kurumu rahatsiz edebilir
        // (kullanicinin kendi talebi). Varsayilan KAPALI - admin kurumla
        // konusup onay aldiktan sonra acar. Sahiplenme durumundan (is_claimed/
        // is_broker_managed) BAGIMSIZDIR - on kayitli bir kurum da acik olabilir.
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('allows_visit_service')->default(false)->after('is_broker_managed');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('allows_visit_service');
        });
    }
};
