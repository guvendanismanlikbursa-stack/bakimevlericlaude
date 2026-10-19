<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pazarlamacı CRM</title>
<meta name="robots" content="noindex, nofollow">
<link rel="manifest" href="{{ route('marketer.manifest') }}">
<meta name="theme-color" content="#111827">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="CRM">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logo-bakimevleri-192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo-bakimevleri-180.png') }}">
@vite('resources/css/app.css')
{{-- 28 Eylul 2026: kullanicinin talebi - pazarlamaci ekran goruntusu/
     kopyalama yapamamali. GERCEK: hicbir web sayfasi isletim sistemi
     seviyesindeki ekran goruntusunu (veya baska bir cihazla fotograf
     cekmeyi) teknik olarak ENGELLEYEMEZ - bu bir tarayici/JS kisitlamasi
     degil. Yapilabilecek GERCEKCI iki sey: (1) kopyalama/sag-tik/secmeyi
     zorlastirmak (deterrent, kesin engel degil), (2) sizinti olursa
     KAYNAGI belli etsin diye sayfaya pazarlamacinin adi+telefonu+ani
     goruntuleyen bir filigran basmak - ciddi CRM/istihbarat
     uygulamalarinin (ör. Bloomberg Terminal) kullandigi gercek yontem
     budur. --}}
