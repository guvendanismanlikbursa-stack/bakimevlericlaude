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

    const runFilter = () => {
      if (!resultsEl) return;
      if (controller) controller.abort();
      hideSpinner();
      controller = new AbortController();
      spinnerTimer = setTimeout(showSpinner, 150);
      const params = new URLSearchParams(new FormData(form));
      const url = (form.getAttribute('action') || window.location.pathname) + '?' + params.toString();

      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
        .then((r) => r.json())
        .then((data) => {
          hideSpinner();
          resultsEl.innerHTML = data.html;
          if (countEl && typeof data.count !== 'undefined') countEl.textContent = Number(data.count).toLocaleString('tr-TR');
          window.history.replaceState(null, '', url);
          if (window.paintEngagementToggles) window.paintEngagementToggles();
        })
        .catch((err) => { if (err.name !== 'AbortError') { hideSpinner(); } });
    };

    form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => {
      input.addEventListener('input', runFilter);
    });
    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', runFilter);
    });
    form.addEventListener('submit', (event) => { event.preventDefault(); runFilter(); });
  }
});
</script>
