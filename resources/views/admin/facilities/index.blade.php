@extends('admin.layout')
@section('title', 'Kurumlar')

@section('content')
<div class="flex items-center justify-between mb-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">{{ request('status') === 'pre_registered' ? 'Sahiplenilmemiş Kurumlar' : (request('status') === 'broker_managed' ? 'Anlaşmalı Kurumlar' : (request('status') === 'claimed' ? 'Sahipli Kurumlar' : 'Kurumlar')) }}</h1>
    <span class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1"><span id="js-result-count">{{ number_format($facilities->total(), 0, ',', '.') }}</span> kurum bulundu</span>
  </div>
  <div class="flex items-center gap-3">
    <a href="{{ route('admin.facilities.subscriptions.index') }}" class="border-2 px-4 py-2 rounded-lg text-sm font-semibold" style="border-color:#c026d3;color:#a21caf;">★ Abone Kurumlar</a>
    <a href="{{ route('admin.facilities.create') }}" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">+ Yeni Kurum</a>
    @if(request('status'))
      <a href="{{ route('admin.facilities.index') }}" class="text-sm font-semibold text-gray-700 underline">Tüm kurumlara dön</a>
    @endif
  </div>
</div>

@if(! request('category') && count($categoryBreakdown) > 0)
  <p class="text-sm text-gray-500 mb-6">
    @foreach($categoryBreakdown as $name => $count)
      <span class="font-semibold text-gray-700">{{ $name }}</span>: {{ number_format($count, 0, ',', '.') }}@if(! $loop->last) &middot; @endif
    @endforeach
  </p>
@endif

<!-- 13 Agustos 2026: burada "claim_status" adinda gizli bir input DAHA vardi
     (asagidaki select ile ayni isimde) - tarayicida ayni isimli iki alan
     olmasi FormData/URL siralamasini belirsizlestiriyordu, kaldirildi;
     secili deger zaten asagidaki select uzerinde tutuluyor. -->
