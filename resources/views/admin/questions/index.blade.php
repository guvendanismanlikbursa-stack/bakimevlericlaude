@extends('admin.layout')
@section('title', 'Aile Soruları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Aile Soruları (Moderasyon)</h1>

<form method="GET" class="mb-4 flex gap-3">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    <option value="pending" @selected(request('status')==='pending')>Cevap Bekliyor</option>
    <option value="answered" @selected(request('status')==='answered')>Cevaplandı</option>
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($questions as $q)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $q->facility?->name }}</div>
        <div class="text-sm text-gray-700 mt-0.5">{{ $q->asker_name }}: {{ $q->question }}</div>
        <div class="text-sm text-gray-500 mt-0.5">{{ $q->answer ?: '— bekliyor —' }}</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $q->created_at->format('d.m.Y H:i') }}</div>
      </div>
      <form method="POST" action="{{ route('admin.questions.destroy', $q) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button></form>
    </div>
  @empty
    <div class="p-8 text-center text-gray-500">Kayıt bulunamadı.</div>
  @endforelse
</div>
<div class="mt-6">{{ $questions->links() }}</div>
@endsection
