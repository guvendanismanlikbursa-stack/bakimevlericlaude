<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedInteger('avg_response_minutes')->nullable()->after('rating');
            $table->unsignedInteger('response_sample_count')->default(0)->after('avg_response_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['avg_response_minutes', 'response_sample_count']);
        });
    }
};
