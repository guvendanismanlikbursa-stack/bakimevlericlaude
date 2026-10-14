@extends('admin.layout')
@section('title', 'Pusula Anketi Detayı')

@section('content')
<a href="{{ route('admin.care-advisor-leads.index') }}" class="text-sm text-gray-500">← Pusula Anketleri'ne dön</a>

<div class="flex items-center gap-2 flex-wrap mt-2 mb-4">
  <h1 class="text-2xl font-bold">{{ $lead->name ?: 'İsim girilmemiş' }}</h1>
  @if($lead->completed_at)
    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold">✅ Tamamlandı · {{ $lead->completed_at->format('d.m.Y H:i') }}</span>
  @else
    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-semibold">⏳ Yarıda kaldı</span>
  @endif
</div>

@if(session('success'))
  <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
@endif

<div class="grid md:grid-cols-3 gap-6">
  <div class="md:col-span-2 bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-bold text-gray-900 mb-4">Bilgileri Düzenle</h2>
    <form method="POST" action="{{ route('admin.care-advisor-leads.update', $lead) }}" class="space-y-4">
      @csrf @method('PUT')
      <div class="grid sm:grid-cols-2 gap-4">
        <label class="block"><span class="text-xs font-bold text-gray-500">Adı Soyadı</span>
          <input type="text" name="name" value="{{ old('name', $lead->name) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">Telefon</span>
          <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">İl</span>
          <select name="city_id" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 bg-white">
            <option value="">Belirtilmemiş</option>
            @foreach($cities as $city)<option value="{{ $city->id }}" @selected(old('city_id', $lead->city_id) == $city->id)>{{ $city->name }}</option>@endforeach
          </select>
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">İlçe</span>
          <input type="text" name="district" value="{{ old('district', $lead->district) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">Kurum türü</span>
          <select name="facility_category_id" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 bg-white">
            <option value="">Belirtilmemiş</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('facility_category_id', $lead->facility_category_id) == $category->id)>{{ $category->name }}</option>@endforeach
          </select>
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">Bölüm</span>
          <select name="section" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 bg-white">
            <option value="">Belirtilmemiş</option>
            @foreach(config('brands.service_sections') as $slug => $section)<option value="{{ $slug }}" @selected(old('section', $lead->section) === $slug)>{{ $section['title'] }}</option>@endforeach
          </select>
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">Yaş</span>
          <input type="number" name="patient_age" value="{{ old('patient_age', $lead->patient_age) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">Cinsiyet</span>
          <input type="text" name="gender" value="{{ old('gender', $lead->gender) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
        </label>
        <label class="block"><span class="text-xs font-bold text-gray-500">Bütçe (üst sınır)</span>
          <input type="number" name="budget_max" value="{{ old('budget_max', $lead->budget_max) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
        </label>
      </div>
      <label class="block"><span class="text-xs font-bold text-gray-500">Durum / hastalık</span>
        <input type="text" name="condition" value="{{ old('condition', $lead->condition) }}" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2">
      </label>
      <button type="submit" class="bg-gray-900 text-white rounded-lg px-5 py-2.5 text-sm font-bold">Kaydet</button>
    </form>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-bold text-gray-900 mb-3">Diğer Bilgiler</h2>
    <dl class="text-sm space-y-2 text-gray-700">
      <div><dt class="text-gray-400 text-xs">Bölüm</dt><dd>{{ config('brands.service_sections.'.$lead->section.'.title') ?? '—' }}</dd></div>
      <div><dt class="text-gray-400 text-xs">Marka</dt><dd>{{ $lead->brand ?: '—' }}</dd></div>
      <div><dt class="text-gray-400 text-xs">İlgi alanları</dt><dd>
        @forelse($lead->concerns ?? [] as $concern)
          <span class="inline-block bg-gray-100 px-2 py-0.5 rounded-full text-xs mb-1">{{ $concernLabels[$concern] ?? $concern }}</span>
        @empty —
        @endforelse
      </dd></div>
      <div><dt class="text-gray-400 text-xs">Oluşturulma</dt><dd>{{ $lead->created_at->format('d.m.Y H:i') }}</dd></div>
      <div><dt class="text-gray-400 text-xs">Son güncelleme</dt><dd>{{ $lead->updated_at->format('d.m.Y H:i') }}</dd></div>
      <div><dt class="text-gray-400 text-xs">İlerleme kaydı (teknik)</dt><dd class="text-xs text-gray-400 break-all">{{ $lead->progress_token ?: '—' }}</dd></div>
    </dl>

    <form method="POST" action="{{ route('admin.care-advisor-leads.destroy', $lead) }}" onsubmit="return confirm('Bu kayıt kalıcı olarak silinsin mi?');" class="mt-5 pt-4 border-t border-gray-100">
      @csrf @method('DELETE')
      <button type="submit" class="text-red-600 font-semibold text-sm">🗑 Kaydı Sil</button>
    </form>
  </div>
</div>
@endsection
