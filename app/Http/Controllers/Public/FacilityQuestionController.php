<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use Illuminate\Http\Request;

// "Aile Sorulari": kurum profili altinda herkese acik soru-cevap.
class FacilityQuestionController extends Controller
{
    public function store(Request $request)
    {
        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->where('slug', $request->route('slug'))->firstOrFail();

        $data = $request->validate([
            'question' => 'required|string|max:800',
            'asker_name' => 'nullable|string|max:120',
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

        FacilityUser::where('facility_id', $facility->id)->get()
            ->each(fn (FacilityUser $user) => notify_user($user, 'new_question', 'Yeni bir soru aldınız', $question->question));

        return back()->with('success', 'Sorunuz alındı, kurum yetkilisi yanıtladığında bu sayfada görünecek.');
    }
}
