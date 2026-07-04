<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Faq::query();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        $faqs = $query->orderBy('brand')->orderBy('sort_order')->get();
        $brands = config('brands.brands');

        return view('admin.faqs.index', compact('faqs', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand' => 'required|string',
            'question' => 'required|string|max:300',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Faq::create($data);
        log_admin_event('faq_created', null, ['brand' => $data['brand'], 'question' => $data['question']]);
        Cache::forget("faqs:{$data['brand']}");

        return back()->with('success', 'Soru eklendi.');
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question' => 'required|string|max:300',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? $faq->sort_order;

        $faq->update($data);
        log_admin_event('faq_updated', $faq);
        Cache::forget("faqs:{$faq->brand}");

        return back()->with('success', 'Soru guncellendi.');
    }

    public function destroy(Faq $faq)
    {
        log_admin_event('faq_deleted', $faq, ['question' => $faq->question]);
        Cache::forget("faqs:{$faq->brand}");
        $faq->delete();

        return back()->with('success', 'Soru silindi.');
    }
}
