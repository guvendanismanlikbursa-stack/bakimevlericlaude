<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 6 Kasım 2026: kullanıcının talebi - ön kayıtlı kurumlarda "Ara" / "WhatsApp" düğmesi
// kısa form penceresi açar; talebin hangi düğmeden geldiği (form | arama | whatsapp) saklanır.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('facility_interest_leads', 'source')) {
            Schema::table('facility_interest_leads', function (Blueprint $table) {
                $table->string('source', 20)->nullable()->after('message');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('facility_interest_leads', 'source')) {
            Schema::table('facility_interest_leads', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
