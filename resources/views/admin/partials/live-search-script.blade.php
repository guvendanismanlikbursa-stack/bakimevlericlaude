{{-- 21 Ekim 2026: kullanıcının bildirdiği hata - Doktorlar ve Organizasyonlar
     sayfalarındaki "İsimle ara" kutusunda oninput="this.form.submit()" vardı;
     her tuş vuruşunda sayfa baştan yükleniyor, imleç kutudan çıkıyordu
     (Kurumlar sayfasında aynı hata 14 Ağustos 2026'da düzeltilmişti).
     Çözüm: yazarken sayfa yenilenmez; kısa bir bekleme sonrası aynı sayfa
     arka planda istenir, sadece liste (data-live-results) ve sonuç sayısı
     (data-live-count) yerinde değiştirilir. Arama kutusu hiç dokunulmadığı
     için odak ve imleç yerinde kalır. Enter ile normal gönderim de çalışır. --}}
<script>
(function () {
  document.querySelectorAll('input[data-live-search]').forEach(function (input) {
    var form = input.form;
    if (!form) return;
    var timer = null, controller = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var params = new URLSearchParams(new FormData(form));
        params.delete('page');
        var url = (form.getAttribute('action') || window.location.pathname) + '?' + params.toString();
        if (controller) controller.abort();
        controller = new AbortController();
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal, credentials: 'same-origin' })
          .then(function (r) { return r.text(); })
          .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            ['data-live-results', 'data-live-count'].forEach(function (attr) {
              var yeni = doc.querySelector('[' + attr + ']');
              var eski = document.querySelector('[' + attr + ']');
              if (yeni && eski) eski.innerHTML = yeni.innerHTML;
            });
            window.history.replaceState(null, '', url);
          })
          .catch(function () {});
      }, 300);
    });
  });
})();
</script>
