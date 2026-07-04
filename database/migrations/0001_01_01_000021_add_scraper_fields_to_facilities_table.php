<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('source')->nullable()->after('email');
            $table->json('source_payload')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['email', 'source', 'source_payload']);
        });
    }
};
