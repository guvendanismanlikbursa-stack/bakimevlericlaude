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

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-gray-500"><tr><th class="p-3 text-left">Kurum</th><th class="p-3 text-left">Yorum</th><th class="p-3 text-left">Puan</th><th class="p-3 text-left">Durum</th><th class="p-3 text-right">İşlem</th></tr></thead>
    <tbody class="divide-y">
      @forelse($reviews as $review)
        <tr>
          <td class="p-3"><div class="font-bold text-gray-900">{{ $review->facility?->name }}</div><div class="text-xs text-gray-500">{{ $review->brand }} · {{ $review->facility?->city?->name }}</div></td>
          <td class="p-3"><div class="font-semibold">{{ $review->reviewer_name }}</div><div class="text-gray-600 max-w-xl">{{ $review->body ?: '-' }}</div></td>
          <td class="p-3 text-amber-500 font-bold">★ {{ $review->rating }}</td>
          <td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $review->status === 'approved' ? 'bg-green-100 text-green-700' : ($review->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ $review->status }}</span></td>
          <td class="p-3 text-right">
            <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="inline-flex gap-2">@csrf @method('PUT')<select name="status" class="border rounded px-2 py-1 text-xs"><option value="pending" @selected($review->status==='pending')>Bekliyor</option><option value="approved" @selected($review->status==='approved')>Onaylı</option><option value="rejected" @selected($review->status==='rejected')>Reddedildi</option></select><button class="bg-gray-900 text-white rounded px-3 py-1 text-xs font-bold">Kaydet</button></form>
            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="inline">@csrf @method('DELETE')<button class="text-red-600 text-xs font-bold ml-2">Sil</button></form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="p-8 text-center text-gray-500">Yorum bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection