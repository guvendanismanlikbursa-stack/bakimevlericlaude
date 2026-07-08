<script>
document.querySelectorAll('.js-location-filter').forEach((form) => {
  const map = JSON.parse(form.dataset.districtMap || '{}');
  const city = form.querySelector('.js-city');
  const district = form.querySelector('.js-district');
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

  // Sonuc listesi zaten bu sayfadaysa (Kurumları Bul), her secim degisiminde
  // formu otomatik gonderip listeyi/sayiyi taze veriyle yeniden ciziyoruz;
  // "Filtrele" butonuna basmaya gerek kalmiyor.
  if (form.dataset.autoSubmit) {
    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => form.submit());
    });
  }

  // Sonuc listesi bu sayfada degilse (ana sayfa hero filtresi gibi), formu
  // hemen gondermek yerine kac kurumun eslestigini canli gosteriyoruz; kesin
  // liste "Kurumları listele" ile acilir.
  const countUrl = form.dataset.countUrl;
  const countTarget = form.querySelector('.js-live-count');
  if (countUrl && countTarget && !form.dataset.autoSubmit) {
    let requestId = 0;
    const updateCount = () => {
      const thisRequest = ++requestId;
      const params = new URLSearchParams(new FormData(form));
      countTarget.textContent = 'Kurum sayılıyor…';
      fetch(countUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((response) => response.json())
        .then((data) => {
          if (thisRequest !== requestId) return;
          countTarget.textContent = data.count + ' kurum bulundu';
        })
        .catch(() => { if (thisRequest === requestId) countTarget.textContent = ''; });
    };
    form.querySelectorAll('select').forEach((select) => select.addEventListener('change', updateCount));
    updateCount();
  }
});
</script>
