@extends('admin.layout')
@section('title', 'Veri Denetimi')
@section('content')

<div class="mb-6">
  <h1 class="text-2xl font-bold">Veri Denetimi</h1>
  <p class="text-sm text-gray-500 mt-1">Otomatik veri toplamadan kaynaklanan hataları (yanlış kategori, yanlış telefon türü, yanlış ilçe, yanlış kuruluş türü, isim biçimi) burada görüp düzeltebilirsiniz. Bir kurum sahibi kendi bilgisini yanlış görürse platforma güvenmez — bu sayfa o hatalar kurum sahibinin gözüne çarpmadan önce temizlenmesi içindir.</p>
</div>

@if(session('success'))<div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-6">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-6">{{ session('error') }}</div>@endif

<div class="space-y-6">

  {{-- 1. Kategori yanlislklari --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Yanlış Kategoriye Düşmüş Kurumlar</h2>
        <p class="text-sm text-gray-500 mt-1">"Huzurevi" / "Yaşlı Bakım Evi" kategorisinde ama ismine bakılırsa gerçekte kreş, çocuk bakım, özel eğitim veya rehabilitasyon merkezi olan kurumlar. Toplu içe aktarmada kategori yanlış eşleşmiş olabilir.</p>
      </div>
      <span class="text-2xl font-black {{ count($miscategory['reassignable']) > 0 ? 'text-red-600' : 'text-green-600' }}">{{ count($miscategory['reassignable']) }}</span>
    </div>
    <p class="text-xs text-gray-400 mb-3">Taranan: {{ $miscategory['scanned'] }} kurum · Otomatik taşınabilir: {{ count($miscategory['reassignable']) }} · Elle karar verilmeli (bakım kurumu olmayabilir): {{ count($miscategory['nonCare']) }}</p>

    @if(count($miscategory['reassignable']) > 0)
      {{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap kart. --}}
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100 mb-3">
        @foreach(array_slice($miscategory['reassignable'], 0, 25) as $row)
          <div class="p-2 flex items-center justify-between gap-3 flex-wrap text-sm">
            <span class="font-medium">{{ $row['facility']->name }}</span>
            <span class="text-gray-600">{{ $row['facility']->sehir }} → {{ $row['target'] }}</span>
          </div>
        @endforeach
        @if(count($miscategory['reassignable']) > 25)<p class="text-xs text-gray-400 p-2">... ve {{ count($miscategory['reassignable']) - 25 }} tane daha</p>@endif
      </div>
      <form method="POST" action="{{ route('admin.data-quality.fix-miscategory') }}" onsubmit="return confirm('{{ count($miscategory['reassignable']) }} kurumun kategorisi otomatik değiştirilecek. Emin misiniz?');">
        @csrf
        <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Düzelt ({{ count($miscategory['reassignable']) }})</button>
      </form>
    @else
      <p class="text-sm text-green-700">Otomatik taşınabilecek bir sorun bulunamadı.</p>
    @endif

    @if(count($miscategory['nonCare']) > 0)
      <details class="mt-3">
        <summary class="text-sm font-semibold text-gray-600 cursor-pointer">Bakım kurumu olmayabilir, elle incelenmeli ({{ count($miscategory['nonCare']) }})</summary>
        <ul class="text-sm text-gray-500 mt-2 space-y-1">
          @foreach(array_slice($miscategory['nonCare'], 0, 20) as $f)
            <li>#{{ $f->id }} {{ $f->name }} ({{ $f->sehir }})</li>
          @endforeach
        </ul>
      </details>
    @endif
  </div>

  {{-- 2. Telefon turu --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Yanlış Sınıflandırılmış Telefon Türü</h2>
        <p class="text-sm text-gray-500 mt-1">Kayıtlı telefon türü (cep/sabit hat) ile numaranın gerçek türü uyuşmuyor. Cep telefonu sabit hat sanılan kurumlara WhatsApp daveti hiç gitmiyor.</p>
      </div>
      <span class="text-2xl font-black {{ count($phoneType['mismatches']) > 0 ? 'text-red-600' : 'text-green-600' }}">{{ count($phoneType['mismatches']) }}</span>
    </div>
    <p class="text-xs text-gray-400 mb-3">Kontrol edilen: {{ $phoneType['checked'] }} kurum</p>

    @if(count($phoneType['mismatches']) > 0)
      {{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap kart. --}}
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100 mb-3">
        @foreach(array_slice($phoneType['mismatches'], 0, 25) as $row)
          <div class="p-2 flex items-center justify-between gap-3 flex-wrap text-sm">
            <span class="font-medium">{{ $row['facility']->name }} <span class="font-normal text-gray-500">({{ $row['facility']->phone }})</span></span>
            <span class="text-gray-600">{{ $row['stored'] }} → {{ $row['computed'] }}</span>
          </div>
        @endforeach
        @if(count($phoneType['mismatches']) > 25)<p class="text-xs text-gray-400 p-2">... ve {{ count($phoneType['mismatches']) - 25 }} tane daha</p>@endif
      </div>
      <form method="POST" action="{{ route('admin.data-quality.fix-phone-type') }}" onsubmit="return confirm('{{ count($phoneType['mismatches']) }} kurumun telefon türü düzeltilecek. Emin misiniz?');">
        @csrf
        <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Düzelt ({{ count($phoneType['mismatches']) }})</button>
      </form>
    @else
      <p class="text-sm text-green-700">Sorun bulunamadı.</p>
    @endif
  </div>

  {{-- 3. Sahiplik turu --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Şüpheli Kuruluş Türü</h2>
        <p class="text-sm text-gray-500 mt-1">"Kamu" olarak kayıtlı ama isminde "özel" geçen kurumlar — muhtemelen elle veya içe aktarmada yanlış işaretlenmiş. Otomatik düzeltilmez, her satır için doğru türü siz seçmelisiniz.</p>
      </div>
      <span class="text-2xl font-black {{ count($ownership) > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ count($ownership) }}</span>
    </div>

    @if(count($ownership) > 0)
      {{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap kart. --}}
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100">
        @foreach($ownership as $f)
          <div class="p-2 flex items-center justify-between gap-3 flex-wrap">
            <div class="text-sm">
              <span class="font-medium">{{ $f->name }}</span>
              <span class="text-gray-600"> · {{ $f->sehir }}</span>
            </div>
            <form method="POST" action="{{ route('admin.data-quality.fix-ownership') }}" class="inline-flex items-center gap-1">
              @csrf
              <input type="hidden" name="id" value="{{ $f->id }}">
              <select name="type" class="border rounded-lg px-2 py-1 text-xs">
                <option value="ozel">Özel</option>
                <option value="vakif">Vakıf</option>
                <option value="belediye">Belediye</option>
                <option value="kamu" selected>Kamu (değiştirme)</option>
              </select>
              <button class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50 whitespace-nowrap">Uygula</button>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-green-700">Şüpheli kayıt bulunamadı.</p>
    @endif
  </div>

  {{-- 4. Ilce tutarsizligi --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">İlçe Bilgisi Tutarsızlığı</h2>
        <p class="text-sm text-gray-500 mt-1">Kurumun ilçe metni ile sistemdeki gerçek ilçe kaydı uyuşmuyor veya biri boş. Bu durumda ilçe filtresi o kurumu atlayabilir.</p>
      </div>
      <span class="text-2xl font-black {{ ($district['onlyFkFilled'] + $district['mismatch']) > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $district['onlyFkFilled'] + $district['mismatch'] }}</span>
    </div>
    <p class="text-xs text-gray-400 mb-3">Kontrol edilen: {{ $district['checked'] }} · Tutarlı: {{ $district['consistent'] }} · İlçe hiç girilmemiş: {{ $district['bothEmpty'] }} · Sadece sistemde kayıtlı (filtre kaçırır): {{ $district['onlyFkFilled'] }} · Birbirinden farklı: {{ $district['mismatch'] }}</p>

    @if(count($district['examples']) > 0)
      {{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap kart. --}}
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100 mb-3">
        @foreach($district['examples'] as $row)
          <div class="p-2 flex items-center justify-between gap-3 flex-wrap text-sm">
            <span class="font-medium">{{ $row['facility']->name }} <span class="font-normal text-gray-500">({{ $row['facility']->sehir }})</span></span>
            <span class="text-gray-600">Metin: {{ $row['facility']->district_text ?: '—' }} · Sistemdeki: {{ $row['facility']->district_fk ?: '—' }} · {{ $row['reason'] === 'sadece_fk' ? 'Metin boş' : 'Uyumsuz' }}</span>
          </div>
        @endforeach
      </div>
    @endif

    @if($district['onlyFkFilled'] > 0)
      <form method="POST" action="{{ route('admin.data-quality.fix-district') }}" onsubmit="return confirm('{{ $district['onlyFkFilled'] }} kurumun ilçe metni otomatik doldurulacak. Emin misiniz?');">
        @csrf
        <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Boş Olanları Doldur ({{ $district['onlyFkFilled'] }})</button>
      </form>
      <p class="text-xs text-gray-400 mt-2">Not: sadece metin alanı boş olanlar otomatik doldurulur. Birbirinden farklı ({{ $district['mismatch'] }} kurum) elle kontrol edilmeli, üzerine yazılmaz.</p>
    @endif
  </div>

  {{-- 5. Isim temizligi --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Kurum İsmi Biçim Hataları</h2>
        <p class="text-sm text-gray-500 mt-1">İsim başında/sonunda gereksiz boşluk veya virgül, ya da kelimeler arasında çift boşluk. Sadece biçim düzeltir, ismin gerçek metnini değiştirmez.</p>
      </div>
      <span class="text-2xl font-black {{ $nameCleanup['count'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $nameCleanup['count'] }}</span>
    </div>

    @if($nameCleanup['count'] > 0)
      {{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap kart. --}}
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100 mb-3">
        @foreach(array_slice($nameCleanup['examples'], 0, 25) as $row)
          <div class="p-2 text-sm">
            <span class="font-medium">"{{ $row['old'] }}"</span>
            <span class="text-gray-600"> → "{{ $row['new'] }}"</span>
          </div>
        @endforeach
        @if(count($nameCleanup['examples']) > 25)<p class="text-xs text-gray-400 p-2">... ve {{ count($nameCleanup['examples']) - 25 }} tane daha</p>@endif
      </div>
      <form method="POST" action="{{ route('admin.data-quality.fix-name-cleanup') }}" onsubmit="return confirm('{{ $nameCleanup['count'] }} kurumun ismi düzeltilecek. Emin misiniz?');">
        @csrf
        <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Düzelt ({{ $nameCleanup['count'] }})</button>
      </form>
    @else
      <p class="text-sm text-green-700">Sorun bulunamadı.</p>
    @endif
  </div>

  {{-- 6. Izmir 1 Ekim mukerrer kurumlar --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Mükerrer Kurumlar (İzmir importu, 1 Ekim 2026)</h2>
        <p class="text-sm text-gray-500 mt-1">İzmir anaokulu/kreş toplu içe aktarımı sırasında sunucu bağlantı zaman aşımları nedeniyle aynı dosyanın tekrar çalıştırılması bazı kurumların iki kez oluşmasına yol açtı. Adres+telefon+oluşturma zamanı karşılaştırılarak doğrulanmış, kesin mükerrer kayıtlar. Her satırı tek tek "Sil" ile kaldırabilirsiniz.</p>
      </div>
      <span class="text-2xl font-black {{ $izmirOct1Duplicates['count'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $izmirOct1Duplicates['count'] }}</span>
    </div>

    @if($izmirOct1Duplicates['count'] > 0)
      <form method="POST" action="{{ route('admin.data-quality.fix-izmir-oct1-duplicates') }}" class="mb-3" onsubmit="return confirm('{{ $izmirOct1Duplicates['count'] }} mükerrer kurumun TÜMÜ silinecek. Emin misiniz?');">
        @csrf
        <button class="bg-red-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Sil ({{ $izmirOct1Duplicates['count'] }})</button>
      </form>
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100">
        @foreach($izmirOct1Duplicates['rows'] as $f)
          <div class="flex items-center gap-3 p-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium truncate" title="{{ $f->name }}">#{{ $f->id }} {{ $f->name }}</div>
              <div class="text-xs text-gray-500 truncate" title="{{ $f->address }}">{{ $f->address }} @if($f->phone) · {{ $f->phone }} @endif</div>
            </div>
            <form method="POST" action="{{ route('admin.facilities.destroy', $f->id) }}" class="shrink-0" onsubmit="return confirm('Bu mükerrer kurum silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 font-bold text-xs whitespace-nowrap px-2 py-1 rounded hover:bg-red-50">Sil</button>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-green-700">Mükerrer kayıt bulunamadı (hepsi temizlenmiş).</p>
    @endif
  </div>

  {{-- Antalya 6 Ekim mukerrer kurumlar --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Mükerrer Kurumlar (Antalya importu, 6 Ekim 2026)</h2>
        <p class="text-sm text-gray-500 mt-1">İlk içe aktarma isteği sunucuda "zaman aşımı" döndürdü ama arka planda çalışmaya devam etti; "başarısız oldu" sanılıp aynı liste ikinci kez çalıştırılınca bazı kurumlar iki kez oluştu. Her çiftte yorumları/eşleşmesi olan (önce oluşan, düşük ID'li) kayıt tutuldu, bu listedeki (sonradan oluşan, hep yorumsuz) kopyalar silinmeye hazır.</p>
      </div>
      <span class="text-2xl font-black {{ $antalyaOct6Duplicates['count'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $antalyaOct6Duplicates['count'] }}</span>
    </div>

    @if($antalyaOct6Duplicates['count'] > 0)
      <form method="POST" action="{{ route('admin.data-quality.fix-antalya-oct6-duplicates') }}" class="mb-3" onsubmit="return confirm('{{ $antalyaOct6Duplicates['count'] }} mükerrer kurumun TÜMÜ silinecek. Emin misiniz?');">
        @csrf
        <button class="bg-red-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Sil ({{ $antalyaOct6Duplicates['count'] }})</button>
      </form>
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100">
        @foreach($antalyaOct6Duplicates['rows'] as $f)
          <div class="flex items-center gap-3 p-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium truncate" title="{{ $f->name }}">#{{ $f->id }} {{ $f->name }}</div>
              <div class="text-xs text-gray-500 truncate" title="{{ $f->address }}">{{ $f->address }} @if($f->phone) · {{ $f->phone }} @endif</div>
            </div>
            <form method="POST" action="{{ route('admin.facilities.destroy', $f->id) }}" class="shrink-0" onsubmit="return confirm('Bu mükerrer kurum silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 font-bold text-xs whitespace-nowrap px-2 py-1 rounded hover:bg-red-50">Sil</button>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-green-700">Mükerrer kayıt bulunamadı (hepsi temizlenmiş).</p>
    @endif
  </div>

  {{-- 7. Bursa/Istanbul 1 Ekim 2. tur mukerrer kurumlar --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Mükerrer Kurumlar (Bursa/İstanbul eksik-tamamlama, 1 Ekim 2026)</h2>
        <p class="text-sm text-gray-500 mt-1">Kaynak Excel dosyalarında aynı kurumun hafif farklı mahalle/sokak yazımıyla iki kez geçmesinden kaynaklanan, adres+oluşturma zamanı karşılaştırılarak doğrulanmış kesin mükerrer kayıtlar.</p>
      </div>
      <span class="text-2xl font-black {{ $bursaIstanbulOct1Duplicates['count'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $bursaIstanbulOct1Duplicates['count'] }}</span>
    </div>

    @if($bursaIstanbulOct1Duplicates['count'] > 0)
      <form method="POST" action="{{ route('admin.data-quality.fix-bursa-istanbul-oct1-duplicates') }}" class="mb-3" onsubmit="return confirm('{{ $bursaIstanbulOct1Duplicates['count'] }} mükerrer kurumun TÜMÜ silinecek. Emin misiniz?');">
        @csrf
        <button class="bg-red-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Sil ({{ $bursaIstanbulOct1Duplicates['count'] }})</button>
      </form>
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100">
        @foreach($bursaIstanbulOct1Duplicates['rows'] as $f)
          <div class="flex items-center gap-3 p-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium truncate" title="{{ $f->name }}">#{{ $f->id }} {{ $f->name }}</div>
              <div class="text-xs text-gray-500 truncate" title="{{ $f->address }}">{{ $f->address }} @if($f->phone) · {{ $f->phone }} @endif</div>
            </div>
            <form method="POST" action="{{ route('admin.facilities.destroy', $f->id) }}" class="shrink-0" onsubmit="return confirm('Bu mükerrer kurum silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 font-bold text-xs whitespace-nowrap px-2 py-1 rounded hover:bg-red-50">Sil</button>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-green-700">Mükerrer kayıt bulunamadı (hepsi temizlenmiş).</p>
    @endif
  </div>

  {{-- 7a. Adresi baska bir ili isaret eden kurumlar (Site Geneli) --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Adresi Başka Bir İli İşaret Eden Kurumlar (Site Geneli)</h2>
        <p class="text-sm text-gray-500 mt-1">Kayıtlı ili ile adres metninin sonundaki "İlçe/İl" kısmı uyuşmayan kurumlar. Veri çekici aradığı işletmeyi bulamayınca alakasız, başka şehirdeki bir işletmeyle eşleşmiş olabilir - sadece il etiketini değil muhtemelen tüm kaydı (isim, telefon, yorumlar) yanlış işletmeden almış olabilir, bu yüzden düzeltmek yerine silinmesi önerilir.</p>
      </div>
      <span class="text-2xl font-black {{ $addressCityMismatch['count'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $addressCityMismatch['count'] }}</span>
    </div>

    @if($addressCityMismatch['count'] > 0)
      <form method="POST" action="{{ route('admin.data-quality.fix-address-city-mismatch') }}" class="mb-3" onsubmit="return confirm('{{ $addressCityMismatch['count'] }} kurumun TÜMÜ silinecek. Emin misiniz?');">
        @csrf
        <button class="bg-red-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Sil ({{ $addressCityMismatch['count'] }})</button>
      </form>
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100 max-h-96 overflow-y-auto">
        @foreach($addressCityMismatch['rows'] as $f)
          <div class="flex items-center gap-3 p-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium truncate" title="{{ $f->name }}">#{{ $f->id }} {{ $f->name }}</div>
              <div class="text-xs text-gray-500 truncate" title="{{ $f->address }}">kayıtlı: {{ $f->kayitli_il }} → adres: {{ $f->adres_ili }} | {{ $f->address }}</div>
            </div>
            <form method="POST" action="{{ route('admin.facilities.destroy', $f->id) }}" class="shrink-0" onsubmit="return confirm('Bu kurum silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 font-bold text-xs whitespace-nowrap px-2 py-1 rounded hover:bg-red-50">Sil</button>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-green-700">Uyumsuz kayıt bulunamadı.</p>
    @endif
  </div>

  {{-- 7b. Site genelinde devlet kurumu tespiti - isaretleme (silme degil) --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Devlet Kurumu Tespiti (Site Geneli) - Kamu Olarak İşaretle</h2>
        <p class="text-sm text-gray-500 mt-1">İsminde TOKİ/BUSMEK/YUVAMIZ geçen veya kendi gerçek ilçesiyle aynı olan "{İlçe} Anaokulu" kalıbındaki kurumlar - sadece bugünkü değil, SİTE GENELİNDE. Bu SİLMEZ, "Kuruluş Türü = Kamu" olarak işaretler; Kurumlar sayfasındaki yeniden eklenen "Kuruluş Türü" filtresiyle hepsini görüp kendiniz silebilirsiniz.</p>
      </div>
      <span class="text-2xl font-black {{ $governmentName['count'] > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ $governmentName['count'] }}</span>
    </div>
    @if($governmentName['count'] > 0)
      <form method="POST" action="{{ route('admin.data-quality.fix-government-name') }}" onsubmit="return confirm('{{ $governmentName['count'] }} kurum \'Kamu\' olarak işaretlenecek (silinmeyecek). Emin misiniz?');">
        @csrf
        <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Tümünü Kamu Olarak İşaretle ({{ $governmentName['count'] }})</button>
      </form>
    @else
      <p class="text-sm text-green-700">İşaretlenecek yeni kurum yok.</p>
    @endif
  </div>

  {{-- 8. Devlet/belediye kurumlari (TOKI/BUSMEK/YUVAMIZ/ilce-adi kalibi) --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
      <div>
        <h2 class="font-black text-gray-950">Devlet/Belediye Kurumları (1 Ekim 2026)</h2>
        <p class="text-sm text-gray-500 mt-1">İsminde TOKİ/BUSMEK/YUVAMIZ geçen veya kendi gerçek ilçesiyle aynı olan "{İlçe} Anaokulu" kalıbındaki, isminden MEB/belediye kurumu olduğu anlaşılan kayıtlar. Platform özel/ücretli kurumlar için olduğundan kaldırılması istendi. Toplu silme engellendiği için her satırı ayrı ayrı silin.</p>
      </div>
      <span class="text-2xl font-black {{ $devletKurumu['count'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $devletKurumu['count'] }}</span>
    </div>

    @if($devletKurumu['count'] > 0)
      <div class="border border-gray-100 rounded-lg divide-y divide-gray-100">
        @foreach($devletKurumu['rows'] as $f)
          <div class="flex items-center gap-3 p-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium truncate" title="{{ $f->name }}">#{{ $f->id }} {{ $f->name }}</div>
              <div class="text-xs text-gray-500 truncate" title="{{ $f->address }}">{{ $f->address }} @if($f->phone) · {{ $f->phone }} @endif</div>
            </div>
            <form method="POST" action="{{ route('admin.facilities.destroy', $f->id) }}" class="shrink-0" onsubmit="return confirm('Bu devlet kurumu silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 font-bold text-xs whitespace-nowrap px-2 py-1 rounded hover:bg-red-50">Sil</button>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-sm text-green-700">Kayıt bulunamadı (hepsi temizlenmiş).</p>
    @endif
  </div>

</div>

<script>
  // Silme/duzeltme formlari sayfayi yeniden yukluyor (POST->redirect), bu da
  // tarayiciyi varsayilan olarak sayfa basina atiyor. Kullanicinin uzun
  // listelerde her silmeden sonra asagi kaydirmasini engellemek icin,
  // gonderim oncesi kayma konumu kaydedilip yeni sayfada geri yukleniyor.
  document.addEventListener('submit', function (e) {
    if (e.target.tagName === 'FORM') {
      sessionStorage.setItem('veriDenetimiScrollY', String(window.scrollY));
    }
  });
  (function () {
    var y = sessionStorage.getItem('veriDenetimiScrollY');
    if (y !== null) {
      window.scrollTo(0, parseInt(y, 10));
      sessionStorage.removeItem('veriDenetimiScrollY');
    }
  })();
</script>
@endsection
