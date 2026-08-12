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
    <button type="button" onclick="toggleSegmentInfo('{{ $segmentIconId }}')" aria-label="Fiyat segmentleri hakkında bilgi"
      style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:9999px;background:#facc15;color:#1f2937;font-size:13px;font-weight:900;box-shadow:0 1px 3px rgba(0,0,0,.3);border:2px solid #fde047;cursor:pointer;vertical-align:middle;">?</button>

    <div id="{{ $segmentIconId }}" class="hidden absolute z-50 right-0 sm:left-0 sm:right-auto top-7 w-72 sm:w-80 bg-white rounded-xl shadow-lg border border-gray-200 p-4 text-left">
      <div class="flex items-center justify-between mb-2">
        <div class="text-sm font-black text-gray-900">Fiyat segmentleri</div>
        <button type="button" onclick="toggleSegmentInfo('{{ $segmentIconId }}')" class="text-gray-400 hover:text-gray-700 text-lg leading-none">&times;</button>
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
      window.toggleSegmentInfo = function (id) {
        document.querySelectorAll('[id^="segment-info-"]').forEach(function (el) {
          if (el.id !== id) el.classList.add('hidden');
        });
        var el = document.getElementById(id);
        if (!el) return;
        var opening = el.classList.contains('hidden');
        el.classList.toggle('hidden');

        if (opening) {
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
      document.addEventListener('click', function (e) {
        if (!e.target.closest('[onclick^="toggleSegmentInfo"]') && !e.target.closest('[id^="segment-info-"]')) {
          document.querySelectorAll('[id^="segment-info-"]').forEach(function (el) { el.classList.add('hidden'); });
        }
      });
    }
  </script>
@endif
