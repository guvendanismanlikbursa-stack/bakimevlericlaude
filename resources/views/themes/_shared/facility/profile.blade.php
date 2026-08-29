@extends('layouts.brand')

@section('content')
@php
  $selectedServices = collect($facility->services ?? []);
  $imageCount = $facility->images->count();
  $remainingImages = max(0, 10 - $imageCount);
@endphp
@include('themes._shared.partials.facility-panel-header', ['title' => 'Kurum Bilgilerimi Düzenle', 'subtitle' => $serviceSection ? $serviceSection['title'].' bölümünde hizmet veriyorsunuz. Ana sayfa filtreleri ve kuruma özel detay alanları bu bölüme göre hazırlanır.' : null])

<div class="max-w-4xl mx-auto px-4 py-10">
  @if($errors->any())
    <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
      <div class="font-black mb-1">Lütfen alanları kontrol edin.</div>
      <ul class="list-disc pl-5 space-y-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <div class="text-sm font-semibold text-gray-500">Profil kalite puanı</div>
        <div class="text-3xl font-black text-gray-950 mt-1">{{ $profileQuality['score'] }}/100</div>
        <p class="text-sm text-gray-500 mt-1">Tam profil daha fazla ziyaretçi güveni, daha iyi teklif dönüşü ve daha güçlü SEO sinyali verir.</p>
      </div>
      <div class="w-full md:w-56">
        <div class="h-3 rounded-full bg-gray-100 overflow-hidden"><div class="h-full bg-primary" style="width: {{ $profileQuality['score'] }}%"></div></div>
        <div class="text-xs text-gray-400 mt-2">{{ $profileQuality['completed'] }}/{{ $profileQuality['total'] }} alan tamamlandı</div>
      </div>
    </div>
    @if($profileQuality['missing'])
      <div class="mt-4 flex flex-wrap gap-2">
        @foreach(array_slice($profileQuality['missing'], 0, 6) as $missing)
          <span class="rounded-full bg-amber-50 text-amber-700 px-3 py-1 text-xs font-semibold">{{ $missing }}</span>
        @endforeach
      </div>
    @endif
  </div>

  <form method="POST" action="{{ brand_route('facility.profile.update') }}" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 mb-8">
    @csrf @method('PUT')
    <div class="md:col-span-2">
      <label for="profile-name" class="text-sm font-medium">Kurum Adı</label>
      <input type="text" id="profile-name" name="name" value="{{ old('name', $facility->name) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label for="profile-city" class="text-sm font-medium">Şehir</label>
      <select id="profile-city" name="city_id" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
        @foreach($cities as $city)<option value="{{ $city->id }}" @selected(old('city_id', $facility->city_id) == $city->id)>{{ $city->name }}</option>@endforeach
      </select>
    </div>
    <div>
      <label for="profile-district" class="text-sm font-medium">İlçe</label>
      <input type="text" id="profile-district" name="district" value="{{ old('district', $facility->district) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label for="profile-phone" class="text-sm font-medium">Telefon</label>
      <input type="text" id="profile-phone" name="phone" value="{{ old('phone', $facility->phone) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label for="profile-capacity" class="text-sm font-medium">Kapasite</label>
      <input type="number" id="profile-capacity" name="capacity" value="{{ old('capacity', $facility->capacity) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div class="md:col-span-2">
      <label for="profile-address" class="text-sm font-medium">Adres</label>
      <input type="text" id="profile-address" name="address" value="{{ old('address', $facility->address) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div class="md:col-span-2">
      <label for="profile-description" class="text-sm font-medium">Açıklama</label>
      <textarea id="profile-description" name="description" rows="4" class="border rounded-lg px-3 py-2 w-full mt-1">{{ old('description', $facility->description) }}</textarea>
    </div>
    <div>
      <label for="profile-price-min" class="text-sm font-medium">Min Fiyat <span class="align-middle">@include('themes._shared.partials.segment-info-icon', ['categories' => [$facility->category], 'id' => 'segment-info-facility-profile'])</span></label>
      <input type="number" step="0.01" id="profile-price-min" name="price_min" value="{{ old('price_min', $facility->price_min) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
      <p class="text-xs text-gray-400 mt-1">Fiyat aralığınız hangi segment(ler)e girdiğini görmek için yukarıdaki ? işaretine tıklayın.</p>
    </div>
    <div>
      <label for="profile-price-max" class="text-sm font-medium">Maks Fiyat</label>
      <input type="number" step="0.01" id="profile-price-max" name="price_max" value="{{ old('price_max', $facility->price_max) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>

    @if($serviceSection)
      <div class="md:col-span-2 rounded-xl border border-gray-100 bg-gray-50 p-4">
        <div class="flex items-center gap-2 mb-2">
          @include('themes._shared.partials.section-icon', ['section' => $serviceSection, 'class' => 'w-5 h-5'])
          <label class="text-sm font-black">Ana sayfa filtrelerinde görünen {{ $serviceSection['title'] }} özellikleri</label>
        </div>
        <p class="text-xs text-gray-500 mb-3">Burada seçilen özellikler ziyaretçinin ana sayfa ve kurum listesi filtresinde kurumunuzu bulmasını sağlar.</p>
        <div class="grid grid-cols-2 gap-2">
          @foreach($serviceSection['features'] as $feature)
            <label class="flex items-center gap-2 text-sm bg-white border rounded-lg px-3 py-2">
              <input type="checkbox" name="services[]" value="{{ $feature }}" @checked($selectedServices->contains($feature))>
              <span>{{ $feature }}</span>
            </label>
          @endforeach
        </div>
      </div>
    @endif

    @if(!empty($sectionDetailFields))
      <div class="md:col-span-2 rounded-xl border border-gray-100 bg-white p-4">
        <div class="text-sm font-black text-gray-950 mb-1">Kuruma özel {{ $serviceSection['title'] }} detayları</div>
        <p class="text-xs text-gray-500 mb-4">Bu alanlar kurum detay sayfasında ziyaretçiye daha net bilgi vermek ve admin tarafından denetlenebilir profil oluşturmak içindir.</p>
        <div class="grid md:grid-cols-2 gap-3">
          @foreach($sectionDetailFields as $field)
            <label class="block">
              <span class="text-sm font-medium">{{ $field['label'] }}</span>
              <input type="text" name="section_details[{{ $field['key'] }}]" value="{{ old('section_details.'.$field['key'], $sectionDetails[$field['key']] ?? '') }}" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="Kurumunuza uygun bilgi girin">
            </label>
          @endforeach
        </div>
      </div>
    @endif

    <div class="md:col-span-2">
      <label for="profile-services-raw" class="text-sm font-medium">Ek hizmetler (virgülle ayırın)</label>
      <input type="text" id="profile-services-raw" name="services_raw" value="{{ old('services_raw', $selectedServices->diff($serviceSection['features'] ?? [])->implode(', ')) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div class="md:col-span-2">
      <button class="bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold">Kaydet</button>
    </div>
  </form>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
      <div>
        <h2 class="font-bold">Kurum Galerisi</h2>
        <p class="text-sm text-gray-500">En fazla 10 görsel eklenebilir. Şu an {{ $imageCount }}/10 görsel yüklü.</p>
      </div>
      <span class="text-xs font-semibold rounded-full px-3 py-1 {{ $remainingImages > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">Kalan hak: {{ $remainingImages }}</span>
    </div>

    <div id="ps-gallery-facility-profile" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-4">
      {{-- 19 Agustos 2026: kullanicinin talebi - hangi gorselin ANA (kapak)
           gorsel oldugu buradan secilebilir (bkz. Facility::primaryImage()). --}}
      @foreach($facility->images->take(10) as $img)
        <div class="relative group">
          <a href="{{ facility_asset($img->path) }}" data-pswp-width="1600" data-pswp-height="1200" target="_blank" rel="noopener">
            <img src="{{ facility_asset($img->path) }}" class="rounded-lg h-28 w-full object-cover border-2 {{ $img->is_primary ? 'border-amber-400' : 'border-gray-100' }} cursor-zoom-in hover:opacity-90 transition" alt="{{ $facility->name }} görseli">
          </a>
          @if($img->is_primary)
            <span class="absolute bottom-1 left-1 bg-amber-400 text-amber-950 text-[10px] font-black px-1.5 py-0.5 rounded">★ Ana Görsel</span>
          @else
            <form method="POST" action="{{ brand_route('facility.profile.image.set-primary', $img) }}" class="absolute bottom-1 left-1">
              @csrf
              <button class="bg-white/90 text-gray-700 text-[10px] font-semibold px-1.5 py-0.5 rounded hover:bg-white">Ana Görsel Yap</button>
            </form>
          @endif
          <form method="POST" action="{{ brand_route('facility.profile.image.destroy', $img) }}" class="absolute top-1 right-1">
            @csrf @method('DELETE')
            <button class="bg-white/90 text-red-600 text-xs px-2 py-0.5 rounded">Sil</button>
          </form>
        </div>
      @endforeach
      @for($i = $imageCount; $i < 10; $i++)
        <div class="h-28 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-center px-2 text-xs text-gray-400">Görsel alanı<br>{{ $i + 1 }}/10</div>
      @endfor
    </div>
    @include('themes._shared.partials.image-lightbox')
    <script>document.addEventListener('DOMContentLoaded', function () { initFacilityGallery('ps-gallery-facility-profile'); });</script>

    @if($remainingImages > 0)
      <form method="POST" action="{{ brand_route('facility.profile.image.store') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row">
        @csrf
        <input type="file" name="images[]" multiple accept="image/*" required class="border rounded-lg px-3 py-2 text-sm flex-1">
        <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Görsel Ekle</button>
      </form>
      <p class="text-xs text-gray-400 mt-2">Tek seferde kalan hak kadar JPG, PNG veya WEBP görsel yükleyebilirsiniz.</p>
    @else
      <div class="rounded-lg bg-gray-50 border border-gray-100 p-3 text-sm text-gray-500">10 görsel limiti doldu. Yeni görsel eklemek için önce mevcut görsellerden birini silin.</div>
    @endif
  </div>

  {{-- 19 Agustos 2026: kullanicinin talebi - "kurum panellerine yemek
       listesi bolumu, kurum yetkilisi haftalik yemek listesinin gorselini
       yuklesin, kullanicilar goruntuleyip buyutebilsin". Galeriden ayri,
       TEK bir gorsel - her yeni yukleme eskisinin yerine gecer. --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="mb-4">
      <h2 class="font-bold">Yemek Listesi</h2>
      <p class="text-sm text-gray-500">Haftalık yemek listenizin fotoğrafını yükleyin, ziyaretçiler kurum sayfanızda görüp büyüterek inceleyebilir.</p>
    </div>

    @if($facility->menu_image_path)
      <div id="ps-menu-facility-profile" class="mb-4">
        <a href="{{ facility_asset($facility->menu_image_path) }}" data-pswp-width="1600" data-pswp-height="2000" target="_blank" rel="noopener" class="inline-block">
          <img src="{{ facility_asset($facility->menu_image_path) }}" class="rounded-lg h-40 object-cover border border-gray-100 cursor-zoom-in hover:opacity-90 transition" alt="{{ $facility->name }} yemek listesi">
        </a>
      </div>
      @include('themes._shared.partials.image-lightbox')
      <script>document.addEventListener('DOMContentLoaded', function () { initFacilityGallery('ps-menu-facility-profile'); });</script>
      <p class="text-xs text-gray-400 mb-3">Son güncelleme: {{ $facility->menu_image_updated_at?->diffForHumans() }}</p>
      <form method="POST" action="{{ brand_route('facility.profile.menu-image.destroy') }}" onsubmit="return confirm('Yemek listesi görselini kaldırmak istediğinize emin misiniz?');" class="inline">
        @csrf @method('DELETE')
        <button class="text-red-600 text-xs font-semibold">Kaldır</button>
      </form>
    @else
      <div class="rounded-lg bg-gray-50 border border-dashed border-gray-300 p-4 text-sm text-gray-400 mb-4">Henüz yemek listesi görseli eklenmedi.</div>
    @endif

    <form method="POST" action="{{ brand_route('facility.profile.menu-image.store') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row mt-3">
      @csrf
      <input type="file" name="menu_image" accept="image/*" required class="border rounded-lg px-3 py-2 text-sm flex-1">
      <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">{{ $facility->menu_image_path ? 'Yemek Listesini Güncelle' : 'Yemek Listesi Ekle' }}</button>
    </form>
    @error('menu_image')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
  </div>

  {{-- 27 Agustos 2026: kullanicinin talebi - anlasmali (is_broker_managed)
       kurum sahibi, admin'e ek olarak kendi panelinden de tanitim videosu
       yukleyip yonetebilsin. 27 Agustos (ikinci talep): alan anlasmasiz
       kurumlara da GORUNSUN (komisyonlu calismaya tesvik icin) ama pasif
       kalsin - yuklemeye calisinca sunucu tarafi (controller) ayni tesvik
       mesajini gosterir, form burada bilerek DEVRE DISI birakilmiyor (kilit
       ikonuyla "pasif" hissi veriliyor ama tiklanip denenebilir kalıyor). --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="mb-4">
      <h2 class="font-bold">
        Tanıtım Videosu
        @if(! $facility->is_broker_managed)
          <span class="text-xs font-normal text-amber-600">🔒 anlaşmalı kurumlara özel</span>
        @else
          <span class="text-xs font-normal text-gray-400">(anlaşmalı kurumlara özel)</span>
        @endif
      </h2>
      <p class="text-sm text-gray-500">En fazla 60 saniyelik bir video yükleyin, ziyaretçiler kurum sayfanızda görüntüleyebilir. Yüklenince otomatik sıkıştırılır, biraz zaman alabilir.</p>
    </div>

    @if(! $facility->is_broker_managed)
      <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 mb-4">
        🔒 Bu alan şu anda <strong>pasif</strong>. Kurumunuz <strong>anlaşmalı (komisyon usulü)</strong> statüye geçtiğinde otomatik olarak aktif hale gelecek ve video yükleyebileceksiniz. Detaylı bilgi için yöneticinizle (admin) iletişime geçin.
      </div>
    @endif

    <div @if(! $facility->is_broker_managed) class="opacity-50 grayscale pointer-events-none select-none" @endif>
      @if($facility->video_path)
        <video src="{{ facility_asset($facility->video_path) }}" controls class="w-full max-w-sm rounded-lg mb-3"></video>
        <p class="text-xs text-gray-400 mb-3">Son güncelleme: {{ $facility->video_updated_at?->diffForHumans() }}</p>
        <form method="POST" action="{{ brand_route('facility.profile.video.destroy') }}" onsubmit="return confirm('Tanıtım videosunu kaldırmak istediğinize emin misiniz?');" class="inline mb-3">
          @csrf @method('DELETE')
          <button class="text-red-600 text-xs font-semibold">Kaldır</button>
        </form>
        <p class="text-xs text-gray-500 mb-2">Yeni bir video yüklerseniz, bu videonun yerine geçer:</p>
      @else
        <div class="rounded-lg bg-gray-50 border border-dashed border-gray-300 p-4 text-sm text-gray-400 mb-4">Henüz tanıtım videosu eklenmedi.</div>
      @endif

      <form method="POST" action="{{ brand_route('facility.profile.video.store') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row mt-3">
        @csrf
        <input type="file" name="video" accept="video/*" required class="border rounded-lg px-3 py-2 text-sm flex-1">
        <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">{{ $facility->video_path ? 'Videoyu Güncelle' : 'Video Ekle' }}</button>
      </form>
    </div>
    @error('video')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
  </div>
</div>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
    <h2 class="text-lg font-black text-gray-950">Boş Yer Durumu</h2>
    <p class="text-sm text-gray-500 mt-1 mb-4">Ailelere kurum sayfanızda gösterilir, dilediğiniz zaman güncelleyebilirsiniz.</p>
    <form method="POST" action="{{ brand_route('facility.profile.vacancy.update') }}" class="grid sm:grid-cols-2 gap-4 max-w-lg">
      @csrf @method('PUT')
      @if($facility->usesGenderSplitVacancy())
        <div>
          <label for="vacancy-male" class="text-sm font-medium">Bay</label>
          <select id="vacancy-male" name="vacancy_male" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="" @selected($facility->vacancy_male === null)>Belirtilmedi</option>
            <option value="1" @selected($facility->vacancy_male === true)>Var</option>
            <option value="0" @selected($facility->vacancy_male === false)>Yok</option>
          </select>
        </div>
        <div>
          <label for="vacancy-female" class="text-sm font-medium">Bayan</label>
          <select id="vacancy-female" name="vacancy_female" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="" @selected($facility->vacancy_female === null)>Belirtilmedi</option>
            <option value="1" @selected($facility->vacancy_female === true)>Var</option>
            <option value="0" @selected($facility->vacancy_female === false)>Yok</option>
          </select>
        </div>
      @else
        <div>
          <label for="vacancy-general" class="text-sm font-medium">Boş Yer</label>
          <select id="vacancy-general" name="vacancy_general" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            <option value="" @selected($facility->vacancy_general === null)>Belirtilmedi</option>
            <option value="1" @selected($facility->vacancy_general === true)>Var</option>
            <option value="0" @selected($facility->vacancy_general === false)>Yok</option>
          </select>
        </div>
      @endif
      <div class="sm:col-span-2 flex items-center gap-3">
        <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Kaydet</button>
        @if($facility->vacancy_updated_at)
          <span class="text-xs text-gray-400">Son güncelleme: {{ $facility->vacancy_updated_at->diffForHumans() }}</span>
        @endif
      </div>
    </form>
  </div>

@include('themes._shared.partials.notification-preferences-form', [
    'notificationGroups' => $notificationGroups,
    'preferences' => $user->notification_preferences ?? [],
    'notificationFormAction' => brand_route('facility.profile.notifications.update'),
])

{{-- 19 Agustos 2026: kullanicinin talebi - KVKK "silme hakki". Bu form
     hesabi DOGRUDAN SILMEZ, sadece bir talep olusturur - gercek silme
     islemini bir admin onaylar (bkz. Facility\ProfileController::destroy()).
     DIKKAT: sadece BU yetkilinin kendi hesabi hedeflenir, kurumun kendisi
     veya varsa diger yetkili hesaplari etkilenmez. --}}
<div class="max-w-3xl mx-auto mt-6 bg-white p-6 rounded-xl shadow-sm border border-red-100">
  <p class="text-sm font-semibold text-red-700 mb-1">Hesabımı Sil</p>
  <p class="text-xs text-gray-500 mb-3">Kendi hesabınızın ve kişisel verilerinizin (ad, e-posta, telefon) silinmesini talep edebilirsiniz. Kurumunuzun profili veya varsa diğer yetkili hesapları bu talepten etkilenmez. Talebiniz ekibimiz tarafından incelenip kısa süre içinde işleme alınır.</p>
  <form method="POST" action="{{ brand_route('facility.profile.destroy') }}" onsubmit="return confirm('Hesabınızın silinmesini talep etmek istediğinize emin misiniz?');" class="flex flex-wrap gap-2 items-end">
    @csrf
    @method('DELETE')
    <div class="flex-1 min-w-[200px]">
      <label for="facility-delete-password" class="block text-xs font-semibold text-gray-600 mb-1">Şifreniz</label>
      <input type="password" id="facility-delete-password" name="password" required class="border rounded-lg px-3 py-2 w-full">
    </div>
    <button class="rounded-lg border border-red-200 text-red-700 px-4 py-2 text-sm font-semibold hover:bg-red-50">Silme talebi gönder</button>
  </form>
  @error('password')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
</div>
@endsection
