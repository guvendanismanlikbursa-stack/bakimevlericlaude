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
});
</script>
