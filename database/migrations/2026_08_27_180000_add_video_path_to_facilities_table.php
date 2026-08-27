<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 27 Agustos 2026: kullanicinin talebi - SADECE anlasmali
        // (is_broker_managed) kurumlara ozel tanitim videosu. Fotograf
        // galerisinin aksine (facility_images, coklu) TEK bir video -
        // menu_image_path ile ayni desen. Depolama BILEREK 3 domain'e
        // kopyalanmiyor (kullanicinin acik talebi), bkz.
        // VideoCompressionService ayni tarihli yorum.
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('menu_image_updated_at');
            $table->timestamp('video_updated_at')->nullable()->after('video_path');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'video_updated_at']);
        });
    }
};
