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
      <div class="border border-gray-100 rounded-lg overflow-hidden mb-3">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-2">Kurum</th><th class="p-2">Şehir</th><th class="p-2">Yeni Kategori</th></tr></thead>
          <tbody class="divide-y">
            @foreach(array_slice($miscategory['reassignable'], 0, 25) as $row)
              <tr><td class="p-2 font-medium">{{ $row['facility']->name }}</td><td class="p-2 text-gray-600">{{ $row['facility']->sehir }}</td><td class="p-2 text-gray-600">{{ $row['target'] }}</td></tr>
            @endforeach
          </tbody>
        </table>
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
      <div class="border border-gray-100 rounded-lg overflow-hidden mb-3">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-2">Kurum</th><th class="p-2">Telefon</th><th class="p-2">Kayıtlı</th><th class="p-2">Olması Gereken</th></tr></thead>
          <tbody class="divide-y">
            @foreach(array_slice($phoneType['mismatches'], 0, 25) as $row)
              <tr><td class="p-2 font-medium">{{ $row['facility']->name }}</td><td class="p-2 text-gray-600">{{ $row['facility']->phone }}</td><td class="p-2 text-gray-600">{{ $row['stored'] }}</td><td class="p-2 text-gray-600">{{ $row['computed'] }}</td></tr>
            @endforeach
          </tbody>
        </table>
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
      <div class="border border-gray-100 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-2">Kurum</th><th class="p-2">Şehir</th><th class="p-2"></th></tr></thead>
          <tbody class="divide-y">
            @foreach($ownership as $f)
              <tr>
                <td class="p-2 font-medium">{{ $f->name }}</td>
                <td class="p-2 text-gray-600">{{ $f->sehir }}</td>
                <td class="p-2 text-right">
                  <form method="POST" action="{{ route('admin.data-quality.fix-ownership') }}" class="inline-flex items-center gap-1">
                    @csrf
                    <input type="hidden" name="id" value="{{ $f->id }}">
                    <select name="type" class="border rounded-lg px-2 py-1 text-xs">
                      <option value="ozel">Özel</option>
                      <option value="vakif">Vakıf</option>
                      <option value="belediye">Belediye</option>
                      <option value="kamu" selected>Kamu (değiştirme)</option>
                    </select>
                    <button class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50">Uygula</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
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
      <div class="border border-gray-100 rounded-lg overflow-hidden mb-3">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-2">Kurum</th><th class="p-2">Şehir</th><th class="p-2">Metin</th><th class="p-2">Sistemdeki</th><th class="p-2">Durum</th></tr></thead>
          <tbody class="divide-y">
            @foreach($district['examples'] as $row)
              <tr>
                <td class="p-2 font-medium">{{ $row['facility']->name }}</td>
                <td class="p-2 text-gray-600">{{ $row['facility']->sehir }}</td>
                <td class="p-2 text-gray-600">{{ $row['facility']->district_text ?: '—' }}</td>
                <td class="p-2 text-gray-600">{{ $row['facility']->district_fk ?: '—' }}</td>
                <td class="p-2 text-gray-600">{{ $row['reason'] === 'sadece_fk' ? 'Metin boş' : 'Uyumsuz' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
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
      <div class="border border-gray-100 rounded-lg overflow-hidden mb-3">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-2">Şu An</th><th class="p-2">Düzeltilmiş</th></tr></thead>
          <tbody class="divide-y">
            @foreach(array_slice($nameCleanup['examples'], 0, 25) as $row)
              <tr><td class="p-2 font-medium">"{{ $row['old'] }}"</td><td class="p-2 text-gray-600">"{{ $row['new'] }}"</td></tr>
            @endforeach
          </tbody>
        </table>
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

</div>
@endsection
