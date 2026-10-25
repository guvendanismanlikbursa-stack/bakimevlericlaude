@extends('admin.layout')
@section('title', 'Doktorlar')

@section('content')
<div class="flex items-center justify-between flex-wrap gap-3 mb-6">
  <div>
    <div class="flex items-center gap-3">
      <h1 class="text-2xl font-bold">Doktorlar</h1>
      {{-- 11 Ekim 2026: kullanicinin bildirdigi gercek hata - Kurumlar
           sayfasinda filtre uygulaninca kac sonuc bulundugu bir rozette
           gorunuyor (bkz. admin/facilities/index.blade.php), Doktorlar'da
           bu YOKTU - filtre (ornegin "Telefonu Eksik") uygulandiginda
           kac kayit oldugu hic anlasilmiyordu, sadece statik toplam/yayinda
           sayisi goruniyordu (filtreden bagimsiz, hep aynı). --}}
      <span data-live-count class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1">{{ number_format($experts->total(), 0, ',', '.') }} doktor bulundu</span>
    </div>
    <p class="text-sm text-gray-500 mt-1">"Özel Doktorlar" bölümünde listelenen doktor kayıtları. Toplam: {{ $totalCount }} · Yayında: {{ $publishedCount }}</p>
  </div>
  <a href="{{ route('admin.experts.create') }}" class="bg-blue-600 text-white rounded-lg px-5 py-2.5 text-sm font-bold">+ Doktor Ekle</a>
</div>
{{-- 25 Ekim 2026: Doktorlar bölümünü yayına al / gizle - tek tıkla geri alınabilir. --}}
@php $doktorYayinda = doctors_live(); @endphp
<div style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;justify-content:space-between;border-radius:14px;padding:12px 16px;margin-bottom:16px;{{ $doktorYayinda ? 'background:#ecfdf5;border:1.5px solid #86efac;' : 'background:#fffbeb;border:1.5px solid #fcd34d;' }}">
  <div style="font-size:14px;color:#1f2937;min-width:0;flex:1 1 260px;">
    <b>Doktorlar bölümü: {{ $doktorYayinda ? '🟢 Yayında' : '🔒 Gizli (test)' }}</b><br>
    <span style="color:#4b5563;">{{ $doktorYayinda ? 'Anasayfada "Özel Doktorlar" kartı görünüyor, /uzmanlar herkese ve Google\'a açık.' : 'Anasayfada kart yok, /uzmanlar yalnızca bağlantıyı bilenlere açık ve Google\'a kapalı.' }}</span>
  </div>
  <form method="POST" action="{{ route('admin.experts.section-toggle') }}" onsubmit="return confirm('{{ $doktorYayinda ? 'Doktorlar bölümü gizlensin mi? Anasayfa kartları eski haline döner.' : 'Doktorlar bölümü yayına alınsın mı? Anasayfada 4. kart çıkar, sayfa Google\'a açılır.' }}')" style="margin:0;">
    @csrf
    <input type="hidden" name="live" value="{{ $doktorYayinda ? '0' : '1' }}">
    <button type="submit" style="border:0;border-radius:10px;padding:9px 16px;font-weight:800;font-size:14px;cursor:pointer;color:#fff;background:{{ $doktorYayinda ? '#b91c1c' : '#0d6e6a' }};">{{ $doktorYayinda ? 'Bölümü gizle (geri al)' : 'Bölümü yayına al' }}</button>
  </form>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3 mb-5 border border-gray-100">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="İsimle ara..." class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[160px]" data-live-search autocomplete="off">
  <select name="city" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->id }}" @selected((string) request('city') === (string) $city->id)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="branch" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Branşlar</option>
    @foreach($branches as $title => $label)
      <option value="{{ $title }}" @selected(request('branch') === $title)>{{ $title }}</option>
    @endforeach
  </select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    <option value="published" @selected(request('status') === 'published')>Yayında</option>
    <option value="draft" @selected(request('status') === 'draft')>Taslak</option>
    <option value="deleted" @selected(request('status') === 'deleted')>Silinenler (çöp kutusu)</option>
  </select>
  {{-- 11 Ekim 2026: kullanicinin talebi - eksik telefonlu doktorlari bulup manuel arama yapabilmek icin. --}}
  <select name="phone_status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Telefon: Tümü</option>
    <option value="eksik" @selected(request('phone_status')==='eksik')>📞 Telefonu Eksik</option>
  </select>
  {{-- 22 Ekim 2026: internet sitesi durumu filtresi (kurumlardakiyle aynı). --}}
  <select name="site" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Site: Tümü</option>
    <option value="eksik" @selected(request('site')==='eksik')>🌐 Kendi sitesi yok (hepsi)</option>
    @foreach(\App\Models\Facility::WEBSITE_STATUSES as $key => $label)
      <option value="{{ $key }}" @selected(request('site')===$key)>{{ $label }}</option>
    @endforeach
    <option value="bilinmiyor" @selected(request('site')==='bilinmiyor')>Bilinmiyor</option>
  </select>
  @if(request('q') || request('city') || request('branch') || request('status') || request('phone_status') || request('site'))
    <a href="{{ route('admin.experts.index') }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
  @endif
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - "hangi olcude olursa olsun butun
     islemleri gorebilmeliyim, kaydirmak istemiyorum" (bkz. admin/facilities/
     _results.blade.php 1 Ekim 2026 yorumuyla AYNI sebep/desen) - genis
     <table> yerine flex-wrap kart. --}}
