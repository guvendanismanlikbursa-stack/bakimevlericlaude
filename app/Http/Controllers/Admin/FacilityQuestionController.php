<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FacilityQuestion;
use Illuminate\Http\Request;

// Moderasyon: aile sorulari/kurum cevaplari uygunsuzsa admin silebilir.
class FacilityQuestionController extends Controller
{
    use RedirectsOutOfRangePagination;

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

        if ($redirect = $this->redirectIfPageOutOfRange($request, $questions)) {
            return $redirect;
        }

        $brands = config('brands.brands');

        return view('admin.questions.index', compact('questions', 'brands'));
    }

    public function destroy(FacilityQuestion $question)
    {
        $facilityName = $question->facility->name ?? 'kurum';
        log_admin_event('facility_question_deleted', $question, ['question' => $question->question]);
        notify_user($question->familyUser, 'question_removed', 'Sorunuz kaldırıldı', "\"{$facilityName}\" kurumuna sorduğunuz soru platformdan kaldırıldı.");
        $question->delete();

        return back()->with('success', 'Soru/cevap silindi.');
    }
}
