@extends('admin.layout')
@section('title', $facility->exists ? 'Kurumu Düzenle' : 'Yeni Kurum')

@section('content')
@php
  $selectedServices = collect($facility->services ?? []);
  $imageCount = $facility->exists ? $facility->images->count() : 0;
  $remainingImages = max(0, 10 - $imageCount);
@endphp
{{-- 18 Agustos 2026: kullanicinin talebi - kayittan sonra artik listeye
     otomatik donulmuyor (bkz. Admin\FacilityController::update() ayni
     tarihli yorum), admin ayni kurumda birden fazla gorsel ekleme/silme
     islemini tek ziyarette rahatca yapabilsin diye. Filtrelenmis listeye
     donus artik BU gorunur butonla, admin isini bitirdiginde kendi
     kontrolunde yapilir. --}}
@isset($returnTo)
  <a href="{{ $returnTo }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 mb-3">← Listeye dön</a>
@endisset
<h1 class="text-2xl font-bold mb-6">{{ $facility->exists ? 'Kurumu Düzenle' : 'Yeni Kurum' }}</h1>

{{--
  25 Agustos 2026: kullanicinin talebi - "Yerinde Sahiplendirme" kutusu
  sayfanin en altindaydi, kurum yetkilisi yanindayken bulmak icin
  asagi kaydirmak gerekiyordu. Artik sayfanin EN USTUNDE, ana forma
  girmeden once.
--}}
@if($facility->exists)
  <div class="max-w-4xl mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
      <h2 class="font-bold mb-3">Sahiplenme Durumu</h2>
      @if($facility->is_claimed)
        <p class="text-sm text-green-700 font-semibold">Sahiplenilmiş ({{ $facility->claimed_at?->format('d.m.Y') }})</p>
        @foreach($facility->facilityUsers as $fu)
          <p class="text-sm text-gray-600 mt-1">
            Yetkili: {{ $fu->name }} ({{ $fu->email }}) &middot; {{ $fu->status }}
            &middot; Kayıt IP: <span class="font-mono">{{ $fu->signup_ip ?? '—' }}</span>
            @if($fu->signup_lat && $fu->signup_lng)
              &middot; <a href="https://www.google.com/maps?q={{ $fu->signup_lat }},{{ $fu->signup_lng }}" target="_blank" class="text-primary font-semibold">Haritada gör →</a>
            @endif
          </p>
        @endforeach
      @else
        <p class="text-sm text-gray-500 mb-3">Bu kurum henüz sahiplenilmedi (ön kayıtlı profil).</p>

        {{-- 19 Agustos 2026: kullanicinin talebi - kurumu yerinde ziyaret
             edip kurum yetkilisi o an sahiplenmek isterse, normal basvuru+
             belge+onay bekleme surecine gerek kalmadan admin buradan
             DOGRUDAN gecici sifre verebilsin (kimlik dogrulamasi zaten yuz
             yuze yapiliyor). Bkz. Admin\FacilityController::instantClaim(). --}}
        @if(session('instant_claim_credentials'))
          @php $cred = session('instant_claim_credentials'); @endphp
          <div class="rounded-lg border-2 border-green-300 bg-green-50 p-4 mb-3">
            <p class="text-sm font-black text-green-800 mb-2">✓ Kurum sahiplendirildi - giriş bilgileri {{ $cred['email'] }} adresine gönderildi. Yine de kurum yetkilisine iletin:</p>
            <p class="text-sm text-gray-800">E-posta: <span class="font-mono font-bold">{{ $cred['email'] }}</span></p>
            <p class="text-sm text-gray-800">Geçici şifre: <span class="font-mono font-bold text-lg">{{ $cred['password'] }}</span></p>
            <p class="text-sm text-gray-800">Giriş adresi: <a href="{{ $cred['login_url'] }}" target="_blank" class="text-primary underline">{{ $cred['login_url'] }}</a></p>
            @if($cred['whatsapp_url'] ?? null)
              <a href="{{ $cred['whatsapp_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 mt-3 bg-[#25D366] text-white font-bold text-sm px-4 py-2 rounded-lg">📱 Kurumun WhatsApp'ına gönder</a>
            @else
              <p class="text-xs text-amber-700 mt-2">Kurumun kayıtlı telefonu WhatsApp için uygun görünmüyor (sabit hat/eksik) - giriş bilgilerini elle iletin.</p>
            @endif
            <p class="text-xs text-gray-500 mt-2">Bu kutu sadece bir kez gösterilir, sayfayı yenilerseniz kaybolur - şimdi not edin.</p>
          </div>
        @endif

        {{-- 19 Agustos 2026: kullanicinin talebi ("1 ve 3. maddeleri duzelt",
             madde 3) - kucuk/gri <details> baglantisi kolayca gozden
             kaciyordu. Ayni katlanir davranis korunuyor, ama artik belirgin
             renkli/ikonlu bir kart - admin sayfayi taradiginda gozunden
             kacmasin diye. --}}
        <details class="mb-1 group border-2 border-amber-300 bg-amber-50 rounded-lg overflow-hidden" open>
          <summary class="list-none cursor-pointer select-none px-4 py-3 flex items-center gap-2 hover:bg-amber-100 transition">
            <span class="text-lg leading-none">📍</span>
            <span class="text-sm font-black text-amber-900">Yerinde sahiplendir</span>
            <span class="text-xs text-amber-700">— kurum yetkilisi şu an yanınızdaysa buraya tıklayın</span>
            <span class="ml-auto text-amber-600 transition-transform group-open:rotate-180">▾</span>
          </summary>
          {{--
            25 Agustos 2026: kullanicinin bildirdigi gercek hata - buton
            "Sahiplendir" yazisi beyazdi ve fark edilmiyordu; koyu yesil
            metne, daha belirgin bir stile cevrildi. Ayrica confirm()
            onceden BUTONUN onclick'indeydi - bir input alaninda Enter'a
            basilip form dogrudan (butona hic dokunmadan) gonderilirse bu
            onay hic calismazdi. Artik confirm() FORM'un onsubmit'inde,
            hangi yoldan gonderilirse gonderilsin calisir.
          --}}
          <form method="POST" action="{{ route('admin.facilities.instant-claim', $facility) }}" class="px-4 pb-4 flex flex-wrap gap-2 items-end" onsubmit="return confirm('Bu kurumu şimdi sahiplendirip geçici şifre oluşturmak istediğinize emin misiniz?');">
            @csrf
            <div><label class="text-xs text-gray-500 block">Yetkili Adı Soyadı</label><input type="text" name="applicant_name" required class="border rounded-lg px-3 py-1.5 text-sm w-44"></div>
            <div><label class="text-xs text-gray-500 block">E-posta</label><input type="email" name="applicant_email" required class="border rounded-lg px-3 py-1.5 text-sm w-52"></div>
            <div><label class="text-xs text-gray-500 block">Telefon</label><input type="text" name="applicant_phone" required class="border rounded-lg px-3 py-1.5 text-sm w-40"></div>
            <button type="submit" class="bg-white border-2 border-green-700 text-green-800 hover:bg-green-50 px-5 py-2 rounded-lg text-sm font-black">✓ Sahiplendir</button>
          </form>
          @error('applicant_email')<p class="text-xs text-red-600 px-4 pb-3">{{ $message }}</p>@enderror
        </details>
      @endif

      @if($facility->claims->isNotEmpty())
        <div class="mt-3">
          <p class="text-xs text-gray-400 mb-1">Başvuru geçmişi:</p>
          @foreach($facility->claims as $claim)
            <a href="{{ route('admin.claims.show', $claim) }}" class="block text-xs text-blue-600">{{ $claim->applicant_name }} &middot; {{ $claim->status }} ({{ $claim->created_at->format('d.m.Y') }})</a>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endif

