<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // offer_requests artik iki modda calisabilir:
        //  - facility_id doluysa: tek bir kuruma dogrudan ucret talebi
        //  - facility_id bos, city_id/facility_category_id doluysa: Armut tipi "yayin" talebi,
        //    o sehir/kategorideki TUM sahiplenilmis kurumlar gorup teklif (quote) verebilir.
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->foreignId('family_user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('facility_id')->constrained()->nullOnDelete();
            $table->foreignId('facility_category_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
            $table->string('patient_name')->nullable()->after('message'); // hasta/cocuk adi
            $table->string('care_for')->nullable()->after('patient_name'); // kendisi/anne-baba/cocuk vb.
            $table->foreignId('accepted_quote_id')->nullable()->after('status')->constrained('quotes')->nullOnDelete();

            $table->index(['city_id', 'facility_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_user_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('facility_category_id');
            $table->dropConstrainedForeignId('accepted_quote_id');
            $table->dropColumn(['patient_name', 'care_for']);
        });
    }
};
