@extends('admin.layout')
@section('title', 'Kurumlar')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">{{ request('claim_status') === 'unclaimed' ? 'Ön Kayıtlı Kurumlar' : 'Kurumlar' }}</h1>
  <div class="flex items-center gap-3">
    <a href="{{ route('admin.facilities.create') }}" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">+ Yeni Kurum</a>
    @if(request('claim_status') === 'unclaimed')
      <a href="{{ route('admin.facilities.index') }}" class="text-sm font-semibold text-gray-700 underline">Tüm kurumlara dön</a>
    @endif
  </div>
</div>

<form method="GET" class="mb-4 flex gap-2 flex-wrap">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)
      <option value="{{ $slug }}" @selected(request('brand') === $slug)>{{ $b['name'] }}</option>
    @endforeach
  </select>
  <select name="claim_status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Sahiplenme: Tümü</option>
    <option value="claimed" @selected(request('claim_status')==='claimed')>Onaylı Kurumlar</option>
    <option value="unclaimed" @selected(request('claim_status')==='unclaimed')>Ön Kayıtlı Kurumlar</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Ad</th><th class="p-3">Şehir</th><th class="p-3">Kategori</th><th class="p-3">Profil Kalitesi</th><th class="p-3">Sahiplenme</th><th class="p-3">Durum</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($facilities as $f)
        <tr>
          <td class="p-3 font-medium">{{ $f->name }}</td>
          <td class="p-3">{{ $f->city->name }}</td>
          <td class="p-3">{{ $f->category->name }}</td>
          <td class="p-3">
            @php($quality = $f->profileQuality())
            <div class="flex items-center gap-2">
              <div class="h-2 w-20 rounded-full bg-gray-100 overflow-hidden"><div class="h-full {{ $quality['score'] >= 80 ? 'bg-green-500' : ($quality['score'] >= 55 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $quality['score'] }}%"></div></div>
              <span class="text-xs font-semibold text-gray-700">{{ $quality['score'] }}/100</span>
            </div>
            @if($quality['missing'])<div class="text-[11px] text-gray-400 mt-1">Eksik: {{ implode(', ', array_slice($quality['missing'], 0, 2)) }}</div>@endif
          </td>
          <td class="p-3">
            @if($f->is_claimed)
              <span class="text-green-700 text-xs font-semibold">✓ Sahiplenilmiş</span>
            @else
              <span class="text-gray-400 text-xs">Ön Kayıtlı</span>
            @endif
          </td>
          <td class="p-3">{{ $f->is_published ? 'Yayında' : 'Taslak' }}</td>
          <td class="p-3 text-right space-x-2">
            <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600">Revize</a>
            @if($f->is_claimed)
              <form method="POST" action="{{ route('admin.facilities.revert', $f) }}" class="inline" onsubmit="return confirm('Bu kurumu ön kayıtlı hale getirmek istediğinize emin misiniz?');">
                @csrf
                <button class="text-orange-600">Ön Kayıt</button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.facilities.destroy', $f) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600">Sil</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $facilities->links() }}</div>
@endsection

