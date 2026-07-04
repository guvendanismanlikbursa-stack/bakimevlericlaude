<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sahiplenme basvurusu yapan kisinin (izin verirse) tarayici konumu ve
// bunun kurumun koordinatina (varsa) olan mesafesi — sahtecilik kontrolu
// icin admin'e ek bir sinyal. Basvuru izin verilmese de tamamlanir, bu
// alanlar boyle durumlarda bos kalir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->decimal('applicant_lat', 10, 7)->nullable()->after('note');
            $table->decimal('applicant_lng', 10, 7)->nullable()->after('applicant_lat');
            $table->string('applicant_city_name')->nullable()->after('applicant_lng');
            $table->decimal('distance_km', 8, 1)->nullable()->after('applicant_city_name');
        });
    }

    public function down(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropColumn(['applicant_lat', 'applicant_lng', 'applicant_city_name', 'distance_km']);
        });
    }
};
