<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 14 Ekim 2026: kullanıcının talebi - "Pusula Anketleri için bildirim
    // eksikti, tamamla". Admin'e ve eşleşen kurumlara bildirim gönderilirken
    // AYNI kayıt için (sayfa yenileme, "Tam Listeyi Gör" ile tekrar
    // tamamlama vb.) mükerrer bildirim gitmesin diye iki ayrı "ne zaman
    // bildirildi" damgası - admin bildirimi SADECE ilk tamamlanmada bir kez
    // gider, kurum bildirimi ise telefon İLK kez eklendiğinde (ilk
    // tamamlamada zaten varsa hemen, yoksa sonradan "Tam Listeyi Gör" ile
    // eklenince) bir kez gider.
    public function up(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->timestamp('notified_admin_at')->nullable()->after('completed_at');
            $table->timestamp('notified_facility_at')->nullable()->after('notified_admin_at');
        });
    }

    public function down(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->dropColumn(['notified_admin_at', 'notified_facility_at']);
        });
    }
};
