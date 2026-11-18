<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 18 Kasım 2026: kullanıcının kararı - aile kaydında ad soyad, telefon, ŞEHİR ve YAŞ zorunlu.
// Şehir mevcut signup_city_name sütununa yazılır; yaş için yeni sütun (eski kayıtlarda boş kalır).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('family_users', 'age')) {
            Schema::table('family_users', function (Blueprint $table) {
                $table->unsignedTinyInteger('age')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('family_users', 'age')) {
            Schema::table('family_users', function (Blueprint $table) {
                $table->dropColumn('age');
            });
        }
    }
};
