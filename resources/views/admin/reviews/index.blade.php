@extends('admin.layout')
@section('title','Yorumlar')
@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Yorumlar</h1>
    <p class="text-sm text-gray-500 mt-1">Kurum yorumlarını onaylayın, reddedin veya silin.</p>
  </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid md:grid-cols-4 gap-3 mb-6">
  <select name="brand" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm siteler</option>@foreach($brands as $slug => $brand)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $brand['name'] }}</option>@endforeach</select>
  <select name="status" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm durumlar</option>@foreach(['pending'=>'Bekliyor','approved'=>'Onaylı','rejected'=>'Reddedildi'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select>
  <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Filtrele</button>
  <a href="{{ route('admin.reviews.index') }}" class="border rounded-lg px-4 py-2 text-sm font-bold text-center">Temizle</a>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
  @forelse($reviews as $review)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900">{{ $review->facility?->name }}</div>
          <div class="text-xs text-gray-500">{{ $review->brand }} · {{ $review->facility?->city?->name }}</div>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="inline-flex gap-2 flex-wrap">@csrf @method('PUT')<select name="status" class="border rounded px-2 py-1 text-xs"><option value="pending" @selected($review->status==='pending')>Bekliyor</option><option value="approved" @selected($review->status==='approved')>Onaylı</option><option value="rejected" @selected($review->status==='rejected')>Reddedildi</option></select><button class="bg-gray-900 text-white rounded px-3 py-1 text-xs font-bold whitespace-nowrap">Kaydet</button></form>
          <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="inline">@csrf @method('DELETE')<button class="text-red-600 text-xs font-bold whitespace-nowrap">Sil</button></form>
        </div>
      </div>
      <div>
        <div class="font-semibold text-sm">{{ $review->reviewer_name }} <span class="text-amber-500 font-bold">★ {{ $review->rating }}</span></div>
        <div class="text-gray-600 text-sm break-words">{{ $review->body ?: '-' }}</div>
      </div>
      <span class="inline-block rounded-full px-2 py-1 text-xs font-bold w-fit {{ $review->status === 'approved' ? 'bg-green-100 text-green-700' : ($review->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ['pending' => 'Bekliyor', 'approved' => 'Onaylı', 'rejected' => 'Reddedildi'][$review->status] ?? $review->status }}</span>
    </div>
  @empty
    <div class="p-8 text-center text-gray-500">Yorum bulunamadı.</div>
  @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection