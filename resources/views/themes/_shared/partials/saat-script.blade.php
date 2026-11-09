{{-- 1 Kasım 2026: çalışma saati kutuları için "şu an açık/kapalı" (Türkiye saatiyle, tarayıcıda;
     önbellekli sayfada da doğru). Sonradan (filtre/sayfalama ile) eklenen kartları da işler. --}}
<script>
(function () {
  if (window.__saatScript) return; window.__saatScript = 1;
  function acikMi(saat, dakika) {
    if (saat === '24 saat açık') return true;
    var acik = false;
    (saat.match(/\d{1,2}:\d{2}–\d{1,2}:\d{2}/g) || []).forEach(function (a) {
      var p = a.split('–').map(function (x) { var s = x.split(':'); return +s[0] * 60 + +s[1]; });
      if (p[1] <= p[0]) p[1] += 1440;
      if (dakika >= p[0] && dakika < p[1]) acik = true;
    });
    return acik;
  }
  function isle() {
    var simdi;
    try { simdi = new Date(new Date().toLocaleString('en-US', { timeZone: 'Europe/Istanbul' })); } catch (e) { simdi = new Date(); }
    var bugun = (simdi.getDay() + 6) % 7, dakika = simdi.getHours() * 60 + simdi.getMinutes();
    document.querySelectorAll('.js-kart-saat:not([data-islendi])').forEach(function (k) {
      k.setAttribute('data-islendi', '1');
      try {
        var bugunSaat = JSON.parse(k.getAttribute('data-saatler'))[bugun] || '';
        var acik = acikMi(bugunSaat, dakika);
        var metin = k.querySelector('.js-kart-saat-metin');
        if (metin && bugunSaat) metin.textContent = bugunSaat === 'Kapalı' ? 'Bugün kapalı' : bugunSaat;
        var e = k.querySelector('.js-kart-saat-durum');
        e.textContent = acik ? '● Şu an açık' : '● Şu an kapalı';
        e.style.background = acik ? '#16a34a' : '#fee2e2';
        e.style.color = acik ? '#ffffff' : '#991b1b';
        e.style.display = 'inline-block';
      } catch (e) {}
    });
    document.querySelectorAll('.js-saat-kutu:not([data-islendi])').forEach(function (k) {
      k.setAttribute('data-islendi', '1');
      try {
        var satir = k.querySelector('.js-saat-satir[data-gun="' + bugun + '"]');
        if (!satir) return;
        satir.style.background = '#f0fdfa';
        satir.cells[0].textContent += ' (bugün)';
        var acik = acikMi(satir.getAttribute('data-saat'), dakika);
        var e = k.querySelector('.js-saat-durum');
        e.textContent = acik ? '● Şu an açık' : '● Şu an kapalı';
        e.style.background = acik ? '#dcfce7' : '#fee2e2';
        e.style.color = acik ? '#166534' : '#991b1b';
        e.style.display = 'inline-block';
      } catch (e) {}
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', isle); else isle();
  var zaman;
  new MutationObserver(function () { clearTimeout(zaman); zaman = setTimeout(isle, 150); }).observe(document.documentElement, { childList: true, subtree: true });
})();
</script>
