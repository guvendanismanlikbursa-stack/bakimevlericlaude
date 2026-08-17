<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\OfferRequest;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Storage;

/**
 * 17 Agustos 2026: kullanicinin bildirdigi hata - bir kurum silinince
 * (soft-delete) ona bagli sahiplenme basvurusu/teklif talebi/bakiye
 * yuklemesi kayitlarina HIC dokunulmuyordu, kendi admin listelerinde
 * ("Sahiplenme Basvurulari", "Teklif Talepleri", "Bakiye Yuklemeleri")
 * kurum silinmemis gibi gorunmeye devam ediyordu. facilities.facility_id
 * FK'leri cascadeOnDelete ile tanimli olsa da bu SADECE gercek (hard) DB
 * DELETE'te tetiklenir - Facility SoftDeletes kullandigi icin normal
 * silme bir UPDATE'tir, cascade hic calismaz. Bu servis, kurum silme/
 * geri yukleme/kalici silme islemleriyle bu 3 bagli tabloyu (hepsi zaten
 * SoftDeletes kullaniyor) SENKRON tutar - admin panelindeki tek silme
 * islemiyle tum bagli veri ayni anda cop kutusuna gider, geri yuklenir
 * veya kalici silinir.
 */
class FacilityCascadeService
{
    public function softDeleteRelated(Facility $facility): void
    {
        FacilityClaim::where('facility_id', $facility->id)->delete();
        OfferRequest::where('facility_id', $facility->id)->delete();
        WalletTopup::where('facility_id', $facility->id)->delete();
    }

    public function restoreRelated(Facility $facility): void
    {
        FacilityClaim::onlyTrashed()->where('facility_id', $facility->id)->restore();
        OfferRequest::onlyTrashed()->where('facility_id', $facility->id)->restore();
        WalletTopup::onlyTrashed()->where('facility_id', $facility->id)->restore();
    }

    /**
     * Kurum COP KUTUSUNDAN kalici olarak silindiginde cagirilir - bagli
     * 3 tablonun (o an trashed veya degil, ikisi de) hem fiziksel
     * dosyalarini (belge/dekont) hem kayitlarini kalici siler. offer_
     * requests.facility_id FK'i cascadeOnDelete DEGIL nullOnDelete oldugu
     * icin (broadcast talepler icin facility_id zaten nullable) elle
     * forceDelete gerekiyor - aksi halde kurum silinince facility_id'si
     * null'lanip sanki gercek bir yayin talebiymis gibi ortada kalirdi.
     */
    public function forceDeleteRelated(Facility $facility): void
    {
        foreach (FacilityClaim::withTrashed()->where('facility_id', $facility->id)->get() as $claim) {
            if ($claim->document_path) {
                Storage::disk('local')->delete($claim->document_path);
            }
        }
        FacilityClaim::withTrashed()->where('facility_id', $facility->id)->forceDelete();

        foreach (WalletTopup::withTrashed()->where('facility_id', $facility->id)->get() as $topup) {
            if ($topup->receipt_path) {
                Storage::disk('local')->delete($topup->receipt_path);
            }
        }
        WalletTopup::withTrashed()->where('facility_id', $facility->id)->forceDelete();

        // Bu, veritabaninda gercek bir DELETE oldugu icin quotes/messages
        // (offer_request_id cascadeOnDelete) otomatik olarak silinir.
        OfferRequest::withTrashed()->where('facility_id', $facility->id)->forceDelete();
    }
}
