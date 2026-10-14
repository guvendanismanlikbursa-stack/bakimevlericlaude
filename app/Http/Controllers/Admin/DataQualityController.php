<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DataQualityService;
use Illuminate\Http\Request;

// 13 Agustos 2026: kullanicinin talebi - daha once bu denetimler sadece
// benim /_ops/{action} uzerinden calistirabildigim, admin panelinde hicbir
// butonu olmayan araclardi. Bu ekran, ayni mantigi (bkz. DataQualityService)
// admin'in kendi basina tiklayarak calistirabilecegi bir sayfaya tasir.
class DataQualityController extends Controller
{
    public function index(DataQualityService $service)
    {
        $miscategory = $service->miscategoryScan();
        $phoneType = $service->phoneTypeAudit();
        $ownership = $service->ownershipAudit();
        $district = $service->districtAudit();
        $nameCleanup = $service->nameCleanupAudit();
        $izmirOct1Duplicates = $service->izmirOct1DuplicateAudit();
        $bursaIstanbulOct1Duplicates = $service->bursaIstanbulOct1DuplicateAudit();
        $devletKurumu = $service->devletKurumuAudit();
        $governmentName = $service->governmentNameAudit();
        $addressCityMismatch = $service->addressCityMismatchAudit();
        $antalyaOct6Duplicates = $service->antalyaOct6DuplicateAudit();

        return view('admin.data-quality.index', compact(
            'miscategory', 'phoneType', 'ownership', 'district', 'nameCleanup', 'izmirOct1Duplicates', 'bursaIstanbulOct1Duplicates', 'devletKurumu', 'governmentName', 'addressCityMismatch', 'antalyaOct6Duplicates'
        ));
    }

    public function fixMiscategory(DataQualityService $service)
    {
        $result = $service->miscategoryFix();
        log_admin_event('data_quality_miscategory_fix', null, $result);

        return back()->with('success', "Kategori düzeltildi: {$result['fixed']} kurum.");
    }

    public function fixPhoneType(DataQualityService $service)
    {
        $result = $service->phoneTypeFix();
        log_admin_event('data_quality_phone_type_fix', null, $result);

        return back()->with('success', "Telefon türü düzeltildi: {$result['fixedPhoneType']} kurum.");
    }

    public function fixDistrict(DataQualityService $service)
    {
        $result = $service->districtFix();
        log_admin_event('data_quality_district_fix', null, $result);

        return back()->with('success', "İlçe metni dolduruldu: {$result['fixed']} kurum.");
    }

    public function fixNameCleanup(DataQualityService $service)
    {
        $result = $service->nameCleanupFix();
        log_admin_event('data_quality_name_cleanup_fix', null, $result);

        return back()->with('success', "Kurum ismi temizlendi: {$result['fixed']} kurum.");
    }

    public function fixIzmirOct1Duplicates(
        DataQualityService $service,
        \App\Services\FacilityArchiveService $archiveService,
        \App\Services\FacilityCascadeService $cascadeService
    ) {
        $rows = $service->izmirOct1DuplicateAudit()['rows'];
        $deleted = 0;

        foreach ($rows as $row) {
            $facility = \App\Models\Facility::find($row->id);
            if (! $facility) {
                continue;
            }
            $archiveService->archiveBeforeDelete($facility);
            \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);
            $cascadeService->softDeleteRelated($facility);
            $facility->delete();
            $deleted++;
        }

        log_admin_event('data_quality_izmir_oct1_duplicates_fix', null, ['deleted' => $deleted]);

        return back()->with('success', "Mükerrer kurum silindi: {$deleted}.");
    }

    public function fixBursaIstanbulOct1Duplicates(
        DataQualityService $service,
        \App\Services\FacilityArchiveService $archiveService,
        \App\Services\FacilityCascadeService $cascadeService
    ) {
        $rows = $service->bursaIstanbulOct1DuplicateAudit()['rows'];
        $deleted = 0;

        foreach ($rows as $row) {
            $facility = \App\Models\Facility::find($row->id);
            if (! $facility) {
                continue;
            }
            $archiveService->archiveBeforeDelete($facility);
            \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);
            $cascadeService->softDeleteRelated($facility);
            $facility->delete();
            $deleted++;
        }

        log_admin_event('data_quality_bursa_istanbul_oct1_duplicates_fix', null, ['deleted' => $deleted]);

        return back()->with('success', "Mükerrer kurum silindi: {$deleted}.");
    }

    public function fixAntalyaOct6Duplicates(
        DataQualityService $service,
        \App\Services\FacilityArchiveService $archiveService,
        \App\Services\FacilityCascadeService $cascadeService
    ) {
        $rows = $service->antalyaOct6DuplicateAudit()['rows'];
        $deleted = 0;

        foreach ($rows as $row) {
            $facility = \App\Models\Facility::find($row->id);
            if (! $facility) {
                continue;
            }
            $archiveService->archiveBeforeDelete($facility);
            \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);
            $cascadeService->softDeleteRelated($facility);
            $facility->delete();
            $deleted++;
        }

        log_admin_event('data_quality_antalya_oct6_duplicates_fix', null, ['deleted' => $deleted]);

        return back()->with('success', "Mükerrer kurum silindi: {$deleted}.");
    }

    public function fixAddressCityMismatch(
        DataQualityService $service,
        \App\Services\FacilityArchiveService $archiveService,
        \App\Services\FacilityCascadeService $cascadeService
    ) {
        $rows = $service->addressCityMismatchAudit()['rows'];
        $deleted = 0;

        foreach ($rows as $row) {
            $facility = \App\Models\Facility::find($row->id);
            if (! $facility) {
                continue;
            }
            $archiveService->archiveBeforeDelete($facility);
            \App\Models\FacilityUser::where('facility_id', $facility->id)->update(['status' => 'suspended']);
            $cascadeService->softDeleteRelated($facility);
            $facility->delete();
            $deleted++;
        }

        log_admin_event('data_quality_address_city_mismatch_fix', null, ['deleted' => $deleted]);

        return back()->with('success', "Adresi başka ili işaret eden kurum silindi: {$deleted}.");
    }

    public function fixGovernmentName(DataQualityService $service)
    {
        $result = $service->governmentNameFix();
        log_admin_event('data_quality_government_name_fix', null, $result);

        return back()->with('success', "Kamu olarak işaretlenen kurum: {$result['fixed']}.");
    }

    public function fixOwnership(Request $request, DataQualityService $service)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'type' => 'required|string|in:ozel,kamu,belediye,vakif',
        ]);

        $result = $service->ownershipFix((int) $data['id'], $data['type']);
        log_admin_event('data_quality_ownership_fix', null, $result);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
