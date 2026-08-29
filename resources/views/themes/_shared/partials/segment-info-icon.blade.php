{{--
  16 Temmuz 2026: fiyat segmentlerinin (Ekonomik/Standart/Premium/Ultra Premium)
  hangi fiyat araligini temsil ettigini gosteren, kurum TURUNE gore degisen
  bilgi pop-up'i. Admin kategori esiklerini degistirdiginde bu icerik otomatik
  guncellenir (canli veri, sabit metin degildir).

  Iki kullanim modu var:
  1) Facility sayfasi/paneli: tek bir kategori bilinir, tabloyu direkt gosterir.
     @include(..., ['categories' => [$facility->category], 'id' => '...'])
  2) Filtre: kurum turu henuz secilmemis olabilir, birden fazla kategori
     bolum kapsaminda olabilir. $categorySelectName ile o secim kutusunun
     "name" ozniteligi verilirse, ikon o an secili kategoriye gore ilgili
     tabloyu JS ile (sayfa yenilenmeden) gosterir; hicbir kategori
     secilmemisse "once kurum turunu secin" uyarisi verir.
     @include(..., ['categories' => $categories, 'id' => '...', 'categorySelectName' => 'category'])
--}}
@php
  $segmentIconId = $id ?? 'segment-info-'.uniqid();
  $segmentCategories = collect($categories ?? [])->filter();
  // 16 Temmuz 2026: facility sayfasi/paneli bu partial'i categorySelectName
  // GECMEDEN kullaniyor (tek kategori bilinir, secim kutusu yok) - asagida
  // bu degiskene ?? olmadan dogrudan erisen @if'ler tanimsiz degisken
  // hatasiyla TUM kurum sayfalarini/panelini kirmisti (16 Temmuz 2026,
  // production log'unda tekrar eden "Undefined variable $categorySelectName"
  // hatasi). Varsayilan olarak null atanip garanti tanimli hale getirildi.
  $categorySelectName = $categorySelectName ?? null;
