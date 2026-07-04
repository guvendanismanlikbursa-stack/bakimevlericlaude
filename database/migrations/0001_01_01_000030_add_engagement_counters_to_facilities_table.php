<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Kurum Performans Sayfasi" ve "En cok goruntulenen kurumlar" icin sayaclar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('rating')->index();
            $table->unsignedInteger('favorites_count')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['views_count', 'favorites_count']);
        });
    }
};
