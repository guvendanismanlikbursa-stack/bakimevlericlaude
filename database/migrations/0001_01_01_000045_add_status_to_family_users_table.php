<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sikayet durumunda admin'in aile hesabini askiya alabilmesi icin.
// FacilityUser'da zaten var olan ayni desen (status: active | suspended).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('phone'); // active | suspended
        });
    }

    public function down(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