<div data-live-results>
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($experts as $expert)
    <div class="p-4 flex flex-col gap-2 {{ $expert->trashed() ? 'opacity-50' : '' }}">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900 break-words">{{ $expert->title }} {{ $expert->name }}@if($expert->isSupporter()) <span class="text-xs font-bold" style="color:#be185d;">💗 Destekçi · bitiş {{ $expert->supporter_until->format('d.m.Y') }}</span>@endif</div>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ $expert->branch_label ?: $expert->branch }} · {{ $expert->city?->name }}@if($expert->district) / {{ $expert->district }}@endif
            @if($expert->clinic_name) · {{ $expert->clinic_name }}@endif
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          @if($expert->trashed())
            <form method="POST" action="{{ route('admin.experts.restore', $expert->id) }}" class="inline">
              @csrf
              <button class="text-green-700 text-sm font-semibold whitespace-nowrap">Geri Yükle</button>
            </form>
          @else
            <a href="{{ route('admin.experts.edit', $expert) }}" class="text-blue-700 text-sm font-semibold whitespace-nowrap">Düzenle</a>
            <form method="POST" action="{{ route('admin.experts.toggle-publish', $expert) }}" class="inline">
              @csrf
              <button class="text-gray-700 text-sm font-semibold whitespace-nowrap">{{ $expert->is_published ? 'Yayından Kaldır' : 'Yayınla' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.experts.destroy', $expert) }}" class="inline" onsubmit="return confirm('Bu doktor silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button>
            </form>
          @endif
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap text-xs">
        <span class="text-gray-600">📞 {{ $expert->phone ?: '—' }}</span>
        {{-- 25 Ekim 2026: Doktorlar sayfası kart sayacı, son 30 gün (yalnızca admin). --}}
        @php $ks = $kartStat[$expert->id] ?? null; @endphp
        <span style="display:inline-block;background:#f0fdfa;color:#115e59;border-radius:9999px;padding:2px 10px;font-weight:600;" title="Son 30 gün: kartın listelendiği arama sayısı · detayının açılma sayısı · telefon tıklaması">30 gün: 📋 {{ (int) ($ks->listelenme ?? 0) }} listelenme · 👁 {{ (int) ($ks->acilma ?? 0) }} açılma · 📞 {{ (int) ($ks->arama ?? 0) }} arama</span>
        @if($expert->website_status)@include('partials.website-status-badge', ['facility' => $expert])@endif
        @if($expert->trashed())
          <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-red-100 text-red-700">Silinmiş</span>
        @elseif($expert->is_published)
          <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-green-100 text-green-700">Yayında</span>
        @else
          <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-gray-100 text-gray-600">Taslak</span>
        @endif
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $experts->links() }}</div>
</div>
@include('admin.partials.live-search-script')
@endsection
