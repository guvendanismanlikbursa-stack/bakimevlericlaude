<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityClaim;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Storage;

// 21 Temmuz 2026: kimlik/ruhsat belgesi (sahiplenme basvurusu) ve dekont
// (bakiye yukleme) dosyalari eskiden 'public' diskte tutuluyordu - dosya adi
// tahmin edilemez (random 40 karakter) ama URL herhangi bir sekilde sizarsa
// (tarayici gecmisi, ekran paylasimi, referrer) HICBIR oturum/yetki kontrolu
// olmadan herkese acikti (guvenlik denetiminde bulundu). Artik 'local'
// (storage/app/private, web'den hic erisilemez) diskte tutuluyor, sadece
// admin oturumuyla bu route uzerinden servis ediliyor.
class DocumentController extends Controller
{
    private const SOURCES = [
        'claim' => [FacilityClaim::class, 'document_path'],
        'topup' => [WalletTopup::class, 'receipt_path'],
    ];

    public function show(string $type, int $id)
    {
        abort_unless(isset(self::SOURCES[$type]), 404);
        [$modelClass, $column] = self::SOURCES[$type];

        $record = $modelClass::findOrFail($id);
        $path = $record->{$column};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
