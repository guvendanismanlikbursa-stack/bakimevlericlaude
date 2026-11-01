<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BotGozcu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 1 Kasım 2026: kullanıcının talebi - şüpheli botları admin panelinden (mobil dahil) engelleyip kaldırabilmek.
class BotEngelController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'asn' => ['nullable', 'integer', 'min:1'],
            'ag' => ['nullable', 'string', 'max:64'],
            'firma' => ['nullable', 'string', 'max:150'],
            'sorgu' => ['nullable', 'string', 'max:64'],
        ]);
        $asn = $data['asn'] ?? null;
        $ag = $data['ag'] ?? null;
        $firma = $data['firma'] ?? null;
        // Elle giriş: "AS51167", "51167" ya da bir IP adresi.
        if (! $asn && ! $ag && filled($data['sorgu'] ?? null)) {
            $s = trim($data['sorgu']);
            if (preg_match('/^(?:AS)?(\d{1,10})$/i', $s, $m)) {
                $asn = (int) $m[1];
            } elseif (filter_var($s, FILTER_VALIDATE_IP)) {
                $bilgi = BotGozcu::ipBilgisi($s);
                $asn = $bilgi['asn'];
                $firma = $bilgi['firma'];
                if (! $asn) {
                    $ag = \App\Http\Middleware\BotKalkani::ag($s);
                }
            } else {
                return $this->geri('error', 'Bir IP adresi ya da AS numarası yazın (ör. 185.216.75.10 veya AS51167).');
            }
        }
        try {
            [$ok, $mesaj] = BotGozcu::engelle($asn ? (int) $asn : null, $ag, $firma, (string) session('admin_name', 'Admin'));
        } catch (\Throwable $e) {
            return $this->geri('error', 'Ağ bilgisi alınamadı, biraz sonra tekrar deneyin. ('.mb_substr($e->getMessage(), 0, 120).')');
        }
        if ($ok) {
            log_admin_event('bot_engel_eklendi', null, ['asn' => $asn, 'ag' => $ag, 'firma' => $firma]);
        }

        return $this->geri($ok ? 'success' : 'error', $mesaj);
    }

    public function destroy(int $id)
    {
        $kayit = DB::table('engelli_aglar')->where('id', $id)->first();
        abort_unless($kayit, 404);
        DB::table('engelli_aglar')->where('id', $id)->update(['aktif' => false, 'updated_at' => now()]);
        BotGozcu::onbellegiTemizle();
        log_admin_event('bot_engel_kaldirildi', null, ['id' => $id, 'asn' => $kayit->asn, 'firma' => $kayit->firma]);

        return $this->geri('success', $kayit->firma.' için engel kaldırıldı.');
    }

    private function geri(string $tur, string $mesaj)
    {
        return redirect()->to(route('admin.site-stats.index').'#engelli-aglar')->with($tur, $mesaj);
    }
}
