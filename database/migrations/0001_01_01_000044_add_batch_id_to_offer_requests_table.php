<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Toplu Fiyat Al": aile ayni anda en fazla 5 kuruma talep gonderirse, bu
// N ayri offer_requests satirini ayni batch_id (uuid) ile isaretler. Aile
// panelinde ayri ayri gorunurler (bilerek — talep uzerine), ama admin
// panelinde ayni toplu talepten geldikleri gruplanip gosterilebilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->after('accepted_quote_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