{{-- 3 Eylul 2026: KRITIK duzeltme - "Videoyu Sil" formu daha once asagida
     ana duzenleme formunun ICINDE (nested <form>) idi. HTML <form> ice ice
     GECERSIZDIR; tarayici gecersiz ic formu yok sayip alanlarini (ozellikle
     _method=DELETE gizli input'unu) DIS forma tasir. Dis formun kendi
     _method=PUT alaniyla AYNI isimde ikinci bir _method alani olustugu icin,
     gonderimde SONUNCU deger (DELETE) kazanir - boylece "Videoyu Sil"e
     basmak degil, ayni sayfadaki HERHANGI bir submit (Kaydet dahil) butonu
     bile dis formu DELETE metoduyla /admin/kurumlar/{id} adresine gonderip
     KURUMUN TAMAMINI siliyordu (resource route'un destroy'una dusuyordu).
     Cozum: video-silme formu disariya tasindi, buton "form" ozniteligiyle
     (asagidaki 499. satirdaki referrals ile ayni, halihazirda kullanilan
     desen) ona bagliyor - artik gercek bir ic ice form yok. --}}
@if($facility->exists && $facility->video_path)
  <form id="video-delete-form" method="POST" action="{{ route('admin.facilities.video.destroy', $facility) }}" onsubmit="return confirm('Video silinsin mi?');">
    @csrf
    @method('DELETE')
  </form>
@endif

<form method="POST" action="{{ $facility->exists ? route('admin.facilities.update', $facility) : route('admin.facilities.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 max-w-4xl">
  @csrf
  @if($facility->exists) @method('PUT') @endif
  {{-- 14 Agustos 2026: kullanicinin talebi - kaydedince filtrelenmis
       listeye (nereden geldiyse) geri donsun, bkz. Admin\FacilityController
       edit()/update()/safeReturnTo(). --}}
  @isset($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endisset

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Kurum Adı</label>
    <input type="text" name="name" value="{{ old('name', $facility->name) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Şehir</label>
    <select id="facility-form-city" name="city_id" required class="border rounded-lg px-3 py-2 w-full mt-1">
      @foreach($cities as $city)
        <option value="{{ $city->id }}" @selected(old('city_id', $facility->city_id) == $city->id)>{{ $city->name }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="text-sm font-medium">Kategori / Ana Bölüm</label>
    <select name="facility_category_id" required class="border rounded-lg px-3 py-2 w-full mt-1">
      @foreach($categories as $category)
        @php $section = service_section_for_scope($category->brand_scope); @endphp
        <option value="{{ $category->id }}" @selected(old('facility_category_id', $facility->facility_category_id) == $category->id)>{{ $section['title'] ?? 'Bölüm yok' }} · {{ $category->name }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="text-sm font-medium">İlçe</label>
    <input type="text" id="facility-form-district" name="district" value="{{ old('district', $facility->district) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Telefon</label>
    <input type="text" name="phone" value="{{ old('phone', $facility->phone) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Adres</label>
    <input type="text" id="facility-form-address" name="address" value="{{ old('address', $facility->address) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  {{-- 29 Agustos 2026: kullanicinin talebi - kimse elle koordinat bilmiyor,
       eskiden "Google Maps'te sag tiklayip kopyalayin" diye anlatiliyordu.
       Artik adres/ilce/sehir'e gore ucretsiz Nominatim (OpenStreetMap)
       servisiyle otomatik konum buluyor ve Leaflet/OSM haritasinda
       surukleyerek duzeltilebilen bir pin gosteriyor - lat/lng alanlari
       pin'in konumuna gore otomatik dolar, elle de duzenlenebilir kalir. --}}
  <div class="md:col-span-2 rounded-xl border border-gray-100 bg-gray-50 p-4">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-2">
      <label class="text-sm font-black text-gray-900">Haritadaki Konum</label>
      <button type="button" id="facility-geocode-btn" class="bg-primary text-white text-xs font-bold px-3 py-2 rounded-lg">📍 Adresten konum bul</button>
    </div>
    <p class="text-xs text-gray-500 mb-3">Adres/ilçe/şehir dolduktan sonra yukarıdaki butona basın, harita otomatik açılır. Pin yanlış yerdeyse üzerine tıklayıp sürükleyerek düzeltebilirsiniz.</p>
    <div id="facility-geocode-status" class="text-xs font-semibold mb-2"></div>
    <div id="facility-location-map" class="hidden rounded-lg border border-gray-200" style="height:280px;"></div>

    <div class="grid grid-cols-2 gap-3 mt-3">
      <div>
        <label class="text-xs text-gray-500">Enlem (lat)</label>
        <input type="text" id="facility-form-lat" name="lat" value="{{ old('lat', $facility->lat) }}" placeholder="Örn: 40.1826" class="border rounded-lg px-3 py-2 w-full mt-1 text-sm">
      </div>
      <div>
        <label class="text-xs text-gray-500">Boylam (lng)</label>
        <input type="text" id="facility-form-lng" name="lng" value="{{ old('lng', $facility->lng) }}" placeholder="Örn: 29.0670" class="border rounded-lg px-3 py-2 w-full mt-1 text-sm">
      </div>
    </div>
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Açıklama</label>
    <textarea name="description" rows="4" class="border rounded-lg px-3 py-2 w-full mt-1">{{ old('description', $facility->description) }}</textarea>
  </div>

  <div>
    <label class="text-sm font-medium">Kapasite</label>
    <input type="number" name="capacity" value="{{ old('capacity', $facility->capacity) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Kapak Görseli URL</label>
    <input type="text" name="cover_image" value="{{ old('cover_image', $facility->cover_image) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  {{-- 4 Eylul 2026: kullanicinin talebi - Google Maps'ten cekilen kurumlarin
       (source=google_maps_veri_cekici) puani yanlis/eski olabiliyordu,
       admin elle duzeltebilsin diye eklendi. --}}
  <div>
    <label class="text-sm font-medium">Puan (0-5) @if($facility->source === 'google_maps_veri_cekici')<span class="text-xs font-normal text-gray-400">— Google Maps'ten çekildi</span>@endif</label>
    <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $facility->rating) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Min Fiyat</label>
    <input type="number" step="0.01" name="price_min" value="{{ old('price_min', $facility->price_min) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Maks Fiyat</label>
    <input type="number" step="0.01" name="price_max" value="{{ old('price_max', $facility->price_max) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="md:col-span-2 -mt-2">
    @php $tier = $facility->priceTier(); @endphp
    <span class="text-xs text-gray-400">Hesaplanan segment:</span>
    @if($tier)
      <span class="{{ $tier['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full ml-1">{{ $tier['emoji'] }} {{ $tier['label'] }}</span>
    @else
      <span class="text-xs text-gray-400 ml-1">Min fiyat girilmeden segment hesaplanamaz.</span>
    @endif
    <a href="{{ route('admin.settings.edit') }}" class="text-xs text-primary underline ml-2">Eşikleri düzenle</a>
  </div>

  @include('admin.facilities._price-options', [
    'optionsTitle' => 'Oda Tipine Göre Fiyat Aralığı',
    'optionsDescription' => 'Yaşlı bakım/huzurevi kurumları için. Sadece doldurduğunuz oda tipleri kurum sayfasında ayrı bir tablo olarak gösterilir. Boş bırakılırsa (daha önce girilmişse) o tipin kaydı silinir.',
    'optionsTypes' => \App\Models\FacilityRoomType::TYPES,
    'optionsInputKey' => 'room_types',
    'optionsExisting' => $facility->exists ? $facility->roomTypes->keyBy('room_type') : collect(),
  ])

  @include('admin.facilities._price-options', [
    'optionsTitle' => 'Yaş Grubuna Göre Fiyat Aralığı',
    'optionsDescription' => 'Çocuk bakım/kreş-anaokulu kurumları için. Sadece doldurduğunuz yaş grupları kurum sayfasında ayrı bir tablo olarak gösterilir. Boş bırakılırsa (daha önce girilmişse) o grubun kaydı silinir.',
    'optionsTypes' => \App\Models\FacilityAgeGroup::TYPES,
    'optionsInputKey' => 'age_groups',
    'optionsExisting' => $facility->exists ? $facility->ageGroups->keyBy('age_group') : collect(),
  ])

  @include('admin.facilities._price-options', [
    'optionsTitle' => 'Program Süresine Göre Fiyat Aralığı',
    'optionsDescription' => 'Çocuk bakım/kreş-anaokulu kurumları için. Sadece doldurduğunuz program süreleri kurum sayfasında ayrı bir tablo olarak gösterilir. Boş bırakılırsa (daha önce girilmişse) o sürenin kaydı silinir.',
    'optionsTypes' => \App\Models\FacilityProgramType::TYPES,
    'optionsInputKey' => 'program_types',
    'optionsExisting' => $facility->exists ? $facility->programTypes->keyBy('program_type') : collect(),
  ])

  <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
    <label class="text-sm font-semibold block mb-3">Bölüme göre özellik önerileri</label>
    <div class="grid md:grid-cols-3 gap-3">
      @foreach($serviceSections as $section)
        <div class="bg-white border rounded-lg p-3">
          <div class="font-semibold text-sm mb-2 flex items-center gap-2">@include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-5 h-5'])<span>{{ $section['title'] }}</span></div>
          <div class="space-y-1">
            @foreach($section['features'] as $feature)
              <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" name="services[]" value="{{ $feature }}" @checked($selectedServices->contains($feature))>
                <span>{{ $feature }}</span>
              </label>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Ek hizmetler (virgülle ayırın)</label>
    <input type="text" name="services_raw" value="{{ old('services_raw', $selectedServices->diff(collect($serviceSections)->flatMap(fn ($s) => $s['features'])->all())->implode(', ')) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="flex gap-6 md:col-span-2">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $facility->exists ? $facility->is_published : true))> Yayında</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $facility->is_featured))> Öne çıkar</label>
    {{-- 26 Agustos 2026: kullanicinin talebi - "bakim takip ziyareti" hizmeti
         SADECE yasli-bakim bolumunde VE kurumun onayi alindiktan sonra
         admin'in acikca isaretledigi kurumlarda gorunur (bkz. facilities.
         allows_visit_service migration ayni tarihli yorum). --}}
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allows_visit_service" value="1" @checked(old('allows_visit_service', $facility->allows_visit_service))> Ziyaret hizmetine açık</label>
  </div>

  {{-- 29 Agustos 2026: kullanicinin talebi - anlasmali kurumun ekip
       tarafindan yerinde ziyaret edildigini kurum detay sayfasinda durust
       bir rozetle gostermek icin. Checkbox ISARETLENIRSE ve daha once BOS
       ise sunucu tarafinda o AN'in tarihi yazilir (bkz. FacilityController
       ayni tarihli yorum) - boylece "ne zaman ziyaret edildi" bilgisi de
       kaydedilmis olur, sadece evet/hayir degil. --}}
  <div class="md:col-span-2">
    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="site_visited" value="1" @checked(old('site_visited', (bool) $facility->site_visited_at))>
      Bu kurum ekibimiz tarafından yerinde ziyaret edildi
    </label>
    @if($facility->site_visited_at)
      <p class="text-xs text-gray-400 mt-1 ml-6">Ziyaret tarihi: {{ $facility->site_visited_at->format('d.m.Y') }} — işareti kaldırıp tekrar kaydederseniz bu bilgi silinir.</p>
    @endif
  </div>

  {{-- 1 Eylul 2026: kullanicinin bildirdigi gercek hata - "Boş Yer Durumu"
       SADECE kurumun KENDI panelinden (Facility\ProfileController::
       updateVacancy(), oturum acmis bir FacilityUser gerektirir)
       guncellenebiliyordu. Sahiplenilmemis (ozellikle anlasmali-ama-
       sahiplenilmemis, hic FacilityUser hesabi olmayan) kurumlarda bu
       bilgiyi guncelleyecek HICBIR yol yoktu - kurum sayfasindaki "boş yer"
       banner'i bu kurumlar icin asla gorunemiyordu. Admin panelinden de
       ayni alanlar duzenlenebilsin diye, kurum panelindeki AYNI form
       deseni buraya tasindi. --}}
  <div class="md:col-span-2 border-t pt-4">
    <label class="text-sm font-medium block mb-2">Boş Yer Durumu</label>
    @php
      // old() bir form hatasi sonrasi '0'/'1' string'i olarak gelir, ilk
      // yuklemede $facility->vacancy_* gercek bir bool|null'dur - ikisini
      // TEK bir uc-durumlu ('1'/'0'/null) degere indirger, asagidaki
      // secenekler bunu karsilastirir.
      $vacancyValue = function (string $field) use ($facility) {
        $default = $facility->{$field} === null ? '' : ($facility->{$field} ? '1' : '0');
        $value = old($field, $default);

        return $value === '' ? null : $value;
      };
    @endphp
    <div class="grid sm:grid-cols-2 gap-3 max-w-lg">
      @if($facility->usesGenderSplitVacancy())
        <div>
          <label for="admin-vacancy-male" class="text-xs text-gray-500">Bay</label>
          <select id="admin-vacancy-male" name="vacancy_male" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="" @selected($vacancyValue('vacancy_male') === null)>Belirtilmedi</option>
            <option value="1" @selected($vacancyValue('vacancy_male') === '1')>Var</option>
            <option value="0" @selected($vacancyValue('vacancy_male') === '0')>Yok</option>
          </select>
        </div>
        <div>
          <label for="admin-vacancy-female" class="text-xs text-gray-500">Bayan</label>
          <select id="admin-vacancy-female" name="vacancy_female" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="" @selected($vacancyValue('vacancy_female') === null)>Belirtilmedi</option>
            <option value="1" @selected($vacancyValue('vacancy_female') === '1')>Var</option>
            <option value="0" @selected($vacancyValue('vacancy_female') === '0')>Yok</option>
          </select>
        </div>
      @else
        <div>
          <label for="admin-vacancy-general" class="text-xs text-gray-500">Boş Yer</label>
          <select id="admin-vacancy-general" name="vacancy_general" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="" @selected($vacancyValue('vacancy_general') === null)>Belirtilmedi</option>
            <option value="1" @selected($vacancyValue('vacancy_general') === '1')>Var</option>
            <option value="0" @selected($vacancyValue('vacancy_general') === '0')>Yok</option>
          </select>
        </div>
      @endif
    </div>
    @if($facility->vacancy_updated_at)
      <p class="text-xs text-gray-400 mt-1">Son güncelleme: {{ $facility->vacancy_updated_at->diffForHumans() }}</p>
    @endif
  </div>

  <div>
    <label class="text-sm font-medium">Bakanlık/Resmi Onay Rozeti</label>
    <select name="ministry_verification" class="border rounded-lg px-3 py-2 w-full mt-1">
      <option value="" @selected(old('ministry_verification', $facility->ministry_verification) === null)>— Rozet yok —</option>
      <option value="verified" @selected(old('ministry_verification', $facility->ministry_verification) === 'verified')>Doğrulandı (Özel)</option>
      <option value="kamu_vakif" @selected(old('ministry_verification', $facility->ministry_verification) === 'kamu_vakif')>Kamu/Belediye/Vakıf</option>
      <option value="review" @selected(old('ministry_verification', $facility->ministry_verification) === 'review')>İncelenmeli</option>
      <option value="unverified" @selected(old('ministry_verification', $facility->ministry_verification) === 'unverified')>Doğrulanamadı</option>
    </select>
    <p class="text-xs text-gray-500 mt-1">Kurum kartı ve detay sayfasında rozet olarak gösterilir.</p>
  </div>

  <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
    <div class="flex items-center justify-between gap-3 mb-2">
      <label class="text-sm font-semibold">Demo / Galeri Görselleri</label>
      <span class="text-xs font-semibold rounded-full px-3 py-1 {{ $remainingImages > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">{{ $imageCount }}/10 yüklü</span>
    </div>
    @if($remainingImages > 0)
      <input type="file" name="images[]" multiple accept="image/*" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <p class="text-xs text-gray-500 mt-1">En fazla 10 görsel olabilir. Bu kurum için kalan yükleme hakkı: {{ $remainingImages }}.</p>
    @else
      <div class="rounded-lg bg-white border border-gray-100 p-3 text-sm text-gray-500">10 görsel limiti doldu. Yeni görsel eklemek için önce mevcut görsellerden birini silin.</div>
    @endif
  </div>

  {{-- 27 Agustos 2026: kullanicinin talebi - tanitim videosu SADECE
       anlasmali (is_broker_managed) kurumlar icin. Bu anahtar ayri bir
       ekrandan ("Anlaşmalı Kurumlar" -> admin.broker.facilities.toggle)
       yonetildigi icin, burada sadece MEVCUT durumuna gore gosterilir/
       gizlenir - JS gerekmez. --}}
  @if($facility->exists && $facility->is_broker_managed)
    <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
      <label class="text-sm font-semibold block mb-1">Tanıtım Videosu <span class="text-xs font-normal text-gray-400">(sadece anlaşmalı kurumlar)</span></label>
      <p class="text-xs text-gray-500 mb-3">En fazla 60 saniye. Yüklenince otomatik olarak sıkıştırılır, biraz zaman alabilir.</p>
      @if($facility->video_path)
        {{-- 3 Eylul 2026: kullanicinin bildirdigi gercek hata - "Kaydet
             butonu tiklanmiyor, SADECE video altindaki". Bu videoda
             YUKSEKLIK sinirlamasi hic yoktu (sadece max-w-sm genislik) -
             dikey (portre) bir video, hesaplanan yuksekligiyle altindaki
             icerigin (Kaydet butonu dahil) UZERINE tasip tiklamalari
             yutuyor olabilirdi. Inline style ile kesin bir yukseklik
             sinirlamasi eklendi (bkz. themes._shared.facilities.show.blade.php
             ayni tarihli, ayni kok nedenli duzeltme). --}}
        <video src="{{ facility_asset($facility->video_path) }}" controls class="w-full max-w-sm rounded-lg mb-3" style="max-height:400px;"></video>
        <div class="mb-3">
          <button type="submit" form="video-delete-form" class="text-red-600 text-xs font-bold hover:underline">Videoyu Sil</button>
        </div>
        <p class="text-xs text-gray-500 mb-2">Yeni bir video yüklerseniz, bu videonun yerine geçer:</p>
      @endif
      <input type="file" name="video" accept="video/*" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
    </div>
  @endif

  {{-- 3 Eylul 2026: yukaridaki video yukseklik duzeltmesiyle birlikte,
       bu butonun kendisi de HERHANGI bir olasi ust uste binmeye karsi
       kendi katmaninda (position:relative + yuksek z-index) garantiye
       alindi - video duzeltmesi tek basina yetmezse bile buton artik
       tiklanabilir kalir. --}}
  <div class="md:col-span-2" style="position:relative;z-index:10;">
    <button type="submit" class="bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold">Kaydet</button>
  </div>
