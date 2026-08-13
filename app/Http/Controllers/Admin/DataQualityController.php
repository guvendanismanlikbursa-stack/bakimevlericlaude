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

        return view('admin.data-quality.index', compact(
            'miscategory', 'phoneType', 'ownership', 'district', 'nameCleanup'
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