<form method="GET" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-admin-facility-results" data-count-target="js-result-count" class="js-location-filter mb-4 flex gap-2 flex-wrap">
  {{-- 14 Agustos 2026: kullanicinin bildirdigi hata - bu input'ta HEM
       oninput="this.form.submit()" (senkron, anlik tam sayfa yenileme) HEM
       de asagida include edilen location-filter-script.blade.php'nin AYNI
       input'a baglanan AJAX "instant filter" dinleyicisi AYNI ANDA
       calisiyordu. Inline reload her zaman once tetiklenip AJAX'i
       bastiriyordu - HER TUS VURUSUNDA sayfa yeniden yukleniyor, autofocus
       imleci basa donduruyordu ("yazilan kelimeler tersten yaziliyormus
       gibi" hissi tam olarak buradan geliyordu). Cozum: inline reload'i
       kaldirmak - AJAX mekanizmasi zaten public sitede sorunsuz calisiyor,
       burada da JS'siz reload gerekmiyor (Enter/"Ara" butonu hala calisir). --}}
  <input type="search" name="q" value="{{ request('q') }}" autofocus placeholder="Kurum adında ara..." class="border rounded-lg px-3 py-2 text-sm w-56">
  <button type="submit" class="border rounded-lg px-3 py-2 text-sm font-semibold bg-white">Ara</button>
  <select name="brand" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)
      <option value="{{ $slug }}" @selected(request('brand') === $slug)>{{ $b['name'] }}</option>
    @endforeach
  </select>
  <select name="city" class="js-city border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="district" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2 text-sm" @if(! request('city')) disabled @endif>
    <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
    @if(request('city'))
      @foreach(districts_for_city(optional($cities->firstWhere('slug', request('city')))->name ?? '') as $districtName)
        <option value="{{ $districtName }}" @selected(request('district') === $districtName)>{{ $districtName }}</option>
      @endforeach
    @endif
  </select>
  {{-- 11 Ekim 2026: kullanicinin talebi - il/ilce gibi mahalleye gore de
       filtreleme. Mahalle serbest metin oldugu icin (sabit bir liste degil,
       bkz. FacilityController::filteredQuery() ayni tarihli yorum) dropdown
       degil, diger metin aramasi gibi bir kutu. --}}
  <input type="text" name="mahalle" value="{{ request('mahalle') }}" placeholder="Mahalle ara..." class="border rounded-lg px-3 py-2 text-sm w-40">
  <select name="category" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Kategoriler</option>
    @foreach($categories as $category)
      <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
    @endforeach
  </select>
  {{-- 3 Eylul 2026: kullanicinin talebi - kamu/belediye/vakif kurumlari
       veritabanindan zaten ayiklandigi icin "Kuruluş Türleri" filtresi
       anlamsizlasmisti. Onun yerine ve eskiden ayri olan "Sahiplenme"
       filtresinin yerine, kullanicinin gercekten kullandigi 3 durumu
       (Ön Kayıt / Anlaşmalı / Sahipli) tek bir filtrede birlestiren
       "Kurum Durumu" filtresi kondu (bkz. FacilityController::filteredQuery()
       ayni tarihli yorum). --}}
  <select name="status" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Kurum Durumu: Tümü</option>
    <option value="pre_registered" @selected(request('status')==='pre_registered')>Sahiplenilmemiş</option>
    <option value="broker_managed" @selected(request('status')==='broker_managed')>Anlaşmalı</option>
    <option value="claimed" @selected(request('status')==='claimed')>Sahipli</option>
  </select>
  {{-- 1 Ekim 2026: kullanicinin talebi - "kamu/belediye kurumlari zaten
       veritabaninda yok" varsayimiyla 3 Eylul'de kaldirilan bu filtre,
       TOKI/BUSMEK/YUVAMIZ ice aktarimlariyla bu varsayimin yanlis oldugu
       kanitlanmasi uzerine geri eklendi. --}}
  <select name="ownership" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Kuruluş Türü: Tümü</option>
    @foreach($ownershipTypes as $key => $label)
      <option value="{{ $key }}" @selected(request('ownership')===$key)>{{ $label }}</option>
    @endforeach
    <option value="isaretsiz" @selected(request('ownership')==='isaretsiz')>İşaretlenmemiş</option>
  </select>
  {{-- 22 Eylul 2026: kullanicinin talebi - konumvericekici'nin Google'da
       net eslesme bulamadigi icin BILEREK atladigi kurumlari admin elle
       tamamlayabilsin diye. --}}
  {{-- 22 Eylul 2026 (2): "onchange=this.form.submit()" kaldirildi - form
       zaten "data-instant-filter" AJAX mekanizmasini kullaniyor
       (location-filter-script.blade.php, TUM select'lere 'change'
       dinleyicisi ekliyor), ikisi birlikte cift-gonderime yol aciyordu. --}}
  {{-- 11 Ekim 2026: kullanicinin talebi - ayri konum/telefon/gorsel
       kutulari yerine TEK, sade bir "Eksiklik" dropdown'u. Gorsel icin
       demo/ klasorundeki placeholder'i olan kurum da "gorseli yok" sayilir
       (bkz. FacilityController::filteredQuery() ayni tarihli yorum). --}}
  <select name="eksik" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Eksiklik: Yok</option>
    <option value="konum" @selected(request('eksik')==='konum')>📍 Konumu Eksik</option>
    <option value="telefon" @selected(request('eksik')==='telefon')>📞 Telefonu Eksik</option>
    <option value="gorsel" @selected(request('eksik')==='gorsel')>🖼️ Görseli Eksik</option>
    <option value="mahalle" @selected(request('eksik')==='mahalle')>🏘️ Mahallesi Eksik</option>
  </select>
  {{-- 30 Eylul 2026: kullanicinin talebi - "hangi kurum ne kadar GERCEKTEN
       goruntulenme almis" sorusuna cevap. Bu filtre/siralama otomatik
       eklenen (boosted_views_count) DEGIL, sadece gercek (lifetime)
       views_count uzerinden calisir (bkz. FacilityController::filteredQuery()/
       index() ayni tarihli yorum). Sayi girisi oldugu icin (select degil)
       diger metin aramasi gibi "Ara" butonuyla/Enter ile calisir. --}}
  <input type="number" name="views_min" value="{{ request('views_min') }}" min="0" placeholder="Min. gerçek görüntülenme" class="border rounded-lg px-3 py-2 text-sm w-40">
  <select name="sort" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Sırala: Son Eklenen</option>
    <option value="views_desc" @selected(request('sort')==='views_desc')>👁️ Görüntülenme (çoktan aza)</option>
    <option value="views_asc" @selected(request('sort')==='views_asc')>👁️ Görüntülenme (azdan çoğa)</option>
  </select>
  @if(request('city') || request('district') || request('mahalle') || request('category') || request('brand') || request('status') || request('q') || request('eksik') || request('views_min') || request('sort') || request('ownership'))
    <a href="{{ route('admin.facilities.index', array_filter(['status' => request('status')])) }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreleri temizle</a>
  @endif
</form>

<div id="js-admin-facility-results">
  @include('admin.facilities._results', ['facilities' => $facilities, 'ownershipTypes' => $ownershipTypes])
</div>

{{-- 15 Agustos 2026: kullanicinin bildirdigi hata - bu include daha once
     js-admin-facility-results div'inden ONCE geliyordu. Script senkron
     calistigi icin document.getElementById('js-admin-facility-results')
     o an DOM'da HENUZ olusmamis oluyordu, resultsEl null donuyor, runFilter()
     "if (!resultsEl) return" ile SESSIZCE hicbir sey yapmadan cikiyordu -
     arama kutusu ve tum secim filtreleri tikliyor/yaziliyor ama hicbir
     sonuc guncellenmiyordu (public sitedeki ayni partial dogru sirada,
     bu yuzden orada sorun yoktu). Include artik sonuc div'inden SONRA. --}}
@include('themes._shared.partials.location-filter-script')

<script>
  // 1 Ekim 2026: kullanicinin talebi - Revize/Panelde Gor/Sil/Abone gibi
  // formlar sayfayi yeniden yukluyor (POST->redirect), tarayici varsayilan
  // olarak sayfa basina atiyor. Veri Denetimi sayfasinda uygulanan ayni
  // cozum (bkz. admin/data-quality/index.blade.php ayni tarihli yorum)
  // burada da: gonderim oncesi kayma konumu kaydedilip yeni sayfada geri
  // yukleniyor.
  document.addEventListener('submit', function (e) {
    if (e.target.tagName === 'FORM') {
      sessionStorage.setItem('kurumlarScrollY', String(window.scrollY));
    }
  });
  // 1 Ekim 2026 (3): kullanicinin bildirdigi gercek hata - "bazi silmelerde
  // hala basa atiyor". Kok neden: sayfanin son kaydini silince
  // RedirectsOutOfRangePagination devreye girip AYRI bir yonlendirme
  // yapiyor (bkz. Admin/Concerns/RedirectsOutOfRangePagination.php), bu da
  // tek 'load' olayinin bazen cok erken/tutarsiz ateslenmesine yol aciyordu.
  // Tek seferlik denemek yerine, kaydirmayi ilk 1.5 saniye boyunca birkac
  // kez tekrar "sabitleyerek" - gec yuklenen gorsel/icerik sayfa
  // uzunlugunu degistirse bile - hedef konumda kalmasi garanti ediliyor.
  (function () {
    var y = sessionStorage.getItem('kurumlarScrollY');
    if (y === null) return;
    var target = parseInt(y, 10);
    var denemeler = [0, 50, 150, 300, 600, 1000, 1500];
    denemeler.forEach(function (gecikme) {
      setTimeout(function () { window.scrollTo(0, target); }, gecikme);
    });
    setTimeout(function () { sessionStorage.removeItem('kurumlarScrollY'); }, 1600);
  })();
</script>
@endsection

