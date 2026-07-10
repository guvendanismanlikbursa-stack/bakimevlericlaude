<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_registrations', function (Blueprint $table) {
            $table->decimal('applicant_lat', 10, 7)->nullable()->after('applicant_phone');
            $table->decimal('applicant_lng', 10, 7)->nullable()->after('applicant_lat');
            $table->string('applicant_city_name')->nullable()->after('applicant_lng');
            $table->string('applicant_ip', 45)->nullable()->after('applicant_city_name');
        });
    }

    public function down(): void
    {
        Schema::table('facility_registrations', function (Blueprint $table) {
            $table->dropColumn(['applicant_lat', 'applicant_lng', 'applicant_city_name', 'applicant_ip']);
        });
    }
};