<style>
  body { -webkit-user-select: none; user-select: none; -webkit-touch-callout: none; }
  input, textarea { -webkit-user-select: text; user-select: text; }
  .marketer-watermark {
    position: fixed; inset: 0; z-index: 9999; pointer-events: none;
    display: flex; flex-wrap: wrap; align-content: space-around; justify-content: space-around;
    opacity: 0.09; overflow: hidden;
  }
  .marketer-watermark span { transform: rotate(-30deg); font-size: 13px; font-weight: 700; color: #111827; white-space: nowrap; margin: 24px; }
</style>
</head>
<body class="bg-gray-100 min-h-screen" oncontextmenu="return false" oncopy="return false" oncut="return false">
  <div class="marketer-watermark" aria-hidden="true">
    @for($i = 0; $i < 40; $i++)
      <span>{{ session('marketer_name') }} · {{ now()->format('d.m.Y H:i') }}</span>
    @endfor
  </div>

  <div class="bg-gray-900 text-white px-4 py-3 flex items-center justify-between">
    <div class="font-bold">Pazarlamacı CRM</div>
    <div class="flex items-center gap-4 text-sm">
      <span class="text-gray-300">{{ session('marketer_name') }}</span>
      <form method="POST" action="{{ route('marketer.logout') }}">@csrf<button class="underline">Çıkış</button></form>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-4 py-6">
    @if(session('success'))
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    @php
      $filterParams = ['city' => request('city'), 'district' => request('district'), 'category' => request('category')];
    @endphp

    <div class="flex flex-wrap gap-2 mb-4">
      @foreach($groups as $key => $def)
        <a href="{{ route('marketer.crm.index', array_filter(array_merge($filterParams, ['group' => $key]))) }}"
           class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $group === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
          <span>{{ $def['title'] }}</span>
          <span class="text-xs {{ $group === $key ? 'text-gray-300' : 'text-gray-400' }}">{{ $groupCounts[$key] ?? 0 }}</span>
        </a>
      @endforeach
    </div>

    <form method="GET" data-district-map='@json($districtMap)' class="js-location-filter mb-5 flex gap-2 flex-wrap">
      <input type="hidden" name="group" value="{{ $group }}">
      <select name="city" onchange="this.form.submit()" class="js-city border rounded-lg px-3 py-2 text-sm">
        <option value="">Tüm İller</option>
        @foreach($cities as $city)
          <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
        @endforeach
      </select>
      <select name="district" data-selected="{{ request('district') }}" onchange="this.form.submit()" class="js-district border rounded-lg px-3 py-2 text-sm" @if(! request('city')) disabled @endif>
        <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
        @if(request('city'))
          @foreach(districts_for_city(optional($cities->firstWhere('slug', request('city')))->name ?? '') as $districtName)
            <option value="{{ $districtName }}" @selected(request('district') === $districtName)>{{ $districtName }}</option>
          @endforeach
        @endif
      </select>
      <select name="category" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
        <option value="">Tüm Bölümler / Kategoriler</option>
        @foreach($categories as $category)
          <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
        @endforeach
      </select>
      {{-- 19 Ekim 2026: internet sitesi durumu filtresi --}}
      <select name="site" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
        <option value="">İnternet Sitesi: Tümü</option>
        <option value="eksik" @selected(request('site') === 'eksik')>Kendi sitesi olmayanlar (hepsi)</option>
        @foreach(\App\Models\Facility::WEBSITE_STATUSES as $wsKey => $wsLabel)
          <option value="{{ $wsKey }}" @selected(request('site') === $wsKey)>{{ $wsLabel }}</option>
        @endforeach
        <option value="bilinmiyor" @selected(request('site') === 'bilinmiyor')>Henüz kontrol edilmedi</option>
      </select>
      @if(request('city') || request('district') || request('category') || request('site'))
        <a href="{{ route('marketer.crm.index', array_filter(['group' => $group])) }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
      @endif
    </form>

    @include('themes._shared.partials.location-filter-script')

    <div class="space-y-3">
      @forelse($facilities as $facility)
        <div class="bg-white rounded-xl shadow-sm p-4">
          <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
            <div>
              <div class="font-bold">{{ $facility->name }}</div>
              <div class="text-sm text-gray-500">{{ $facility->category?->name }} · {{ $facility->district }} / {{ $facility->city?->name }}</div>
              <div class="text-sm mt-1">📞 <a href="tel:{{ $facility->phone }}" class="font-semibold text-blue-700">{{ $facility->phone }}</a></div>
              @if($facility->website_status)<div class="mt-1">@include('partials.website-status-badge', ['facility' => $facility])</div>@endif
              @if($canViewOfferPage)
                <a href="{{ route('marketer.crm.offer-page', $facility) }}" target="_blank" rel="noopener" class="inline-block mt-2 rounded-lg border px-3 py-1 text-xs font-bold" style="border-color:#e9d5ff;color:#a21caf;">💛 Teklif Sayfasını Gör</a>
              @endif
            </div>
            <div class="text-right">
              @if($facility->marketing_status)
                <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-700">{{ $statusLabels[$facility->marketing_status] ?? $facility->marketing_status }}</span>
              @endif
              @if($facility->marketing_brochure_requested !== null)
                <div class="text-xs text-gray-500 mt-1">Broşür: {{ $facility->marketing_brochure_requested ? 'İstiyor' : 'İstemiyor' }}</div>
              @endif
            </div>
          </div>

          @if($facility->marketingNotes->isNotEmpty())
            <div class="border-t pt-2 mt-2 space-y-1">
              @foreach($facility->marketingNotes as $note)
                <div class="text-xs text-gray-500">
                  <span class="font-semibold text-gray-700">{{ $statusLabels[$note->status] ?? $note->status }}</span>
                  · {{ $note->created_at->format('d.m.Y H:i') }}
                  @if($note->contactLabel()) · 👤 {{ $note->contactLabel() }} @endif
                  @if($note->website_status) · 🌐 {{ \App\Models\FacilityMarketingNote::WEBSITE_STATUSES[$note->website_status] ?? $note->website_status }} @endif
                  @if($note->site_offer_interest) · {{ \App\Models\FacilityMarketingNote::SITE_OFFER_INTEREST[$note->site_offer_interest] ?? '' }} @endif
                  @if($note->note) — {{ $note->note }} @endif
                </div>
              @endforeach
            </div>
          @endif

          @if($facility->marketing_callback_at)
            <div class="text-xs font-semibold {{ $facility->marketing_callback_at->isFuture() ? 'text-amber-700' : 'text-gray-400' }} border-t pt-2 mt-2">
              📅 Aranacak zaman: {{ $facility->marketing_callback_at->format('d.m.Y H:i') }}{{ $facility->marketing_callback_at->isPast() ? ' (geçti)' : '' }}
            </div>
          @endif

          <form method="POST" action="{{ route('marketer.crm.store-note', $facility) }}" class="border-t pt-3 mt-3">
            @csrf
            <div class="flex flex-wrap items-end gap-2">
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Durum</label>
                <select name="status" required class="border rounded-lg px-2 py-1.5 text-sm">
                  <option value="called">Arandı</option>
                  <option value="reached_decision_maker">Yetkiliyle Görüştü</option>
                  <option value="listened_details">Detay Dinledi</option>
                  <option value="price_requested">Fiyat İstedi</option>
                  <option value="thinking">Düşünecek</option>
                  <option value="interested">İlgileniyor</option>
                  <option value="subscribed">Üye Oldu</option>
                  <option value="receipt_sent">Dekont Geldi</option>
                  <option value="not_interested">İlgilenmiyor</option>
                  <option value="removal_requested">Platformdan Kaldırılmasını İstiyor</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Broşür dağıtımı</label>
                <select name="brochure_requested" class="border rounded-lg px-2 py-1.5 text-sm">
                  <option value="">Sorulmadı / geçiliyor</option>
                  <option value="1">İstiyor</option>
                  <option value="0">İstemiyor</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Ne zaman aranacak</label>
                <input type="datetime-local" name="callback_at" class="border rounded-lg px-2 py-1.5 text-sm">
              </div>
              <button class="bg-gray-900 text-white text-sm font-semibold px-4 py-1.5 rounded-lg">Kaydet</button>
            </div>
            {{-- 18 Ekim 2026: kullanıcının talebi - görüşülen kişinin adı ve konumu. --}}
            <div class="mt-2 flex flex-wrap items-end gap-2">
              <div class="flex-1" style="min-width:12rem;">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Görüşülen kişinin adı</label>
                <input type="text" name="contact_name" maxlength="120" class="border rounded-lg px-2 py-1.5 text-sm w-full" placeholder="ör. Ayşe Yılmaz">
              </div>
              <div>
                <span class="block text-xs font-semibold text-gray-500 mb-1">Kurumdaki konumu</span>
                <div class="flex gap-3 text-sm py-1.5">
                  @foreach(\App\Models\FacilityMarketingNote::CONTACT_ROLES as $roleValue => $roleLabel)
                    <label class="inline-flex items-center gap-1"><input type="radio" name="contact_role" value="{{ $roleValue }}"> {{ $roleLabel }}</label>
                  @endforeach
                </div>
              </div>
            </div>
            {{-- 19 Ekim 2026: kurum sitesi fikri ölçümü (isteğe bağlı). --}}
            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
              <div>
                <span class="block text-xs font-semibold text-gray-500 mb-1">Kurumun web sitesi var mı?</span>
                <div class="flex flex-wrap gap-3 text-sm">
                  @foreach(\App\Models\FacilityMarketingNote::WEBSITE_STATUSES as $wsValue => $wsLabel)
                    <label class="inline-flex items-center gap-1"><input type="radio" name="website_status" value="{{ $wsValue }}"> {{ $wsLabel }}</label>
                  @endforeach
                </div>
              </div>
              <div>
                <span class="block text-xs font-semibold text-gray-500 mb-1">"Size internet sitesi de yapıyoruz" teklifine</span>
                <div class="flex flex-wrap gap-3 text-sm">
                  <label class="inline-flex items-center gap-1"><input type="radio" name="site_offer_interest" value="ilgilendi"> İlgilendi</label>
                  <label class="inline-flex items-center gap-1"><input type="radio" name="site_offer_interest" value="ilgilenmedi"> İlgilenmedi</label>
                </div>
              </div>
            </div>
            <div class="mt-2">
              <label class="block text-xs font-semibold text-gray-500 mb-1">Not</label>
              <input type="text" name="note" maxlength="2000" class="border rounded-lg px-2 py-1.5 text-sm w-full" placeholder="Görüşme notu (opsiyonel) — ör. Yetkili müsait değildi, Salı 14:00'te arayın dedi.">
            </div>
          </form>

          @if($facility->marketingNotes->isNotEmpty())
            <form method="POST" action="{{ route('marketer.crm.notify-admin', $facility) }}" class="mt-2">
              @csrf
              <button class="text-xs font-semibold text-blue-700 underline">Kaydı admine gönder</button>
            </form>
          @endif
        </div>
      @empty
        <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-400">Bu filtrede kurum yok.</div>
      @endforelse
    </div>

    <div class="mt-6">{{ $facilities->links() }}</div>
  </div>
</body>
</html>
