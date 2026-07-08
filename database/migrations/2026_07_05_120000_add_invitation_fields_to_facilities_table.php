<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('phone_type')->nullable()->after('phone');
            $table->string('invitation_status')->default('not_started')->after('ownership_type');
            $table->timestamp('invitation_status_at')->nullable()->after('invitation_status');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['phone_type', 'invitation_status', 'invitation_status_at']);
        });
    }
};
