@extends('admin.layout')
@section('title','Ziyaret Talepleri')
@section('content')
@php $visitStatusLabels = ['new' => 'Yeni', 'contacted' => 'Arandı', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal']; @endphp
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Ziyaret Talepleri</h1>
    <p class="text-sm text-gray-500 mt-1">Kurum ziyaret/randevu taleplerini takip edin.</p>
  </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid md:grid-cols-4 gap-3 mb-6">
  <select name="brand" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm siteler</option>@foreach($brands as $slug => $brand)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $brand['name'] }}</option>@endforeach</select>
  <select name="status" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm durumlar</option>@foreach(['new'=>'Yeni','contacted'=>'Arandı','completed'=>'Tamamlandı','cancelled'=>'İptal'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select>
  <select name="type" class="border rounded-lg px-3 py-2 text-sm"><option value="">Ziyaret + Kontenjan</option><option value="ziyaret" @selected(request('type')==='ziyaret')>Sadece Ziyaret</option><option value="kontenjan" @selected(request('type')==='kontenjan')>Sadece Kontenjan Sorusu</option></select>
  <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Filtrele</button>
  <a href="{{ route('admin.visit-requests.index') }}" class="border rounded-lg px-4 py-2 text-sm font-bold text-center">Temizle</a>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
  @forelse($visitRequests as $visit)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900">{{ $visit->facility?->name }}</div>
          <div class="text-xs text-gray-500">{{ $visit->brand }} · {{ $visit->facility?->city?->name }}</div>
          <span class="inline-block mt-1 rounded-full px-2 py-0.5 text-xs font-bold {{ $visit->type === 'kontenjan' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $visit->type === 'kontenjan' ? 'Kontenjan Sorusu' : 'Ziyaret Talebi' }}</span>
          <span class="rounded-full px-2 py-1 text-xs font-bold ml-1 {{ match($visit->status) { 'completed' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700', 'contacted' => 'bg-blue-100 text-blue-700', default => 'bg-gray-100 text-gray-600' } }}">{{ $visitStatusLabels[$visit->status] ?? $visit->status }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          <form method="POST" action="{{ route('admin.visit-requests.update', $visit) }}" class="inline-flex gap-2 flex-wrap">@csrf @method('PUT')<select name="status" class="border rounded px-2 py-1 text-xs"><option value="new" @selected($visit->status==='new')>Yeni</option><option value="contacted" @selected($visit->status==='contacted')>Arandı</option><option value="completed" @selected($visit->status==='completed')>Tamamlandı</option><option value="cancelled" @selected($visit->status==='cancelled')>İptal</option></select><button class="bg-gray-900 text-white rounded px-3 py-1 text-xs font-bold whitespace-nowrap">Kaydet</button></form>
          <form method="POST" action="{{ route('admin.visit-requests.destroy', $visit) }}" class="inline">@csrf @method('DELETE')<button class="text-red-600 text-xs font-bold whitespace-nowrap">Sil</button></form>
        </div>
      </div>
      <div class="text-sm">
        <div class="font-semibold">{{ $visit->full_name }}</div>
        <div class="text-xs text-gray-500">{{ $visit->phone }} {{ $visit->email ? '· '.$visit->email : '' }} @if($visit->preferred_day) · Tercih: {{ $visit->preferred_day }} {{ $visit->preferred_time }}@endif</div>
        @if($visit->message)<div class="text-gray-600 mt-1">{{ $visit->message }}</div>@endif
      </div>
    </div>
  @empty
    <div class="p-8 text-center text-gray-500">Ziyaret talebi bulunamadı.</div>
  @endforelse
</div>
<div class="mt-6">{{ $visitRequests->links() }}</div>
@endsection