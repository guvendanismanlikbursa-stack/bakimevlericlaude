<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 18 Ekim 2026: kullanıcının talebi - pazarlamacı CRM'inde "Teklif Sayfasını
// Gör" düğmesi; her pazarlamacı için admin açıp kapatır (varsayılan kapalı).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->boolean('can_view_offer_page')->default(false)->after('assigned_category_ids');
        });
    }

    public function down(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->dropColumn('can_view_offer_page');
        });
    }
};
