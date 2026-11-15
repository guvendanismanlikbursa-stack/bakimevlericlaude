<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 15 Kasım 2026: kullanıcının kararı - bireysel fizyoterapistler kurum değil, doktorlar gibi uzman olarak gösterilir
// (reklam yasağı, sade bilgi, doğrudan arama). Taşınan kurum kaydı silinmiş sayılır ama fotoğraf/yorum verisi
// korunur; eski adresler yeni uzman sayfasına 301 ile yönlenir (bkz. App\Console\Commands\FizyoterapistUzmanaTasi).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('facilities', 'uzmana_tasindi_expert_id')) {
            Schema::table('facilities', function (Blueprint $t) {
                $t->unsignedBigInteger('uzmana_tasindi_expert_id')->nullable()->index();
            });
        }
        if (! Schema::hasTable('expert_slug_redirects')) {
            Schema::create('expert_slug_redirects', function (Blueprint $t) {
                $t->id();
                $t->string('old_slug')->unique();
                $t->unsignedBigInteger('expert_id')->index();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_slug_redirects');
        if (Schema::hasColumn('facilities', 'uzmana_tasindi_expert_id')) {
            Schema::table('facilities', function (Blueprint $t) {
                $t->dropIndex(['uzmana_tasindi_expert_id']);
                $t->dropColumn('uzmana_tasindi_expert_id');
            });
        }
    }
};
