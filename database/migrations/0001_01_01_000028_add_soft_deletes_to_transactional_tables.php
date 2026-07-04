<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// canliyaal projesinden tasindi: "Cop Kutusu" (silinenler / geri yukleme)
// ozelligi icin gerekli soft-delete kolonlari. facilities tablosunda zaten
// vardi (bkz. 0001_01_01_000023); burada islem gecmisi tasiyan diger
// tablolara da ekleniyor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('facility_claims', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
