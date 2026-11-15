<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\FizyoterapistUzmanaTasi;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 15 Kasım 2026: kullanıcının talebi - "bireysel fizyoterapistlerin bu halini sakla, gerektiğinde geri almak kolay olsun;
 * admin paneline bu haline geri getirebilecek bir sekme koy". Taşıma / geri alma işi fizyoterapist:uzmana-tasi komutudur.
 */
class FizyoterapistTasimaController extends Controller
{
    public function index()
    {
        $hazir = Schema::hasColumn('facilities', 'uzmana_tasindi_expert_id');
        $kategoriId = DB::table('facility_categories')->where('slug', 'bireysel-fizyoterapist')->value('id');
        $durum = [
            'uzmanda' => Expert::where('source', FizyoterapistUzmanaTasi::KAYNAK)->count(),
            'kurumda' => $kategoriId ? DB::table('facilities')->where('facility_category_id', $kategoriId)->whereNull('deleted_at')->count() : 0,
            'yonlendirme' => Schema::hasTable('expert_slug_redirects') ? DB::table('expert_slug_redirects')->count() : 0,
            'son_tasima' => Expert::where('source', FizyoterapistUzmanaTasi::KAYNAK)->max('created_at'),
        ];
        $yedekler = collect(glob(storage_path('app/private/kalici_yedekler/*.sql.gz')) ?: [])
            ->map(fn ($y) => ['ad' => basename($y), 'boyut' => round(filesize($y) / 1048576, 1), 'tarih' => date('d.m.Y H:i', filemtime($y))])->values();

        return view('admin.fizyoterapist-tasima.index', compact('hazir', 'durum', 'yedekler'));
    }

    public function geriAl(Request $request)
    {
        $request->validate(['onay' => 'required|in:GERİ AL,GERI AL,geri al']);
        @set_time_limit(600);
        Artisan::call('fizyoterapist:uzmana-tasi', ['--geri-al' => true, '--gercek' => true]);
        $sonuc = trim(Artisan::output());
        log_admin_event('fizyoterapist_kuruma_geri_alindi', null, ['sonuc' => $sonuc]);

        return back()->with('success', 'Fizyoterapistler kurum haline geri döndürüldü. '.$sonuc);
    }

    public function tasi(Request $request)
    {
        $request->validate(['onay' => 'required|in:TAŞI,TASI,taşı,tasi']);
        @set_time_limit(600);
        Artisan::call('fizyoterapist:uzmana-tasi', ['--gercek' => true]);
        $sonuc = trim(Artisan::output());
        log_admin_event('fizyoterapist_uzmana_tasindi', null, ['sonuc' => $sonuc]);

        return back()->with('success', 'Fizyoterapistler Uzmanlar bölümüne taşındı. '.$sonuc);
    }
}
