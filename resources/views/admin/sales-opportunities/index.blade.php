@extends('admin.layout')
@section('title', 'Satış Fırsatları')

@section('content')
<div class="flex items-center justify-between flex-wrap gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-bold">📞 Satış Fırsatları</h1>
    <p class="text-sm text-gray-500 mt-1">Üye/anlaşmalı olmayan ama gerçekten görüntülenmiş kurumlar, görüntülenmeye göre sıralı. Arama sırasında "siz bu ay X kez görüntülendiniz" ve "bölgenizde X kurum zaten üye" rakamlarını kullanın. Sayfa her açıldığında canlı veriyle yeniden hesaplanır.</p>
  </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3 mb-5 border border-gray-100">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Kurum adında ara..." class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[160px]">
  <select name="city" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="period" class="border rounded-lg px-3 py-2 text-sm">
    <option value="hafta" @selected($period==='hafta')>Bu hafta</option>
    <option value="ay" @selected($period==='ay')>Bu ay</option>
    <option value="ceyrek" @selected($period==='ceyrek')>Bu çeyrek (yeni başladı)</option>
    <option value="toplam" @selected($period==='toplam')>Toplam (ömür boyu)</option>
  </select>
  <button type="submit" class="border rounded-lg px-4 py-2 text-sm font-semibold bg-white">Filtrele</button>
  @if(request('city') || request('q'))
    <a href="{{ route('admin.sales-opportunities.index', ['period' => $period]) }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
  @endif
</form>

{{-- 11 Ekim 2026: kullanicinin bildirdigi gercek hata - admin/facilities
     sayfasinda daha once AYNI sebeple (bkz. _results.blade.php 1 Ekim 2026
     yorumu) <table>'dan kart duzenine gecilmisti, ama bu YENI sayfa yine
     genis bir <table> ile yapilmisti - ayni sorunu (satir sonundaki
     "Kurumu Aç" butonunu gormek icin yatay kaydirma gerekmesi) tekrar
     yaratti. AYNI kart/flex-wrap deseni burada da kullanilir. --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($facilities as $f)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900 break-words">{{ $f->name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ $f->category?->name }} · {{ $f->city?->name }}@if($f->district) / {{ $f->district }}@endif</div>
        </div>
        <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600 text-sm font-semibold whitespace-nowrap">Kurumu Aç</a>
      </div>
      <div class="flex items-center gap-2 flex-wrap text-xs">
        <span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-gray-100 text-gray-700">📞 {{ $f->phone ?: 'telefon yok' }}</span>
        <span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-blue-100 text-blue-700">👁️ {{ number_format($f->period_views, 0, ',', '.') }} görüntülenme ({{ ['hafta' => 'bu hafta', 'ay' => 'bu ay', 'ceyrek' => 'bu çeyrek', 'toplam' => 'toplam'][$period] }})</span>
        @if($f->rakip_uye_sayisi > 0)
          <span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-amber-100 text-amber-700">🤝 bölgede {{ $f->rakip_uye_sayisi }} kurum zaten üye</span>
        @endif
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $facilities->links() }}</div>
@endsection
