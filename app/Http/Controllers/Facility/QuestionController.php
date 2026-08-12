<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityQuestion;
use App\Models\FacilityUser;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $questions = FacilityQuestion::where('facility_id', $user->facility_id)->latest()->paginate(20);

        return view('themes._shared.facility.questions', compact('questions'));
    }

    public function answer(Request $request, FacilityQuestion $question)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        abort_unless((int) $question->facility_id === (int) $user->facility_id, 403);

        $data = $request->validate(['answer' => 'required|string|max:1500']);

        $question->update([
            'answer' => $data['answer'],
            'answered_by' => $user->id,
            'answered_at' => now(),
            'status' => 'answered',
        ]);

        // 21 Temmuz 2026: aile soru sordugunda kurum bildirim aliyordu (bkz.
        // Public/FacilityQuestionController::store) ama ters yonde hicbir
        // bildirim yoktu - aile cevabi gormek icin kurum sayfasini tekrar
        // ziyaret etmeyi hatirlamak zorundaydi. Soru anonim sorulmus olabilir
        // (family_user_id null, "Ziyaretci") - o durumda bildirilecek hesap yok.
        if ($question->family_user_id) {
            $question->loadMissing('facility');
            notify_user(
                \App\Models\FamilyUser::find($question->family_user_id),
                'question_answered',
                'Sorunuz yanıtlandı',
                ($question->facility?->name ?? 'Kurum').' sorunuza cevap verdi.',
                ['facility_slug' => $question->facility?->slug]
            );
        }

        return back()->with('success', 'Cevabınız yayınlandı.');
    }
}
