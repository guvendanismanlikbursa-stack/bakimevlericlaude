<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            // Sahiplenme ve bakiye/hak sistemi icin alanlar
            $table->boolean('is_claimed')->default(false)->after('is_published');
            $table->timestamp('claimed_at')->nullable()->after('is_claimed');
            $table->unsignedInteger('free_quote_credits')->default(0)->after('claimed_at');
            $table->decimal('balance', 10, 2)->default(0)->after('free_quote_credits');

            $table->index('is_claimed');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['is_claimed', 'claimed_at', 'free_quote_credits', 'balance']);
        });
    }
};
