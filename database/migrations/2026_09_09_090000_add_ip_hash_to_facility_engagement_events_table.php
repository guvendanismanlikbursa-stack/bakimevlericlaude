<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 9 Eylul 2026: kullanicinin "en cok tiklanan kurumlar sayaci dogru mu"
// sorusu uzerine bulundu - "gercek tiklama" tekillestirmesi SADECE tarayici
// oturum cerezine (session) dayaniyordu. Cerez saklamayan bot/otomatik
// araclar icin HER istek "yeni ziyaretci" gibi gorunup sayiliyordu (canli
// kanit: bir kurumda 67 saniyede 3 ayri "gercek tiklama"). Bu, cerezden
// BAGIMSIZ, IP'ye dayali (ham IP DEGIL, tuzlanmis hash - gizlilik icin)
// ikinci bir tekillestirme katmani ekler - session VEYA ip_hash'ten HERHANGI
// biri son 24 saatte ayni kurum icin zaten kayitliysa yeni olay yazilmaz.
//
// GUVENLIK: ilk calistirmada varsayilan index adi 64 karakter MySQL
// sinirini astigi icin hata verdi - kisa, elle verilmis bir index adi
// kullanildi. Sutun EKLENMIS olabilecegi icin (ilk denemede basarili oldu,
// sadece index adiminda hata verdi) burada varlik kontrolu yapiliyor.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('facility_engagement_events', 'ip_hash')) {
            Schema::table('facility_engagement_events', function (Blueprint $table) {
                $table->string('ip_hash', 64)->nullable()->after('type');
            });
        }

        Schema::table('facility_engagement_events', function (Blueprint $table) {
            $table->index(['facility_id', 'type', 'ip_hash', 'created_at'], 'fee_facility_type_iphash_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('facility_engagement_events', function (Blueprint $table) {
            $table->dropIndex('fee_facility_type_iphash_created_idx');
            $table->dropColumn('ip_hash');
        });
    }
};
