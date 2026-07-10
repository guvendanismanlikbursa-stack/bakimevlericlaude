<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_users', function (Blueprint $table) {
            $table->decimal('signup_lat', 10, 7)->nullable()->after('phone');
            $table->decimal('signup_lng', 10, 7)->nullable()->after('signup_lat');
            $table->string('signup_city_name')->nullable()->after('signup_lng');
            $table->string('signup_ip', 45)->nullable()->after('signup_city_name');
        });
    }

    public function down(): void
    {
        Schema::table('facility_users', function (Blueprint $table) {
            $table->dropColumn(['signup_lat', 'signup_lng', 'signup_city_name', 'signup_ip']);
        });
    }
};
