<script>
(function(){
  const brand = @json(current_brand()['slug']);
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  const key = (mode) => `${brand}:${mode}`;
  const read = (mode) => JSON.parse(localStorage.getItem(key(mode)) || '[]').map(Number);
  const write = (mode, ids) => localStorage.setItem(key(mode), JSON.stringify([...new Set(ids.map(Number))]));
  const label = (mode, active) => mode === 'compare' ? (active ? 'Eklendi' : 'Karşılaştır') : (active ? 'Favoride' : 'Favori');
  const favoriteCountUrlTemplate = @json(brand_route('facilities.favorite-count', ['slug' => '__SLUG__']));
  const bumpFavoriteCount = (slug, action) => {
    if (!slug || !csrfToken) return;
    fetch(favoriteCountUrlTemplate.replace('__SLUG__', slug), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ action })
    }).catch(() => {});
  };
  const paint = () => {
    document.querySelectorAll('.js-engagement-toggle').forEach((button) => {
      const mode = button.dataset.mode;
      const id = Number(button.dataset.id);
      const active = read(mode).includes(id);
      button.textContent = label(mode, active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      if (active) button.classList.add('ring-2','ring-offset-1'); else button.classList.remove('ring-2','ring-offset-1');
    });
  };
  document.addEventListener('click', (event) => {
    const button = event.target.closest('.js-engagement-toggle');
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();
    const mode = button.dataset.mode;
    const id = Number(button.dataset.id);
    const slug = button.dataset.slug;
    const ids = read(mode);
    if (ids.includes(id)) {
      write(mode, ids.filter(item => Number(item) !== id));
    } else {
      const limit = mode === 'compare' ? 4 : 30;
      write(mode, [id, ...ids].slice(0, limit));
      if (mode === 'favorites') bumpFavoriteCount(slug, 'add');
    }
    paint();
  });
  paint();
})();
</script>
