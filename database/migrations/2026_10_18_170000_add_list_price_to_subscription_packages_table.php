<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 18 Ekim 2026: kullanıcının talebi - ilk yıldan sonra uygulanacak gerçek
// liste fiyatı; doluysa teklif sayfasında ve panelde üstü çizili gösterilir,
// asıl fiyat "lansman fiyatı" olarak vurgulanır. Boşsa hiçbir şey değişmez.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->decimal('list_price', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('list_price');
        });
    }
};
