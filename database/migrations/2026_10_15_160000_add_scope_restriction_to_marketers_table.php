<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->json('assigned_city_ids')->nullable()->after('is_logged_in');
            $table->json('assigned_districts')->nullable()->after('assigned_city_ids');
            $table->json('assigned_category_ids')->nullable()->after('assigned_districts');
        });
    }

    public function down(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->dropColumn(['assigned_city_ids', 'assigned_districts', 'assigned_category_ids']);
        });
    }
};