</form>

{{-- 4 Eylul 2026: kullanicinin talebi - anlaşmalı kurumlarin tanitimi
     "süper" gorunsun diye admin buradan dogrudan (aile hesabina gerek
     olmadan) bir yorum ekleyebilir. Ayni facility_reviews tablosuna,
     status=approved olarak yazilir - kurum sayfasinda organik bir aile
     yorumundan hicbir gorsel/veri farki olmaz (bkz. Admin\
     FacilityReviewController::store() ayni tarihli yorum). --}}
@if($facility->exists && $facility->is_broker_managed)
  <div class="max-w-4xl mt-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
      <h2 class="font-bold mb-1">Yorum Ekle <span class="text-xs font-normal text-gray-400">(anlaşmalı kurum)</span></h2>
      <p class="text-xs text-gray-500 mb-4">Telefon/WhatsApp üzerinden topladığınız bir aile geri bildirimini buradan ekleyebilirsiniz — kurum sayfasında ailelerin platform üzerinden yazdığı yorumlarla birebir aynı şekilde görünür.</p>
      <form method="POST" action="{{ route('admin.reviews.store-for-facility', $facility) }}" class="grid md:grid-cols-3 gap-3">
        @csrf
        <div>
          <label class="text-sm font-medium">Aile Adı</label>
          <input type="text" name="reviewer_name" required maxlength="120" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="ör. Ayşe Y.">
        </div>
        <div>
          <label class="text-sm font-medium">Puan</label>
          <select name="rating" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="5">★★★★★ (5)</option>
            <option value="4">★★★★ (4)</option>
            <option value="3">★★★ (3)</option>
            <option value="2">★★ (2)</option>
            <option value="1">★ (1)</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold w-full">Yorumu Ekle ve Yayınla</button>
        </div>
        <div class="md:col-span-3">
          <label class="text-sm font-medium">Yorum Metni</label>
          <textarea name="body" rows="2" maxlength="2000" class="border rounded-lg px-3 py-2 w-full mt-1"></textarea>
        </div>
      </form>

      @if($facility->approvedReviews->isNotEmpty())
        <div class="mt-5 pt-5 border-t border-gray-100 space-y-2">
          <div class="text-xs font-semibold text-gray-500 mb-2">Yayındaki yorumlar ({{ $facility->approvedReviews->count() }})</div>
          @foreach($facility->approvedReviews as $review)
            <div class="flex items-center justify-between gap-3 bg-gray-50 rounded-lg px-3 py-2 text-sm">
              <div class="min-w-0">
                <span class="font-bold">{{ $review->reviewer_name }}</span>
                <span class="text-amber-700 font-black ml-1">★ {{ $review->rating }}</span>
                @if($review->body)<div class="text-gray-500 text-xs mt-0.5 line-clamp-1">{{ $review->body }}</div>@endif
              </div>
              <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Bu yorum silinsin mi?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 text-xs font-bold hover:underline whitespace-nowrap">Sil</button>
              </form>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endif

