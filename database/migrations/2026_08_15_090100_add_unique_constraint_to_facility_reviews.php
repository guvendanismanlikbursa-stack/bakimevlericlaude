<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine yapilan
// veritabani denetiminde bulundu - Public\FacilityReviewController::store()
// ayni aile+kurum icin tekrar yorumu SADECE uygulama seviyesinde (exists()
// kontrolu) engelliyordu, ayni saniyede 2 istek (cift tiklama/2 sekme)
// gelirse DB seviyesinde hicbir engel yoktu. quotes tablosunda AYNI riskli
// desen icin (offer_request_id+facility_id) zaten gercek bir unique
// constraint var - facility_reviews'da bu tek eksik nokta idi.
return new class extends Migration
{
    public function up(): void
    {
        // Constraint eklemeden once, uygulama-seviyesi kontrolun eksik
        // oldugu donemden kalmis olabilecek mukerrer satirlari temizle -
        // aksi halde bu migration canli veride mevcut mukerrer kayit varsa
        // basarisiz olur. PHP tarafinda grupla, en eski kaydi (ilk yorum)
        // tut, gerisini sil.
        $groups = DB::table('facility_reviews')
            ->select('id', 'family_user_id', 'facility_id')
            ->whereNotNull('family_user_id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => $row->family_user_id.'-'.$row->facility_id);

        $idsToDelete = [];
        foreach ($groups as $group) {
            if ($group->count() > 1) {
                $idsToDelete = array_merge($idsToDelete, $group->slice(1)->pluck('id')->all());
            }
        }

        if (! empty($idsToDelete)) {
            DB::table('facility_reviews')->whereIn('id', $idsToDelete)->delete();
        }

        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->unique(['family_user_id', 'facility_id'], 'facility_reviews_family_facility_unique');
        });
    }

    public function down(): void
    {
        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->dropUnique('facility_reviews_family_facility_unique');
        });
    }
};
