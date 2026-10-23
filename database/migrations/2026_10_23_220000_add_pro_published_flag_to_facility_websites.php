<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 23 Ekim 2026: kullanıcının talebi - "Profesyonel'e geçir" düğmesi taslak
// siteyi yayına da alıyordu; "Profesyoneli kapat" ise yalnızca paketi
// kapatıp siteyi yayında bırakıyordu. Bu alan, siteyi düğmenin yayına
// aldığını hatırlar; kapatınca site eski haline (taslağa) döner.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_websites', function (Blueprint $table) {
            $table->boolean('pro_yayina_aldi')->default(false)->after('pro_until');
        });

        // Kullanıcının onayıyla: bu düğmeyle denenip yayında kalan
        // Tuzla Ergoterapi Merkezi (kurum #15536) sitesi taslağa alınır.
        DB::table('facility_websites')->where('facility_id', 15536)->where('plan', 'standart')->update(['status' => 'taslak']);
    }

    public function down(): void
    {
        Schema::table('facility_websites', function (Blueprint $table) {
            $table->dropColumn('pro_yayina_aldi');
        });
    }
};