{{-- 29 Agustos 2026: kullanicinin talebi - "gorsel ekle" alani ile "mevcut
     galeri" ust uste/bitisik olsun istiyor, aralarinda "Bakiye / Hak"
     karti vardi. Mevcut Galeri karti buraya (formun hemen alti) tasindi,
     Bakiye/Hak karti asagida kaldi - sadece siralama degisti, mantik ayni. --}}
@if($facility->exists)
  <div class="max-w-4xl mt-8 space-y-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between gap-3 mb-3">
        <h2 class="font-bold">Mevcut Görseller</h2>
        <span class="text-xs font-semibold rounded-full bg-gray-100 text-gray-600 px-3 py-1">{{ $imageCount }}/10</span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        {{-- 19 Agustos 2026: kullanicinin talebi - hangi gorselin ANA (kapak)
             gorsel oldugu buradan secilebilir (bkz. Facility::primaryImage()). --}}
        @foreach($facility->images->take(10) as $img)
          <div class="relative">
            <img src="{{ facility_asset($img->path) }}" class="rounded-lg h-24 w-full object-cover border-2 {{ $img->is_primary ? 'border-amber-400' : 'border-gray-100' }}">
            @if($img->is_primary)
              <span class="absolute bottom-1 left-1 bg-amber-400 text-amber-950 text-[10px] font-black px-1.5 py-0.5 rounded">★ Ana Görsel</span>
            @else
              <form method="POST" action="{{ route('admin.facilities.image.set-primary', $img) }}" class="absolute bottom-1 left-1">
                @csrf
                <button type="submit" class="bg-white/90 text-gray-700 text-[10px] font-semibold px-1.5 py-0.5 rounded hover:bg-white">Ana Görsel Yap</button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.facilities.image.destroy', $img) }}" class="absolute top-1 right-1">
              @csrf @method('DELETE')
              <button type="submit" class="bg-white/90 text-red-600 text-xs px-2 py-0.5 rounded">Sil</button>
            </form>
          </div>
        @endforeach
        @for($i = $imageCount; $i < 10; $i++)
          <div class="h-24 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-xs text-gray-400 text-center px-2">Görsel alanı<br>{{ $i + 1 }}/10</div>
        @endfor
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
      <h2 class="font-bold mb-3">Bakiye / Hak (Manuel Düzenleme)</h2>
      <p class="text-sm text-gray-600 mb-3">Ücretsiz Hak: <strong>{{ $facility->free_quote_credits }}</strong> &middot; Bakiye: <strong>{{ number_format($facility->balance,2,',','.') }} TL</strong></p>
      <form method="POST" action="{{ route('admin.facilities.balance.adjust', $facility) }}" class="flex flex-wrap gap-2 items-end">
        @csrf
        <div><label class="text-xs text-gray-500 block">Bakiye Değişimi (TL, +/-)</label><input type="number" step="0.01" name="balance_delta" placeholder="örn: 100 veya -50" class="border rounded-lg px-3 py-1.5 text-sm w-40"></div>
        <div><label class="text-xs text-gray-500 block">Hak Değişimi (+/-)</label><input type="number" name="credits_delta" placeholder="örn: 5 veya -2" class="border rounded-lg px-3 py-1.5 text-sm w-32"></div>
        <div class="flex-1 min-w-[160px]"><label class="text-xs text-gray-500 block">Not</label><input type="text" name="note" placeholder="Sebep" class="border rounded-lg px-3 py-1.5 text-sm w-full"></div>
        <button type="submit" class="bg-gray-900 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Uygula</button>
      </form>

      <div class="border-t border-gray-100 mt-4 pt-4">
        <p class="text-sm text-gray-600 mb-2">Bu kurum için özel teklif ücreti:
          <strong>{{ $facility->quote_price_override !== null ? number_format($facility->quote_price_override,2,',','.').' TL' : 'Yok (genel ücret geçerli)' }}</strong>
        </p>
        <form method="POST" action="{{ route('admin.facilities.balance.adjust', $facility) }}" class="flex flex-wrap gap-2 items-end">
          @csrf
          <div><label class="text-xs text-gray-500 block">Özel Teklif Ücreti (TL)</label><input type="number" step="0.01" min="0" name="quote_price_override" placeholder="örn: 150" class="border rounded-lg px-3 py-1.5 text-sm w-40"></div>
          <button type="submit" class="bg-gray-900 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Kaydet</button>
          @if($facility->quote_price_override !== null)
            <button type="submit" name="clear_quote_price_override" value="1" class="bg-white border border-gray-300 text-gray-600 px-4 py-1.5 rounded-lg text-sm font-semibold">Genel ücrete dön</button>
          @endif
        </form>
      </div>

      @php
        $balanceLogTypeLabels = [
            'topup_approved' => 'Bakiye yükleme onaylandı',
            'registration_bonus_credits' => 'Kayıt bonus hakkı',
            'claim_bonus_credits' => 'Sahiplenme bonus hakkı',
            'admin_adjust_balance' => 'Admin bakiye düzenlemesi',
            'admin_adjust_credits' => 'Admin hak düzenlemesi',
            'quote_charge_credit' => 'Teklif verildi (ücretsiz hak kullanıldı)',
            'quote_charge_balance' => 'Teklif verildi (bakiyeden düşüldü)',
            'claim_reverted' => 'Sahiplenme geri alındı (bonus sıfırlandı)',
        ];
      @endphp

      <div class="border-t border-gray-100 mt-4 pt-4">
        <h3 class="font-semibold text-sm mb-2">Bakiye / Hak Geçmişi</h3>
        @if($facility->balanceLogs->isEmpty())
          <p class="text-sm text-gray-500">Henüz bir hareket kaydı yok.</p>
        @else
          {{-- 25 Agustos 2026: kullanicinin talebi - yanlislikla iki kez
               eklenen "Sahiplenme bonus hakkı" gibi kayitlar dogrudan bu
               tablodan duzenlenebilsin/silinebilsin. Girdi alanlari
               tablodan sonraki gizli <form>'lara "form" ozniteligiyle
               baglanir (referrals tablosundaki ayni desen) - <form>
               elemani <tr>/<td> disina, HTML kurallarina uygun sekilde
               tasinir. --}}
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100">
                  <th class="py-1.5 pr-3">Tarih</th>
                  <th class="py-1.5 pr-3">İşlem</th>
                  <th class="py-1.5 pr-3 text-right">Tutar</th>
                  <th class="py-1.5 pr-3 text-right">Hak</th>
                  <th class="py-1.5 pr-3 text-right">Bakiye Sonrası</th>
                  <th class="py-1.5 pr-3 text-right">Hak Sonrası</th>
                  <th class="py-1.5">Not</th>
                  <th class="py-1.5">İşlemler</th>
                </tr>
              </thead>
              <tbody>
                @foreach($facility->balanceLogs->sortByDesc('created_at') as $log)
                  <tr class="border-b border-gray-50">
                    <td class="py-1.5 pr-3 whitespace-nowrap text-gray-500">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                    <td class="py-1.5 pr-3">{{ $balanceLogTypeLabels[$log->type] ?? $log->type }}</td>
                    <td class="py-1.5 pr-3 text-right">
                      <input type="number" step="0.01" name="amount" form="log-edit-{{ $log->id }}" value="{{ $log->amount }}" class="border rounded px-1.5 py-1 text-right text-sm w-24 {{ $log->amount < 0 ? 'text-red-600' : ($log->amount > 0 ? 'text-green-700' : '') }}">
                    </td>
                    <td class="py-1.5 pr-3 text-right">
                      <input type="number" name="credits_amount" form="log-edit-{{ $log->id }}" value="{{ $log->credits_amount }}" class="border rounded px-1.5 py-1 text-right text-sm w-16 {{ $log->credits_amount < 0 ? 'text-red-600' : ($log->credits_amount > 0 ? 'text-green-700' : '') }}">
                    </td>
                    <td class="py-1.5 pr-3 text-right text-gray-600">{{ number_format($log->balance_after, 2, ',', '.') }} TL</td>
                    <td class="py-1.5 pr-3 text-right text-gray-600">{{ $log->credits_after }}</td>
                    <td class="py-1.5">
                      <input type="text" name="note" form="log-edit-{{ $log->id }}" value="{{ $log->note }}" class="border rounded px-1.5 py-1 text-sm w-full min-w-[140px]">
                    </td>
                    <td class="py-1.5 whitespace-nowrap">
                      <button type="submit" form="log-edit-{{ $log->id }}" class="text-xs font-semibold text-primary hover:underline">Kaydet</button>
                      <button type="submit" form="log-delete-{{ $log->id }}" class="text-xs font-semibold text-red-600 hover:underline ml-2">Sil</button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @foreach($facility->balanceLogs as $log)
            <form id="log-edit-{{ $log->id }}" method="POST" action="{{ route('admin.facilities.balance-log.update', [$facility, $log]) }}" onsubmit="return confirm('Bu hareket kaydı güncellensin ve bakiye/hak buna göre yeniden hesaplansın mı?');">
              @csrf
              @method('PUT')
            </form>
            <form id="log-delete-{{ $log->id }}" method="POST" action="{{ route('admin.facilities.balance-log.destroy', [$facility, $log]) }}" onsubmit="return confirm('Bu hareket kaydı silinsin ve etkisi (tutar/hak) mevcut bakiyeden geri alınsın mı?');">
              @csrf
              @method('DELETE')
            </form>
          @endforeach
        @endif
      </div>
    </div>

  </div>
