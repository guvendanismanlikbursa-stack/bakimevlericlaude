<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->string('applicant_ip', 45)->nullable()->after('applicant_phone');
        });
    }

    public function down(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropColumn('applicant_ip');
        });
    }
};
