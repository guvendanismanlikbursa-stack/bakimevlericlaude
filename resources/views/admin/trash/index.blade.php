@extends('admin.layout')
@section('title', 'Çöp Kutusu')

@section('content')
<h1 class="text-2xl font-bold mb-6">Çöp Kutusu (Silinenler)</h1>

<div class="flex gap-2 mb-6">
  @foreach($types as $key => $label)
    <a href="{{ route('admin.trash.index', ['type' => $key]) }}" class="px-4 py-2 rounded-lg text-sm font-semibold {{ $activeType === $key ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-600' }}">{{ $label }}</a>
  @endforeach
</div>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($items as $item)
    <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $item->name ?? $item->full_name ?? $item->applicant_name ?? ('Kayıt #'.$item->id) }}</div>
        <div class="text-xs text-gray-500 mt-0.5">#{{ $item->id }} · Silindi: {{ optional($item->deleted_at)->format('d.m.Y H:i') }}</div>
      </div>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        <form method="POST" action="{{ route('admin.trash.restore', ['type' => $activeType, 'id' => $item->id]) }}" class="inline">@csrf<button class="text-emerald-600 text-sm font-semibold whitespace-nowrap">Geri Yükle</button></form>
        <form method="POST" action="{{ route('admin.trash.force-destroy', ['type' => $activeType, 'id' => $item->id]) }}" class="inline" onsubmit="return confirm('Bu kayıt kalıcı olarak silinecek, emin misiniz?');">@csrf @method('DELETE')<button class="text-red-600 text-sm font-semibold whitespace-nowrap">Kalıcı Sil</button></form>
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Bu kategoride silinmiş kayıt yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $items->links() }}</div>
@endsection
