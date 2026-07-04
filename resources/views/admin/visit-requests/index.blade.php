@extends('admin.layout')
@section('title','Ziyaret Talepleri')
@section('content')
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

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-gray-500"><tr><th class="p-3 text-left">Kurum</th><th class="p-3 text-left">Kişi</th><th class="p-3 text-left">Tercih</th><th class="p-3 text-left">Durum</th><th class="p-3 text-right">İşlem</th></tr></thead>
    <tbody class="divide-y">
      @forelse($visitRequests as $visit)
        <tr>
          <td class="p-3"><div class="font-bold text-gray-900">{{ $visit->facility?->name }}</div><div class="text-xs text-gray-500">{{ $visit->brand }} · {{ $visit->facility?->city?->name }}</div><span class="inline-block mt-1 rounded-full px-2 py-0.5 text-xs font-bold {{ $visit->type === 'kontenjan' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $visit->type === 'kontenjan' ? 'Kontenjan Sorusu' : 'Ziyaret Talebi' }}</span></td>
          <td class="p-3"><div class="font-semibold">{{ $visit->full_name }}</div><div class="text-xs text-gray-500">{{ $visit->phone }} {{ $visit->email ? '· '.$visit->email : '' }}</div><div class="text-gray-600 mt-1">{{ $visit->message }}</div></td>
          <td class="p-3">{{ $visit->preferred_day ?: '-' }}<div class="text-xs text-gray-500">{{ $visit->preferred_time ?: '' }}</div></td>
          <td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-bold bg-blue-100 text-blue-700">{{ $visit->status }}</span></td>
          <td class="p-3 text-right">
            <form method="POST" action="{{ route('admin.visit-requests.update', $visit) }}" class="inline-flex gap-2">@csrf @method('PUT')<select name="status" class="border rounded px-2 py-1 text-xs"><option value="new" @selected($visit->status==='new')>Yeni</option><option value="contacted" @selected($visit->status==='contacted')>Arandı</option><option value="completed" @selected($visit->status==='completed')>Tamamlandı</option><option value="cancelled" @selected($visit->status==='cancelled')>İptal</option></select><button class="bg-gray-900 text-white rounded px-3 py-1 text-xs font-bold">Kaydet</button></form>
            <form method="POST" action="{{ route('admin.visit-requests.destroy', $visit) }}" class="inline">@csrf @method('DELETE')<button class="text-red-600 text-xs font-bold ml-2">Sil</button></form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="p-8 text-center text-gray-500">Ziyaret talebi bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $visitRequests->links() }}</div>
@endsection