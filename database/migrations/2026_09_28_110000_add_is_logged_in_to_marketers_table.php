<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->boolean('is_logged_in')->default(false)->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->dropColumn('is_logged_in');
        });
    }
};
