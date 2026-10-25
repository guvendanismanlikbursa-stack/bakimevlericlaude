{{-- 25 Ekim 2026: kullanıcının talebi - "Profesyonel siteyi çok daha güzel, hareketli ve dikkat
     çekici yapalım". Magic UI (MIT) efektlerinden esinlenen hareket katmanı; React yok, düz CSS/JS.
     Yalnızca body.hrk varken etkili (şimdilik admin önizlemesi: ?tasarim=hareketli).
     $parca: 'css' (head içinde) ya da 'js' (body sonunda).
     Hareket azaltma tercihi olan ziyaretçide tüm animasyonlar kapanır. --}}
@if($parca === 'css')
<style>
  @property --aci{syntax:'<angle>';inherits:false;initial-value:0deg}
  @keyframes hrk-akis{to{background-position:200% center}}
  @keyframes hrk-sur{0%{transform:translate(0,0) scale(1)}50%{transform:translate(8vw,-6vh) scale(1.18)}100%{transform:translate(-6vw,7vh) scale(.92)}}
  @keyframes hrk-kelime{to{opacity:1;filter:blur(0);transform:none}}
  @keyframes hrk-parilti{0%,55%{transform:translateX(-130%) skewX(-18deg)}100%{transform:translateX(130%) skewX(-18deg)}}
  @keyframes hrk-donus{to{--aci:360deg}}
  @keyframes hrk-yuz{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
  @keyframes hrk-kay{to{transform:translateX(-50%)}}
  @keyframes hrk-pop{0%{transform:scale(.4) rotate(-20deg);opacity:0}100%{transform:none;opacity:1}}
  @keyframes hrk-fare{0%{transform:translateY(0);opacity:1}70%{transform:translateY(12px);opacity:0}100%{opacity:0}}
  @keyframes hrk-don{to{transform:rotate(360deg)}}

  /* Kaydırma ilerleme çubuğu */
  .hrk .ilerleme{position:fixed;top:0;left:0;right:0;height:3px;z-index:95;transform-origin:0 50%;transform:scaleX(0);background:linear-gradient(90deg,var(--altin),var(--ana),#7dd3fc,var(--altin));background-size:200% auto;animation:hrk-akis 3s linear infinite;pointer-events:none}

  /* Giriş: dolaşan renk ışıkları + kelime kelime başlık + cam rozetler */
  .hrk .aurora{position:absolute;inset:-25%;z-index:1;pointer-events:none;mix-blend-mode:screen;opacity:.5;filter:blur(70px)}
  .hrk .aurora i{position:absolute;width:48vmax;height:48vmax;border-radius:50%;background:radial-gradient(circle,var(--ana),transparent 62%);left:5%;top:5%;animation:hrk-sur 22s ease-in-out infinite alternate}
  .hrk .aurora i:nth-child(2){background:radial-gradient(circle,var(--altin),transparent 62%);left:auto;right:0;top:20%;animation-duration:27s;animation-delay:-7s}
  .hrk .aurora i:nth-child(3){background:radial-gradient(circle,#7dd3fc,transparent 62%);left:35%;top:auto;bottom:0;animation-duration:33s;animation-delay:-12s;opacity:.7}
  .hrk .giris h1 .kelime{display:inline-block;opacity:0;filter:blur(12px);transform:translateY(.45em);animation:hrk-kelime 1s cubic-bezier(.2,.7,.2,1) forwards;animation-delay:calc(var(--i) * 75ms + .2s)}
  .hrk .giris h1 .vurgu{background:linear-gradient(90deg,#fff,#f5dcae,#fff);background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent;animation:hrk-kelime 1s cubic-bezier(.2,.7,.2,1) forwards,hrk-akis 5s linear infinite;animation-delay:calc(var(--i) * 75ms + .2s),0s}
  .hrk .guven > span{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-radius:999px;padding:8px 16px;animation:hrk-yuz 5s ease-in-out infinite}
  .hrk .guven > span:nth-child(2){animation-delay:-1.6s}
  .hrk .guven > span:nth-child(3){animation-delay:-3.2s}
  .hrk .asagi{position:absolute;left:50%;bottom:26px;z-index:3;transform:translateX(-50%);width:28px;height:44px;border:2px solid rgba(255,255,255,.7);border-radius:16px;display:none}
  .hrk .asagi span{position:absolute;left:50%;top:8px;width:4px;height:8px;margin-left:-2px;border-radius:2px;background:#fff;animation:hrk-fare 1.8s ease-in-out infinite}
  @media(min-width:900px){.hrk .asagi{display:block}}

  /* Düğmeler: parıltı + ana düğmede dönen ışık hüzmesi */
  .hrk .btn-dolu,.hrk .btn-wa,.hrk .btn-beyaz{position:relative;overflow:hidden;isolation:isolate}
  .hrk .btn-dolu::after,.hrk .btn-wa::after,.hrk .btn-beyaz::after{content:"";position:absolute;top:0;bottom:0;left:0;width:60%;z-index:-1;background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);transform:translateX(-130%) skewX(-18deg);animation:hrk-parilti 3.8s ease-in-out infinite;pointer-events:none}
  .hrk .btn-beyaz::after{background:linear-gradient(90deg,transparent,color-mix(in srgb,var(--ana) 22%,transparent),transparent)}
  .hrk .giris .btnler .btn:first-child::before,.hrk .cagri .btnler .btn:first-child::before{content:"";position:absolute;inset:0;border-radius:inherit;padding:2px;background:conic-gradient(from var(--aci),transparent 0 62%,rgba(255,255,255,.95) 80%,transparent 92%);-webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);-webkit-mask-composite:xor;mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);mask-composite:exclude;animation:hrk-donus 3s linear infinite;pointer-events:none}
  .hrk .cagri .btnler .btn-beyaz:first-child::before{background:conic-gradient(from var(--aci),transparent 0 62%,var(--ana) 80%,transparent 92%)}

  /* Bölüm etiketleri: akan renk */
  .hrk .bolum:not(.koyu-bolum) .etiket{background:linear-gradient(90deg,var(--ana),var(--altin),var(--ana));background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent;animation:hrk-akis 6s linear infinite}

  /* Bilgi/rakam kartı: akan üst çizgi */
  .hrk .bilgi-kart::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;z-index:3;background:linear-gradient(90deg,var(--ana),var(--altin),#7dd3fc,var(--ana));background-size:200% auto;animation:hrk-akis 4s linear infinite}
  .hrk .bilgi-kart .rakam{font-variant-numeric:tabular-nums}

  /* Kartlar: fareyle gezinen ışık */
  .hrk .deger,.hrk .hizmet,.hrk .yorum,.hrk .duyuru,.hrk .uye .resim{position:relative;overflow:hidden}
  .hrk .deger::before,.hrk .hizmet::before,.hrk .yorum::before,.hrk .duyuru::before{content:"";position:absolute;inset:0;z-index:0;pointer-events:none;opacity:0;transition:opacity .35s ease;background:radial-gradient(380px circle at var(--mx,50%) var(--my,50%),color-mix(in srgb,var(--ana) 16%,transparent),transparent 60%)}
  .hrk .deger:hover::before,.hrk .hizmet:hover::before,.hrk .yorum:hover::before,.hrk .duyuru:hover::before{opacity:1}
  .hrk .deger > *,.hrk .hizmet > *,.hrk .yorum > *,.hrk .duyuru > *{position:relative;z-index:1}
  .hrk .deger:hover,.hrk .duyuru:hover,.hrk .yorum:hover{border-color:color-mix(in srgb,var(--ana) 45%,var(--cizgi))}
  .hrk .deger .no{background:linear-gradient(135deg,var(--altin),var(--ana));-webkit-background-clip:text;background-clip:text;color:transparent}
  .js .hrk .hizmet.acik .tik{animation:hrk-pop .7s cubic-bezier(.3,1.6,.5,1) both;animation-delay:calc(var(--gecikme,0ms) + .25s)}

  /* Kolaj: hafif derinlik */
  .hrk .kolaj .b2{transition:transform .2s linear;will-change:transform}
  .hrk .kolaj::before{animation:hrk-yuz 7s ease-in-out infinite}

  /* Kayan galeri şeridi */
  .hrk .serit{overflow:hidden;-webkit-mask:linear-gradient(90deg,transparent,#000 7%,#000 93%,transparent);mask:linear-gradient(90deg,transparent,#000 7%,#000 93%,transparent)}
  .hrk .serit + .serit{margin-top:16px}
  .hrk .serit-ic{display:flex;gap:16px;width:max-content;animation:hrk-kay var(--sure,60s) linear infinite}
  .hrk .serit.ters .serit-ic{animation-direction:reverse}
  .hrk .serit:hover .serit-ic{animation-play-state:paused}
  .hrk .serit .galeri-oge{flex:none;height:clamp(150px,21vw,250px);aspect-ratio:4/3}

  /* Çağrı bandı: akan zemin + dönen halkalar */
  .hrk .cagri{background:linear-gradient(120deg,var(--koyu),var(--ana),var(--koyu));background-size:200% auto;animation:hrk-akis 9s linear infinite}
  .hrk .cagri::after{border-style:dashed;animation:hrk-don 40s linear infinite}
  .hrk .cagri::before{animation:hrk-don 28s linear infinite reverse}

  /* Belirme: bulanıktan netleşerek, sırayla */
  .js .hrk .gorun{filter:blur(8px);transform:translateY(34px) scale(.985);transition:opacity 1s cubic-bezier(.2,.7,.2,1),transform 1s cubic-bezier(.2,.7,.2,1),filter 1s cubic-bezier(.2,.7,.2,1);transition-delay:var(--gecikme,0ms)}
  .js .hrk .gorun.acik{filter:blur(0);transform:none}

  /* Üst menü dolunca alt çizgi akar */
  .hrk .ust.dolu::after{content:"";position:absolute;left:0;right:0;bottom:0;height:2px;background:linear-gradient(90deg,transparent,var(--ana),var(--altin),transparent);background-size:200% auto;animation:hrk-akis 5s linear infinite;opacity:.7}

  /* Doktorlar için sade sürüm (reklam yasağı: dikkat çekici parıltılar yok, sakin ve bilgilendirici) */
  .hrk-sade .btn-dolu::after,.hrk-sade .btn-wa::after,.hrk-sade .btn-beyaz::after,
  .hrk-sade .giris .btnler .btn:first-child::before,.hrk-sade .cagri .btnler .btn:first-child::before{display:none}
  .hrk-sade .aurora{opacity:.32}
  .hrk-sade .guven > span{animation:none}
  .hrk-sade .bolum:not(.koyu-bolum) .etiket{animation:none}

  @media(prefers-reduced-motion:reduce){
    .hrk *,.hrk *::before,.hrk *::after{animation:none !important}
    .hrk .giris h1 .kelime,.hrk .giris h1 .vurgu{opacity:1;filter:none;transform:none}
    .js .hrk .gorun{filter:none;transform:none}
    .hrk .serit{overflow-x:auto;-webkit-mask:none;mask:none}
    .hrk .aurora{display:none}
  }
</style>
@else
<script>
(function () {
  var b = document.body;
  if (!b.classList.contains('hrk')) return;
  var az = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Kaydırma ilerleme çubuğu
  var bar = document.getElementById('ilerleme');
  function ilerle() {
    var h = document.documentElement, o = h.scrollTop / ((h.scrollHeight - h.clientHeight) || 1);
    if (bar) bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, o)) + ')';
  }

  // Başlık kelime kelime (son kelime parlak)
  var h1 = document.querySelector('.giris h1');
  if (h1 && !az) {
    var kelimeler = h1.textContent.trim().split(/\s+/);
    h1.setAttribute('aria-label', kelimeler.join(' '));
    h1.textContent = '';
    kelimeler.forEach(function (k, i) {
      var s = document.createElement('span');
      s.className = (i === kelimeler.length - 1 && kelimeler.length > 2 && !b.classList.contains('hrk-sade')) ? 'vurgu' : 'kelime';
      if (s.className === 'vurgu') s.style.display = 'inline-block';
      s.style.setProperty('--i', i);
      s.setAttribute('aria-hidden', 'true');
      s.textContent = k;
      h1.appendChild(s);
      if (i < kelimeler.length - 1) h1.appendChild(document.createTextNode(' '));
    });
  }

  // Aynı kapsayıcıdaki öğeler sırayla belirsin
  var gruplar = new Map();
  document.querySelectorAll('.gorun').forEach(function (o) {
    var p = o.parentElement, n = gruplar.get(p) || 0;
    o.style.setProperty('--gecikme', Math.min(n, 6) * 90 + 'ms');
    gruplar.set(p, n + 1);
  });

  // Rakamlar sayarak artsın ("15+", "1.200", "4,9", "%98" biçimleri korunur)
  function say(el) {
    var m = el.textContent.trim().match(/^(\D*)(\d[\d.,]*)(.*)$/);
    if (!m) return;
    var ham = m[2], ondalik = ham.indexOf(',') > -1 ? ham.split(',')[1].length : 0;
    var hedef = parseFloat(ham.replace(/\./g, '').replace(',', '.'));
    if (!isFinite(hedef) || hedef === 0) return;
    var bas = null, sure = 1700;
    function yaz(v) { el.textContent = m[1] + v.toLocaleString('tr-TR', { minimumFractionDigits: ondalik, maximumFractionDigits: ondalik }) + m[3]; }
    function adim(t) {
      if (bas === null) bas = t;
      var p = Math.min(1, (t - bas) / sure), e = 1 - Math.pow(1 - p, 4);
      yaz(ondalik ? hedef * e : Math.round(hedef * e));
      if (p < 1) requestAnimationFrame(adim); else el.textContent = m[1] + ham + m[3];
    }
    yaz(0);
    requestAnimationFrame(adim);
  }
  var rakamlar = document.querySelectorAll('.bilgi-kart .rakam, .rozet-yil b');
  if (!az && 'IntersectionObserver' in window) {
    var rio = new IntersectionObserver(function (g) {
      g.forEach(function (x) { if (x.isIntersecting) { say(x.target); rio.unobserve(x.target); } });
    }, { threshold: 0.4 });
    rakamlar.forEach(function (r) { rio.observe(r); });
  }

  // Kartlarda fareyle gezinen ışık
  document.querySelectorAll('.deger, .hizmet, .yorum, .duyuru').forEach(function (k) {
    k.addEventListener('pointermove', function (e) {
      var r = k.getBoundingClientRect();
      k.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      k.style.setProperty('--my', (e.clientY - r.top) + 'px');
    });
  });

  // Kolajdaki küçük fotoğraf kaydırırken hafifçe kaysın
  var kucuk = document.querySelectorAll('.kolaj .b2');
  var bekliyor = false;
  function paralaks() {
    bekliyor = false;
    ilerle();
    if (az) return;
    kucuk.forEach(function (el) {
      var r = el.getBoundingClientRect(), fark = (r.top + r.height / 2 - innerHeight / 2) * -0.07;
      el.style.transform = 'translateY(' + fark.toFixed(1) + 'px)';
    });
  }
  window.addEventListener('scroll', function () { if (!bekliyor) { bekliyor = true; requestAnimationFrame(paralaks); } }, { passive: true });
  paralaks();
})();
</script>
@endif
