<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class SiteVisit extends Model
{
    protected $fillable = ['brand', 'visit_date', 'count'];

    protected function casts(): array
    {
        return ['visit_date' => 'date'];
    }

    /**
     * Bugun icin ilgili markanin ziyaret sayacini 1 artirir; gunun ilk
     * ziyaretiyse satiri olusturur. (brand, visit_date) unique oldugu icin
     * ayni gun/marka icin tablo tek satirda kalir.
     */
    public static function recordVisit(string $brand): void
    {
        // 6 Eylul 2026: kullanicinin tekrar bildirdigi "Too many connections"
        // (paylasimli sunucunun 300 baglantilik GENEL limitine - bkz.
        // /_ops/mysql-connections-diagnose - takilmasi) olayinda bu metodun
        // ICINDEKI increment/create cagrisi QueryException firlatiyor ve
        // hicbir try/catch olmadigi icin TUM sayfa istegini 500'e dusuruyordu
        // - sirf bir ziyaret sayaci yazilamadi diye GERCEK bir aile arama
        // sonucunu hic goremiyordu. Bu sadece ikincil/analitik bir yazma,
        // basarisiz olursa sessizce atlanmali (bkz. FacilityController::
        // index() SearchQuery::record() ayni tarihli/turden duzeltme).
        try {
            $today = now()->toDateString();

            $updated = static::where('brand', $brand)->where('visit_date', $today)->increment('count');

            if ($updated) {
                return;
            }

            try {
                static::create(['brand' => $brand, 'visit_date' => $today, 'count' => 1]);
            } catch (QueryException $e) {
                // Ayni anda baska bir istek satiri olusturmus olabilir (yaris durumu);
                // bu durumda basitce artir, hata firlatma.
                static::where('brand', $brand)->where('visit_date', $today)->increment('count');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Site ziyareti kaydedilemedi: '.$e->getMessage(), ['brand' => $brand]);
        }
    }
}
