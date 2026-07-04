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
        abort_unless($question->facility_id === $user->facility_id, 403);

        $data = $request->validate(['answer' => 'required|string|max:1500']);

        $question->update([
            'answer' => $data['answer'],
            'answered_by' => $user->id,
            'answered_at' => now(),
            'status' => 'answered',
        ]);

        return back()->with('success', 'Cevabınız yayınlandı.');
    }
}