@endif

{{-- 29 Agustos 2026: kullanicinin talebi - bkz. yukaridaki "Haritadaki Konum"
     kutusu yorumu. Leaflet (OSM tabanli, API anahtari gerektirmeyen, ucretsiz)
     harita kutuphanesi + Nominatim (ucretsiz OSM geocoding) ile adres/ilce/
     sehir metninden otomatik lat/lng bulunur, surukle-birak pin ile
     duzeltilebilir. --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var addressEl = document.getElementById('facility-form-address');
  var districtEl = document.getElementById('facility-form-district');
  var cityEl = document.getElementById('facility-form-city');
  var latEl = document.getElementById('facility-form-lat');
  var lngEl = document.getElementById('facility-form-lng');
  var btn = document.getElementById('facility-geocode-btn');
  var statusEl = document.getElementById('facility-geocode-status');
  var mapEl = document.getElementById('facility-location-map');
  if (!btn || !mapEl || typeof L === 'undefined') return;

  var map = null;
  var marker = null;

  function setStatus(text, colorClass) {
    statusEl.textContent = text;
    statusEl.className = 'text-xs font-semibold mb-2 ' + colorClass;
  }

  function showMap(lat, lng, zoom) {
    mapEl.classList.remove('hidden');
    if (!map) {
      map = L.map(mapEl).setView([lat, lng], zoom || 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap katkıda bulunanlar',
        maxZoom: 19,
      }).addTo(map);
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function () {
        var pos = marker.getLatLng();
        latEl.value = pos.lat.toFixed(6);
        lngEl.value = pos.lng.toFixed(6);
      });
    } else {
      map.setView([lat, lng], zoom || 15);
      marker.setLatLng([lat, lng]);
      setTimeout(function () { map.invalidateSize(); }, 50);
    }
    latEl.value = lat.toFixed(6);
    lngEl.value = lng.toFixed(6);
  }

  function geocode(query, onFound, onNotFound) {
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=tr&q=' + encodeURIComponent(query))
      .then(function (r) { return r.json(); })
      .then(function (results) {
        if (results && results.length) {
          onFound(parseFloat(results[0].lat), parseFloat(results[0].lon));
        } else if (onNotFound) {
          onNotFound();
        }
      })
      .catch(function () {
        setStatus('Konum servisi şu an yanıt vermedi, lütfen birazdan tekrar deneyin.', 'text-red-600');
      });
  }

  btn.addEventListener('click', function () {
    var address = addressEl.value.trim();
    var district = districtEl.value.trim();
    var city = cityEl.options[cityEl.selectedIndex] ? cityEl.options[cityEl.selectedIndex].text : '';

    if (!address && !district && !city) {
      setStatus('Önce şehir/ilçe/adres bilgisini girin.', 'text-amber-600');
      return;
    }

    setStatus('Konum aranıyor...', 'text-gray-500');

    var fullQuery = [address, district, city, 'Türkiye'].filter(Boolean).join(', ');

    geocode(fullQuery, function (lat, lng) {
      setStatus('✓ Konum bulundu - pin yanlış yerdeyse sürükleyerek düzeltebilirsiniz.', 'text-green-700');
      showMap(lat, lng, 16);
    }, function () {
      var fallbackQuery = [district, city, 'Türkiye'].filter(Boolean).join(', ');
      geocode(fallbackQuery, function (lat, lng) {
        setStatus('Tam adres bulunamadı, haritayı ' + (district || city) + ' bölgesine ortaladık - pini elle doğru yere sürükleyin.', 'text-amber-600');
        showMap(lat, lng, 13);
      }, function () {
        setStatus('Konum bulunamadı, lütfen adresi kontrol edin veya haritayı açıp pini elle yerleştirin.', 'text-red-600');
        showMap(39.9, 32.85, 6);
      });
    });
  });

  var existingLat = parseFloat(latEl.value);
  var existingLng = parseFloat(lngEl.value);
  if (!isNaN(existingLat) && !isNaN(existingLng)) {
    showMap(existingLat, existingLng, 15);
  }
});
</script>
@endsection
