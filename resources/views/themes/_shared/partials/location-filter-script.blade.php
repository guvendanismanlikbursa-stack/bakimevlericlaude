<style>@keyframes js-filter-spin { to { transform: rotate(360deg); } }</style>
<script>
document.querySelectorAll('.js-location-filter, .js-instant-filter').forEach((form) => {
  // Il/ilce secimi olmayan basit arama formlarinda (orn. admin
  // Aileler/Kurum Yetkilileri) bu iki alan bulunmaz - yoklarsa asagidaki
  // il/ilce doldurma mantigi atlanir, hata firlatip forEach'i durdurmaz.
  const map = JSON.parse(form.dataset.districtMap || '{}');
  const city = form.querySelector('.js-city');
  const district = form.querySelector('.js-district');
  if (city && district) {
    const fillDistricts = () => {
      const selected = district.dataset.selected || '';
      const values = map[city.value] || [];
      district.innerHTML = values.length ? '<option value="">Tüm ilçeler</option>' : '<option value="">Önce il seçin</option>';
      values.forEach((name) => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        option.selected = selected === name;
        district.appendChild(option);
      });
      district.disabled = values.length === 0;
    };
    city.addEventListener('change', () => { district.dataset.selected = ''; fillDistricts(); });
    fillDistricts();
  }

  // 12 Agustos 2026: kullanicinin talebi - TUM filtreler (metin kutusu +
  // secim kutulari) sayfa yenilenmeden, sonuc her zaman ayni tanidik
  // alanda (buyuk kurum kartlari + sayfalama) aninda guncellenir. Metin
  // kutusunda HER TUS VURUSUNDA (debounce yok, kullanicinin acik istegi:
  // "her harfte aninda filtrele"), secim kutularinda deger degisince
  // tetiklenir. Yarisan istekleri (eski cevap yeniyi ezmesin diye)
  // AbortController ile iptal ediyoruz. Onceki "kucuk/ayri canli onizleme
  // kutusu" tasarimi kullanicidan gelen acik geri bildirimle reddedildi -
  // bu yuzden sonuc HEP ayni buyuk kart alaninda goruntuleniyor, sadece
  // tam sayfa yenilenmesi AJAX'a donusuyor.
  if (form.dataset.instantFilter) {
    const resultsEl = document.getElementById(form.dataset.resultsTarget || '');
    const countEl = document.getElementById(form.dataset.countTarget || '');
    let controller = null;

    // 12 Agustos 2026: kullanicinin talebi - "her harfte aninda filtrele"
    // sirasinda ekranda hicbir geri bildirim yoktu, sonuc birden
    // degisiyordu. Yaniti gecikirse (150ms'den uzun surerse - hizli
    // yanitlarda titremeyi onlemek icin) sonuc alani hafifce solup kucuk
    // bir donen gosterge cikiyor, boylece "bir sey oluyor" hissi veriliyor.
    let spinnerTimer = null;
    let overlayEl = null;
    const showSpinner = () => {
      if (getComputedStyle(resultsEl).position === 'static') resultsEl.style.position = 'relative';
      resultsEl.style.transition = 'opacity .15s ease';
      resultsEl.style.opacity = '0.45';
      overlayEl = document.createElement('div');
      overlayEl.className = 'js-filter-spinner';
      overlayEl.style.cssText = 'position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:flex-start;justify-content:center;padding-top:3rem;pointer-events:none;';
      overlayEl.innerHTML = '<div style="width:2.25rem;height:2.25rem;border-radius:9999px;border:3px solid rgba(0,0,0,.12);border-top-color:{{ current_brand()["primary_color"] }};animation:js-filter-spin .7s linear infinite;"></div>';
      resultsEl.appendChild(overlayEl);
    };
    const hideSpinner = () => {
      clearTimeout(spinnerTimer);
      resultsEl.style.opacity = '';
      if (overlayEl) { overlayEl.remove(); overlayEl = null; }
    };

    // 25 Agustos 2026: kullanicinin bildirdigi gercek hata - "Ara"ya
    // basildiginda sonuc alani (bu form genelde bir hero/tanitim
    // bolumunun UZERINDE, sonuclar ekranin cok asagisinda sessizce
    // guncelleniyordu) sayfa hic kaymadigi icin kullaniciya "hicbir sey
    // olmadi/filtre calismiyor" gibi gorunuyordu - 0 sonuc donen bir arama
    // fark edilmeden kayboluyordu.
    // 26 Agustos 2026: kullanicinin bildirdigi ikinci hata - bir onceki
    // duzeltme select degisince de kaydiriyordu, ama kullanici henuz TUM
    // filtre alanlarini doldurmadan (ör. sadece il secip kategori/hizmet
    // secmeden) sayfa erkenden sonuclara atlıyordu. Kaydirma artik SADECE
    // "Ara/Filtrele/Bul" butonuna basildiginda (veya Enter'a basildiginda -
    // formun submit event'i) calisir; tek tek alan degistirmede (select
    // change, metin kutusunda yazarken) sonuc arka planda guncellenir ama
    // sayfa kaymaz - kullanici tum filtreleri kendi hizinda doldurabilir.
    const runFilter = (scrollToResults) => {
      if (!resultsEl) return;
      if (controller) controller.abort();
      hideSpinner();
      controller = new AbortController();
      spinnerTimer = setTimeout(showSpinner, 150);
      const params = new URLSearchParams(new FormData(form));
      const url = (form.getAttribute('action') || window.location.pathname) + '?' + params.toString();

      // 13 Agustos 2026: admin oturumu suresi dolmus/gecersizse AdminAuth
      // middleware'i AJAX istegini de sessizce giris sayfasina (HTML, 200)
      // yonlendiriyor - fetch bu yonlendirmeyi otomatik takip ettigi icin
      // eskiden r.json() sessizce patliyor, kullaniciya "filtre hic
      // calismiyor" gibi goruniyordu (hicbir hata da gozukmuyordu). Artik
      // JSON olmayan/basarisiz her yanitta tam sayfa navigasyonuna
      // dusuyoruz - boylece filtre HER ZAMAN gorunur sekilde uygulanir
      // (oturum dusmusse kullanici giris sayfasina yonlenir).
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
        .then((r) => {
          if (! r.ok) throw new Error('HTTP ' + r.status);
          const contentType = r.headers.get('content-type') || '';
          if (! contentType.includes('application/json')) throw new Error('JSON degil');
          return r.json();
        })
        .then((data) => {
          hideSpinner();
          resultsEl.innerHTML = data.html;
          if (countEl && typeof data.count !== 'undefined') countEl.textContent = Number(data.count).toLocaleString('tr-TR');
          window.history.replaceState(null, '', url);
          if (window.paintEngagementToggles) window.paintEngagementToggles();
          if (scrollToResults) resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch((err) => {
          if (err.name === 'AbortError') return;
          hideSpinner();
          window.location = url;
        });
    };

    form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => {
      input.addEventListener('input', () => runFilter(false));
    });
    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => runFilter(false));
    });
    form.addEventListener('submit', (event) => { event.preventDefault(); runFilter(true); });
  }
});
</script>
