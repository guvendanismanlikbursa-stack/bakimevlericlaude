@extends('admin.layout')
@section('title', 'Teklif Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-6">Teklif Talepleri</h1>

<form method="GET" class="flex gap-3 mb-4">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','closed'=>'Kapandı'] as $k=>$v)
      <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
    @endforeach
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Ad Soyad</th><th class="p-3">Telefon</th><th class="p-3">Kurum</th><th class="p-3">Marka</th><th class="p-3">Durum</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($requests as $r)
        <tr>
          <td class="p-3">{{ $r->full_name }}</td>
          <td class="p-3">{{ $r->phone }}</td>
          <td class="p-3">{{ $r->facility?->name ?? '-' }}</td>
          <td class="p-3">{{ $brands[$r->brand]['name'] ?? $r->brand }}</td>
          <td class="p-3">
            <form method="POST" action="{{ route('admin.offer-requests.update', $r) }}">
              @csrf @method('PUT')
              <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
                @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','closed'=>'Kapandı'] as $k=>$v)
                  <option value="{{ $k }}" @selected($r->status===$k)>{{ $v }}</option>
                @endforeach
              </select>
            </form>
          </td>
          <td class="p-3 text-gray-400">{{ $r->created_at->format('d.m.Y H:i') }}</td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
