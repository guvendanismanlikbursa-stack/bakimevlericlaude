<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityQuestion;
use Illuminate\Http\Request;

// Moderasyon: aile sorulari/kurum cevaplari uygunsuzsa admin silebilir.
class FacilityQuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = FacilityQuestion::with('facility')->latest();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $questions = $query->paginate(20)->withQueryString();
        $brands = config('brands.brands');

        return view('admin.questions.index', compact('questions', 'brands'));
    }

    public function destroy(FacilityQuestion $question)
    {
        log_admin_event('facility_question_deleted', $question, ['question' => $question->question]);
        $question->delete();

        return back()->with('success', 'Soru/cevap silindi.');
    }
}
