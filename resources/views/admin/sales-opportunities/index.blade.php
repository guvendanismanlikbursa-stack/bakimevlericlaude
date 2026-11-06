@extends('admin.layout')
@section('title', 'Satış Fırsatları')

@section('content')
<div class="flex items-center justify-between flex-wrap gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-bold">📞 Satış Fırsatları</h1>
    <p class="text-sm text-gray-500 mt-1">Üye olmayan kurumlar, son 30 günün <b>gerçek ilgisine</b> göre sıralı: önce aile talepleri ve Ara / WhatsApp / Yol tarifi tıklamaları, sonra ziyaretçi sayısı. Arama sırasında bu rakamları kullanın; "💬 Teklif + rakamlar" kuruma rakamlı mesajla kendi teklif linkini WhatsApp'tan gönderir.</p>
    <p class="text-xs text-gray-400 mt-1">"Doğrulanmış ziyaretçi" sayımı 6 Kasım 2026'da başladı (yaklaşık 2 hafta sonra anlamlı olur); o zamana kadar "ziyaretçi" (sunucu ölçümü) kullanılır. Tıklama ölçümü de 6 Kasım'da başladı.</p>
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
  <select name="kategori" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Kategoriler</option>
    @foreach($kategoriler as $k)
      <option value="{{ $k->slug }}" @selected(request('kategori') === $k->slug)>{{ $k->name }}</option>
    @endforeach
  </select>
  <select name="sirala" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Sıralama: gerçek ilgi (30 gün)</option>
    <option value="goruntulenme" @selected(request('sirala') === 'goruntulenme')>Sıralama: eski görüntülenme sayacı</option>
  </select>
  <select name="period" class="border rounded-lg px-3 py-2 text-sm">
    <option value="hafta" @selected($period==='hafta')>Bu hafta</option>
    <option value="ay" @selected($period==='ay')>Bu ay</option>
    <option value="ceyrek" @selected($period==='ceyrek')>Bu çeyrek (yeni başladı)</option>
    <option value="toplam" @selected($period==='toplam')>Toplam (ömür boyu)</option>
  </select>
  <button type="submit" class="border rounded-lg px-4 py-2 text-sm font-semibold bg-white">Filtrele</button>
  @if(request('city') || request('q') || request('kategori') || request('sirala'))
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
        <div class="flex items-center gap-3 flex-wrap">
          <a href="{{ route('admin.invitations.offer-link', ['facility' => $f, 'via' => 'whatsapp']) }}" target="_blank" rel="noopener" class="text-green-700 text-sm font-semibold whitespace-nowrap" title="Kuruma son 30 günün rakamlarıyla kendi teklif linkini WhatsApp'tan gönderir">💬 Teklif + rakamlar</a>
          <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600 text-sm font-semibold whitespace-nowrap">Kurumu Aç</a>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap text-xs">
        <span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-gray-100 text-gray-700">☎️ {{ $f->phone ?: 'telefon yok' }}</span>@if($f->offer_link_opened_at)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold" style="background:#f3e8ff;color:#7e22ce;">👀 teklifi açtı ({{ $f->offer_link_opened_at->format('d.m') }})</span>@elseif($f->offer_token)<span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-gray-50 text-gray-500">teklif linki var, açılmadı</span>@endif
        @if((int) $f->ilgi_talep > 0)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold" style="background:#ffe4e6;color:#be123c;">📝 {{ (int) $f->ilgi_talep }} aile talebi</span>@endif
        @if((int) $f->ilgi_istek > 0)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold" style="background:#ffedd5;color:#c2410c;" title="Numarası kapalı olduğu için form penceresi açıldı">🔒 {{ (int) $f->ilgi_istek }} kez aramak/yazmak istedi</span>@endif
        @if((int) $f->ilgi_ara > 0)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-green-100 text-green-700">📞 {{ (int) $f->ilgi_ara }} arama tıklaması</span>@endif
        @if((int) $f->ilgi_wa > 0)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-green-100 text-green-700">💬 {{ (int) $f->ilgi_wa }} WhatsApp tıklaması</span>@endif
        @if((int) $f->ilgi_yol > 0)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-green-100 text-green-700">📍 {{ (int) $f->ilgi_yol }} yol tarifi</span>@endif
        @if((int) $f->ilgi_js > 0)<span class="inline-block rounded-full px-2.5 py-0.5 font-bold" style="background:#e0e7ff;color:#4338ca;">✅ {{ number_format((int) $f->ilgi_js, 0, ',', '.') }} doğrulanmış ziyaretçi</span>@endif
        <span class="inline-block rounded-full px-2.5 py-0.5 font-bold bg-blue-100 text-blue-700">👁️ {{ number_format((int) $f->ilgi_rv, 0, ',', '.') }} ziyaretçi (30 gün)</span>
        <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-gray-50 text-gray-400" title="Eski sayaç: botlar dahil her istek">{{ number_format($f->period_views, 0, ',', '.') }} görüntülenme, bot dahil ({{ ['hafta' => 'bu hafta', 'ay' => 'bu ay', 'ceyrek' => 'bu çeyrek', 'toplam' => 'toplam'][$period] }})</span>
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
