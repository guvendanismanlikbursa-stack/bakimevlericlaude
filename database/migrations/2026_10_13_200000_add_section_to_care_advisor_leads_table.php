<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 13 Ekim 2026 (9): kullanıcının talebi - admin "Pusula Anketleri"
    // ekranına zamanla bölüm/il/durum bazlı filtreleme gerekecek. Hangi
    // bölümün (yaşlı-bakım/çocuk/rehabilitasyon) sorularıyla doldurulduğu
    // şu ana kadar hiç kaydedilmiyordu (facility_category_id'den güvenilir
    // çıkarılamıyor - kullanıcı "Farketmez" seçerse boş kalıyor). Form zaten
    // gizli bir "bolum" alanı taşıyor (bkz. _quiz-core.blade.php), artık
    // doğrudan kaydediliyor.
    public function up(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->string('section')->nullable()->after('brand');
        });
    }

    public function down(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
