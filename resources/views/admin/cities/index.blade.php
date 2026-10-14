@extends('admin.layout')
@section('title', 'Şehirler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Şehirler</h1>

<form method="POST" action="{{ route('admin.cities.store') }}" class="flex gap-2 mb-6">
  @csrf
  <input type="text" name="name" placeholder="Yeni şehir adı" required class="border rounded-lg px-3 py-2">
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Ekle</button>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @foreach($cities as $city)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $city->name }} <span class="font-normal text-gray-500">· {{ $city->facilities_count }} kurum</span></div>
        @if(!empty($categoryBreakdown[$city->id]))
          <div class="text-xs text-gray-400 mt-0.5">
            @foreach($categoryBreakdown[$city->id] as $categoryName => $count)
              {{ $categoryName }}: {{ $count }}@if(!$loop->last) · @endif
            @endforeach
          </div>
        @endif
      </div>
      <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button></form>
    </div>
  @endforeach
</div>
@endsection
