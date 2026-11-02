<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2 Kasım 2026: CRM'den WhatsApp ile gönderilen site hizmet sözleşmesi, görüşme geçmişine
// kanıt olarak "sözleşme" işaretli kayıt olarak düşer (görüşme sayısına katılmaz, silinemez).
return new class extends Migration
{
    public function up(): void
    {
        foreach (['facility_marketing_notes', 'expert_marketing_notes'] as $tablo) {
            if (! Schema::hasColumn($tablo, 'sozlesme')) {
                Schema::table($tablo, function (Blueprint $table) {
                    $table->boolean('sozlesme')->default(false);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['facility_marketing_notes', 'expert_marketing_notes'] as $tablo) {
            if (Schema::hasColumn($tablo, 'sozlesme')) {
                Schema::table($tablo, function (Blueprint $table) {
                    $table->dropColumn('sozlesme');
                });
            }
        }
    }
};
