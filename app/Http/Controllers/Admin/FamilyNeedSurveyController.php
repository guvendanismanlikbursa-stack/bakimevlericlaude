<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FamilyNeedSurvey;
use App\Models\FamilyNeedSurveySend;
use Illuminate\Http\Request;

// 12 Ekim 2026: kullanicinin talebi - hastanede balon karsiligi toplanan
// kagit anketleri admin panelinden elle girip, konuma/ihtiyaca uygun
// kurumlari otomatik listeleyip WhatsApp'tan elle gonderebilmek icin.
class FamilyNeedSurveyController extends Controller
{
    public function index()
    {
        $surveys = FamilyNeedSurvey::with('city')->withCount('sends')->latest()->paginate(30);

        return view('admin.family-need-surveys.index', compact('surveys'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();
        // bkz. Admin\DataExtractorController::index() ayni tarihli yorum - il/ilce/mahalle secmeli form, AYNI JS/AJAX deseni.
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->id => districts_for_city($city->name)]);

        return view('admin.family-need-surveys.create', compact('cities', 'districtMap'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'city_id' => 'required|exists:cities,id',
            'district' => 'nullable|string|max:120',
            'neighborhood' => 'nullable|string|max:120',
            'age_group' => 'nullable|string|in:'.implode(',', array_keys(FamilyNeedSurvey::AGE_GROUPS)),
            'service_type' => 'required|string|in:'.implode(',', array_keys(FamilyNeedSurvey::SERVICE_TYPES)),
            'urgency' => 'nullable|string|in:'.implode(',', array_keys(FamilyNeedSurvey::URGENCIES)),
            'ownership_preference' => 'nullable|string|in:'.implode(',', array_keys(FamilyNeedSurvey::OWNERSHIP_PREFERENCES)),
        ]);
        $data['created_by_admin_id'] = session('admin_id');

        $survey = FamilyNeedSurvey::create($data);

        return redirect()->route('admin.family-need-surveys.index')->with('success', "Anket kaydedildi. {$survey->matchingFacilities()->count()} uygun kurum bulundu.");
    }

    public function destroy(FamilyNeedSurvey $familyNeedSurvey)
    {
        $familyNeedSurvey->delete();

        return back()->with('success', 'Anket silindi.');
    }

    // 12 Ekim 2026: bkz. FacilityInvitationController::openWhatsapp() AYNI
    // desen - WhatsApp resmi otomasyon yasagi nedeniyle admin ELLE tetikler,
    // wa.me linki onceden doldurulmus mesajla yeni sekmede acilir.
    public function whatsapp(FamilyNeedSurvey $familyNeedSurvey, Facility $facility)
    {
        $serviceLabel = FamilyNeedSurvey::SERVICE_TYPES[$familyNeedSurvey->service_type] ?? $familyNeedSurvey->service_type;
        $place = $familyNeedSurvey->neighborhood ? "{$familyNeedSurvey->neighborhood} / {$familyNeedSurvey->district}" : $familyNeedSurvey->district;

        $message = "Merhaba, bakimevleri.com'dan arıyoruz. {$place} bölgesinde {$serviceLabel} arayan bir aile ile görüştük. "
            ."Kurumunuz bu bölgeye uygun göründüğü için haber vermek istedik - detaylı bilgi için sizi arayabilir miyiz?";

        $url = facility_whatsapp_url_with_message($facility, $message);
        abort_if(! $url, 404, 'Bu kurumun cep telefonu yok.');

        // 12 Ekim 2026: kullanicinin talebi - hangi ankete gore hangi kuruma
        // gonderildigi + o kuruma toplam kac anket gonderildigi takip edilsin.
        FamilyNeedSurveySend::create([
            'family_need_survey_id' => $familyNeedSurvey->id,
            'facility_id' => $facility->id,
            'sent_by_admin_id' => session('admin_id'),
        ]);

        return redirect()->away($url);
    }
}
