<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Acik riza metni onay kaydi (KVKK uyumluluk icin: ne zaman, hangi IP'den
// onaylandi) ve onayla birlikte alinan yaklasik konum bilgisi.
// Konum tarayici Geolocation API'siyle, sadece kullanici izin verirse alinir;
// izin verilmezse bu alanlar bos kalir, kayit engellenmez.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->timestamp('consent_accepted_at')->nullable()->after('password');
            $table->string('consent_ip', 45)->nullable()->after('consent_accepted_at');
            $table->decimal('signup_lat', 10, 7)->nullable()->after('consent_ip');
            $table->decimal('signup_lng', 10, 7)->nullable()->after('signup_lat');
            $table->string('signup_city_name', 100)->nullable()->after('signup_lng');
        });
    }

    public function down(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->dropColumn(['consent_accepted_at', 'consent_ip', 'signup_lat', 'signup_lng', 'signup_city_name']);
        });
    }
};