@endphp
@if($segmentCategories->isNotEmpty())
  {{-- 16 Temmuz 2026: renkler burada bilerek satir-ici (inline) style ile
       veriliyor - Tailwind CSS bu proje derleme zamaninda (npm run build)
       kullanilan class'lari tarayip pakete koyuyor; "yellow" tonlari baska
       hicbir yerde kullanilmadigi icin derlenmis CSS'te hic yoktu, bg-yellow-400
       gibi class'lar sessizce hicbir sey yapmiyordu. Inline style, CSS
       paketinden bagimsiz calisir, her zaman garanti gorunur. --}}
  <span class="relative inline-block align-middle" style="margin-left:10px;" data-segment-select-name="{{ $categorySelectName ?? '' }}">
    <button type="button" onclick="toggleSegmentInfo(this, '{{ $segmentIconId }}')" aria-label="Fiyat segmentleri hakkında bilgi"
      style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:9999px;background:#facc15;color:#1f2937;font-size:13px;font-weight:900;box-shadow:0 1px 3px rgba(0,0,0,.3);border:2px solid #fde047;cursor:pointer;vertical-align:middle;">?</button>

    {{-- 29 Agustos 2026: kullanicinin bildirdigi gercek hata - kutu bazi
         durumlarda (ör. admin "Panelde Gör" ile baska bir kullanici adina
         goruntulerken) ikonun yaninda degil, sayfanin sol ust kosesinde
         yarim kirpilmis halde cikiyordu. Kok neden: kutu, CSS ile en yakin
         "relative" ataya (bu span'a) gore konumlaniyordu (position:absolute)
         - ama bu zincir cok katmanli (label > span > span) ve bazi gercek
         tarayici/DOM durumlarinda (ör. impersonasyon banner'inin sticky+z-50
         olmasi gibi baska bir "positioned"/stacking-context etkisiyle)
         guvenilmez cikti. Artik JS ile ACILDIGI ANDA butonun gercek ekran
         konumuna (getBoundingClientRect) gore position:fixed olarak
         KONUMLANDIRILIYOR - hangi atanin ne CSS'e sahip oldugundan tamamen
         bagimsiz, garanti dogru yerde acilir. --}}
    <div id="{{ $segmentIconId }}" class="hidden fixed z-50 w-72 sm:w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-lg border border-gray-200 p-4 text-left">
      <div class="flex items-center justify-between mb-2">
        <div class="text-sm font-black text-gray-900">Fiyat segmentleri</div>
        <button type="button" onclick="document.getElementById('{{ $segmentIconId }}').classList.add('hidden')" class="text-gray-400 hover:text-gray-700 text-lg leading-none">&times;</button>
      </div>

      @if($categorySelectName)
        <div class="segment-select-prompt text-xs text-gray-500">Önce yukarıdan bir kurum türü seçin.</div>
      @endif

      @foreach($segmentCategories as $category)
        @php $t = $category->priceTierThresholds(); @endphp
        <div class="segment-category-block mb-3 last:mb-0" data-category-slug="{{ $category->slug }}" @if($categorySelectName) style="display:none" @endif>
          @if(! $categorySelectName && $segmentCategories->count() > 1)
            <div class="text-xs font-bold text-gray-500 mb-1">{{ $category->name }}</div>
          @endif
          <ul class="text-xs text-gray-700 space-y-1">
            <li>🟢 Ekonomik: <strong>{{ number_format($t['standart_min'], 0, ',', '.') }}₺'ye kadar</strong></li>
            <li>🔵 Standart: <strong>{{ number_format($t['standart_min'], 0, ',', '.') }}₺ - {{ number_format($t['premium_min'], 0, ',', '.') }}₺</strong></li>
            <li>🟣 Premium: <strong>{{ number_format($t['premium_min'], 0, ',', '.') }}₺ - {{ number_format($t['ultra_min'], 0, ',', '.') }}₺</strong></li>
            <li>🟡 Ultra Premium: <strong>{{ number_format($t['ultra_min'], 0, ',', '.') }}₺ ve üzeri</strong></li>
          </ul>
        </div>
      @endforeach
    </div>
  </span>

  <script>
    if (!window.toggleSegmentInfo) {
      window.toggleSegmentInfo = function (trigger, id) {
        document.querySelectorAll('[id^="segment-info-"]').forEach(function (el) {
          if (el.id !== id) el.classList.add('hidden');
        });
        var el = document.getElementById(id);
        if (!el) return;
        var opening = el.classList.contains('hidden');
        el.classList.toggle('hidden');

        if (opening) {
          var margin = 16;
          var rect = trigger.getBoundingClientRect();
          var width = el.offsetWidth || 288;
          var left = Math.min(rect.right - width, window.innerWidth - width - margin);
          left = Math.max(left, margin);
          var top = rect.bottom + 6;
          var maxTop = window.innerHeight - (el.offsetHeight || 0) - margin;
          if (maxTop > margin) top = Math.min(top, maxTop);
          el.style.left = left + 'px';
          el.style.top = top + 'px';

          var wrapper = el.closest('[data-segment-select-name]');
          var selectName = wrapper ? wrapper.getAttribute('data-segment-select-name') : '';
          if (selectName) {
            var select = document.querySelector('[name="' + selectName + '"]');
            var slug = select ? select.value : '';
            var prompt = el.querySelector('.segment-select-prompt');
            var blocks = el.querySelectorAll('.segment-category-block');
            var matched = false;
            blocks.forEach(function (block) {
              var show = slug && block.getAttribute('data-category-slug') === slug;
              block.style.display = show ? '' : 'none';
              if (show) matched = true;
            });
            if (prompt) prompt.style.display = matched ? 'none' : '';
          }
        }
      };
      var closeAllSegmentInfo = function () {
        document.querySelectorAll('[id^="segment-info-"]').forEach(function (el) { el.classList.add('hidden'); });
      };
      document.addEventListener('click', function (e) {
        if (!e.target.closest('[onclick^="toggleSegmentInfo"]') && !e.target.closest('[id^="segment-info-"]')) {
          closeAllSegmentInfo();
        }
      });
      // 29 Agustos 2026: bkz. yukaridaki yorum - kutu artik position:fixed
      // (butona gore JS ile hesaplanan sabit koordinat), yani sayfa
      // kaydirilirsa butonla birlikte hareket ETMEZ. Kaydirma/pencere
      // boyutu degisince acik kutuyu kapatmak, yanlis yerde asili kalmasindan
      // daha güvenli ve basit.
      window.addEventListener('scroll', closeAllSegmentInfo, true);
      window.addEventListener('resize', closeAllSegmentInfo);
    }
  </script>
@endif
