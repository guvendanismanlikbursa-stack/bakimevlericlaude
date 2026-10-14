@extends('admin.layout')
@section('title', 'Sıkça Sorulan Sorular')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sıkça Sorulan Sorular (SSS)</h1>

<form method="GET" class="mb-4">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
</form>

{{-- 1 Eylul 2026: kullanicinin talebi - backend update() zaten calisiyordu
     ama ekranda "Duzenle" hic yoktu, ContentPageController'daki ?edit=
     deseni buraya da uygulandi. update() 'brand' kabul etmiyor (marka
     olusturulduktan sonra degistirilemez), o yuzden duzenleme modunda
     marka secimi gizlenir. --}}
@if($editingFaq)
  <p class="text-sm font-semibold text-amber-700 mb-2">"{{ \Illuminate\Support\Str::limit($editingFaq->question, 60) }}" düzenleniyor. <a href="{{ route('admin.faqs.index') }}" class="underline">Vazgeç, yeni soru ekle</a></p>
@endif
<form method="POST" action="{{ $editingFaq ? route('admin.faqs.update', $editingFaq) : route('admin.faqs.store') }}" class="bg-white rounded-xl shadow-sm p-5 grid md:grid-cols-2 gap-3 mb-8">
  @csrf
  @if($editingFaq)
    @method('PUT')
  @else
    <select name="brand" required class="border rounded-lg px-3 py-2">
      @foreach($brands as $slug => $b)<option value="{{ $slug }}">{{ $b['name'] }}</option>@endforeach
    </select>
  @endif
  <input type="number" name="sort_order" value="{{ old('sort_order', $editingFaq?->sort_order) }}" placeholder="Sıra (0,10,20...)" class="border rounded-lg px-3 py-2">
  <input type="text" name="question" value="{{ old('question', $editingFaq?->question) }}" placeholder="Soru" required class="border rounded-lg px-3 py-2 md:col-span-2">
  <textarea name="answer" placeholder="Cevap" rows="3" required class="border rounded-lg px-3 py-2 md:col-span-2">{{ old('answer', $editingFaq?->answer) }}</textarea>
  <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingFaq?->is_active ?? true))> Yayında</label>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold md:col-span-2">{{ $editingFaq ? 'Güncelle' : 'Ekle' }}</button>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @foreach($faqs as $faq)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900 break-words">{{ $faq->question }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ $brands[$faq->brand]['name'] ?? $faq->brand }} · Sıra: {{ $faq->sort_order }} · {{ $faq->is_active ? 'Yayında' : 'Pasif' }}</div>
      </div>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        <a href="{{ route('admin.faqs.index', ['edit' => $faq->id]) }}" class="text-primary text-sm font-semibold whitespace-nowrap">Düzenle</a>
        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button></form>
      </div>
    </div>
  @endforeach
</div>
@endsection
