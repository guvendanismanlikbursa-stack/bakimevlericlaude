<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FamilyUser;
use Illuminate\Http\Request;

// "Aile Sorulari": kurum profili altinda herkese acik soru-cevap.
class FacilityQuestionController extends Controller
{
    public function store(Request $request)
    {
        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])
            ->where('is_claimed', true)
            ->where('slug', $request->route('slug'))
            ->firstOrFail();

        $data = $request->validate([
            'question' => 'required|string|max:800',
            'asker_name' => 'nullable|string|max:120',
            // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php
            'website' => 'max:0',
        ]);

        $familyUserId = session('family_user_id');
        $askerName = $data['asker_name'] ?? null;
        if ($familyUserId) {
            $askerName = $askerName ?: FamilyUser::find($familyUserId)?->name;
        }

        $question = $facility->questions()->create([
            'brand' => $brand['slug'],
            'family_user_id' => $familyUserId,
            'asker_name' => $askerName ?: 'Ziyaretçi',
            'question' => $data['question'],
            'status' => 'pending',
        ]);

        // 31 Agustos 2026: kullanicinin talebi - anlasmali (is_broker_managed)
        // kurumlarda TUM aile temaslari (teklif/ziyaret/mesaj) admine gidiyor
        // (bkz. notify_facility_or_broker_admins()), ama "Soru Sor" bu ortak
        // kurala HIC uymuyordu - dogrudan FacilityUser'a gidiyordu, yani
        // anlasmali bir kurumun sorusu admine hic haber vermiyordu.
        notify_facility_or_broker_admins(
            $facility,
            'new_question', 'Yeni bir soru aldınız', $question->question,
            'broker_new_question', 'Anlaşmalı kurum: yeni soru', "\"{$facility->name}\" kurumuna yeni bir soru soruldu: {$question->question}",
        );

        return back()->with('success', 'Sorunuz alındı, kurum yetkilisi yanıtladığında bu sayfada görünecek.');
    }
}
