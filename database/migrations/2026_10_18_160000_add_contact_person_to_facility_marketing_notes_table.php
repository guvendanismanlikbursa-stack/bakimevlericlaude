<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 18 Ekim 2026: kullanıcının talebi - pazarlamacı görüşme notunda görüştüğü
// kişinin adını ve kurumdaki konumunu (çalışan / müdür / sahibi) kaydedebilsin.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_marketing_notes', function (Blueprint $table) {
            $table->string('contact_name', 120)->nullable()->after('status');
            $table->string('contact_role', 20)->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('facility_marketing_notes', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_role']);
        });
    }
};
