@extends('admin.layout')
@section('title', 'Çöp Kutusu')

@section('content')
<h1 class="text-2xl font-bold mb-6">Çöp Kutusu (Silinenler)</h1>

<div class="flex gap-2 mb-6">
  @foreach($types as $key => $label)
    <a href="{{ route('admin.trash.index', ['type' => $key]) }}" class="px-4 py-2 rounded-lg text-sm font-semibold {{ $activeType === $key ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-600' }}">{{ $label }}</a>
  @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">ID</th><th class="p-3">Özet</th><th class="p-3">Silinme Tarihi</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($items as $item)
        <tr>
          <td class="p-3 text-gray-400">#{{ $item->id }}</td>
          <td class="p-3">{{ $item->name ?? $item->full_name ?? $item->applicant_name ?? ('Kayıt #'.$item->id) }}</td>
          <td class="p-3 text-gray-400">{{ optional($item->deleted_at)->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right whitespace-nowrap space-x-3">
            <form method="POST" action="{{ route('admin.trash.restore', ['type' => $activeType, 'id' => $item->id]) }}" class="inline">@csrf<button class="text-emerald-600 font-semibold">Geri Yükle</button></form>
            <form method="POST" action="{{ route('admin.trash.force-destroy', ['type' => $activeType, 'id' => $item->id]) }}" class="inline" onsubmit="return confirm('Bu kayıt kalıcı olarak silinecek, emin misiniz?');">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Kalıcı Sil</button></form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="4">Bu kategoride silinmiş kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $items->links() }}</div>
@endsection
