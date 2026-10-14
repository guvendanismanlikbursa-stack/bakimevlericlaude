{{-- 13 Ekim 2026 (3): kullanıcının talebi - Pusula'yı (eski "Bakım
     Danışmanı") bulmak için kullanıcının menüyü arayıp bulması zordu;
     "kayan müşteri asistanı gibi" sürekli sağ-altta, mevcut canlı destek
     balonunun (bkz. support-chat-widget.blade.php #js-chat-toggle, bottom:
     var(--chat-toggle-bottom, 1.25rem)) ÜSTÜNDE durmalı. Aynı değişkeni
     kullanarak, chat balonu mobilde yukarı itildiğinde (facilities/show.blade.php)
     bu da OTOMATİK onunla birlikte yukarı kayar - iki buton asla çakışmaz.

     13 Ekim 2026 (4): kullanıcının talebi (2 aşamada netleşti) - önce
     "iletişim botundaki açılır küçük panel gibi işleyiş olsun", sonra
     "sorular açılır Pusula panelinde de ilerleyecek, en son sonuç
     Pusula'nın büyük sayfasında gösterilecek". Yani: panel açılınca GERÇEK
     quiz (_quiz-core.blade.php) AJAX ile (CareAdvisorController::form()
     ?widget=1) yüklenir, aynı adım/ilerleme JS'i (initCareAdvisorQuizStepper)
     panel içinde de çalışır - ama form'un action'ı hâlâ normal GET
     (brand_route('care-advisor.results')) olduğu için SON gönderimde
     tarayıcı Pusula'nın büyük sonuç sayfasına gider - AJAX submit YOK,
     sadece SORULARIN yüklenmesi AJAX. JS ÇALIŞMAZSA bu <a> olduğu için
     doğrudan Pusula sayfasına gider (hiçbir işlevsellik kaybolmaz).

     13 Ekim 2026 (5): kullanıcının bildirdiği GERÇEK hata - panel "hayvan
     gibi büyük" açılıyordu. Kök neden: FTP-only deploy'da derlenmiş
     app-*.css SADECE sitede daha önce kullanılmış Tailwind sınıflarını
     içeriyor; bu panele özel ilk kez kullanılan köşeli-parantez sınıflar
     ("w-[min(90vw,360px)]" vb.) derlenmiş CSS'te YOK, tarayıcı sessizce
     yok sayıyordu. Bu yüzden panelin TÜM konum/boyut/geçiş kuralları
     artık class değil, aşağıdaki <style> içinde DÜZ CSS (derlemeye hiç
     bağlı değil, FTP ile olduğu gibi çalışır).

     13 Ekim 2026 (6): kullanıcının talebi - "mobil için daha kibar bir
     görünüş, hala kaba ve amatörce" - masaüstünde küçük köşe kartı aynı
     kalıyor ama mobilde artık yerli (native) bir "alt sayfa" (bottom
     sheet) gibi: tam genişlik, üstte yuvarlak köşe + sürükleme tutamacı,
     arkasında yumuşak bir karartma (backdrop), aşağıdan yukarı yumuşak
     kayma animasyonu. Başlık artık düz renkli kalın bir şerit değil, beyaz
     zeminde ince ayrıntılı - daha sakin/profesyonel. Giriş animasyonu
     abartılı "zıplama" yerine yumuşak belirme+yükselme.

     13 Ekim 2026 (7): kullanıcının talebi - "hiçbir şey kaydırma
     gerektirmeden görünmeli ... diğer sorularda da gerekmedikçe kaydırma
     ihtiyacı olmasın". _quiz-core.blade.php tam sayfa için sm:grid-cols-2/3
     kullanıyor (masaüstünde geniş alanda güzel görünür) - panel dar
     olduğu için bunu TEK sütuna zorlamak dikeyde ÇOK yer kaplıyordu
     (örn. il+ilçe alt alta). Artık 2 sütuna zorlanıyor (3 sütunlu
     seçenekler bölümü de 2'ye iner) - bu, tüm dikey yüksekliği neredeyse
     yarıya indiriyor. Form'un kendi iç boşlukları (p-6/space-y-5/gap-4)
     da panel bağlamında sıkılaştırıldı, alt bilgilendirme metni (zaten
     tam sayfada kalıcı duruyor) panelde yer kazanmak için gizlendi.

     14 Ekim 2026: kullanıcının talebi - "yazıyı kaldır, ikonu iletişim
     ikonu boyutuna getir". Artık #js-chat-toggle (support-chat-widget.blade.php)
     ile BİREBİR AYNI ölçüde (56x56px, yuvarlak) - sadece ikon, metin yok. --}}
<div id="js-pusula-backdrop"></div>

<a
  href="{{ brand_route('care-advisor.form') }}"
  id="js-pusula-toggle"
  aria-label="Pusula"
  style="background: {{ $brand['primary_color'] }};"
>
  <span class="js-pusula-toggle-icon">🧭</span>
</a>

<div id="js-pusula-panel" role="dialog" aria-modal="true" aria-label="Pusula">
  <div class="js-pusula-handle"></div>
  <div class="js-pusula-header">
    <span class="js-pusula-header-title" style="color: {{ $brand['primary_color'] }};">🧭 Pusula</span>
    <button type="button" id="js-pusula-close" aria-label="Kapat">
      <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
    </button>
  </div>
  <div id="js-pusula-body" class="js-pusula-body">
    <p class="text-sm text-gray-500 text-center py-6">Yükleniyor…</p>
  </div>
</div>

<style>
  {{-- 14 Ekim 2026: kullanıcının talebi - #js-chat-toggle (canlı destek
       balonu) ile BİREBİR AYNI boyut: 56x56px (w-14 h-14), yuvarlak,
       sadece ikon. --}}
  #js-pusula-toggle {
    position: fixed; right: 1.25rem; z-index: 40;
    bottom: calc(var(--chat-toggle-bottom, 1.25rem) + 4.25rem);
    width: 56px; height: 56px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 9999px; color: #fff;
    box-shadow: 0 8px 20px -6px rgba(15, 23, 42, .35);
    opacity: 0; transform: translateY(8px);
    animation: js-pusula-fade-in .4s ease-out .2s forwards;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  #js-pusula-toggle:hover { transform: translateY(-1px) scale(1.05); box-shadow: 0 10px 24px -6px rgba(15, 23, 42, .4); }
  .js-pusula-toggle-icon { font-size: 1.6rem; line-height: 1; }
  @keyframes js-pusula-fade-in { to { opacity: 1; transform: translateY(0); } }
  @media (prefers-reduced-motion: reduce) { #js-pusula-toggle { animation: none; opacity: 1; transform: none; } }

  #js-pusula-backdrop {
    position: fixed; inset: 0; z-index: 48; background: rgba(15, 23, 42, .4);
    opacity: 0; pointer-events: none; transition: opacity .25s ease;
  }
  #js-pusula-backdrop.js-pusula-open { opacity: 1; pointer-events: auto; }

  #js-pusula-panel {
    position: fixed; z-index: 49; right: 1.25rem;
    bottom: calc(var(--chat-toggle-bottom, 1.25rem) + 4.25rem + 3.5rem);
    width: min(90vw, 368px); max-height: 80vh;
    background: #fff; border-radius: 20px; overflow: hidden;
    box-shadow: 0 24px 48px -12px rgba(15, 23, 42, .28), 0 0 0 1px rgba(15, 23, 42, .04);
    display: flex; flex-direction: column;
    opacity: 0; transform: translateY(14px) scale(.98); pointer-events: none;
    transition: opacity .22s ease, transform .22s cubic-bezier(.32,.72,0,1);
  }
  #js-pusula-panel.js-pusula-open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
  .js-pusula-handle { display: none; }
  .js-pusula-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 1.1rem .7rem; border-bottom: 1px solid #f1f1f4; flex-shrink: 0;
  }
  .js-pusula-header-title { font-size: .95rem; font-weight: 800; }
  #js-pusula-close {
    width: 28px; height: 28px; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
    color: #9ca3af; background: transparent; transition: background .15s ease, color .15s ease;
  }
  #js-pusula-close:hover { background: #f3f4f6; color: #4b5563; }
  .js-pusula-body { padding: .85rem; overflow-y: auto; }

  {{-- 13 Ekim 2026 (7): "hiçbir şey kaydırma gerektirmeden görünmeli".
       _quiz-core.blade.php tam sayfa için sm:grid-cols-2/3 kullanıyor
       (masaüstünde geniş alanda güzel görünür) - panelde bunu TEK sütuna
       zorlamak dikeyde çok yer kaplıyordu (örn. il+ilçe alt alta, 2 satır).
       2 sütuna zorlamak neredeyse yarı yükseklik - alanlar yan yana kalır.
       Form'un kendi iç boşlukları da (p-6/space-y-5/gap-3/gap-4/mb-4/mb-5)
       panel bağlamında sıkılaştırıldı, alt bilgilendirme metni (tam
       sayfada kalıcı duruyor) panelde yer kazanmak için gizlendi. --}}
  .js-pusula-body .grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
  .js-pusula-body .gap-3, .js-pusula-body .gap-4 { gap: .55rem !important; }
  .js-pusula-body .mb-4, .js-pusula-body .mb-5 { margin-bottom: .6rem !important; }
  .js-pusula-body form.p-6 { padding: .95rem !important; }
  .js-pusula-body form.space-y-5 > :not([hidden]) ~ :not([hidden]) { margin-top: .7rem !important; }
  .js-pusula-body form > p.text-gray-400 { display: none; }

  @media (max-width: 640px) {
    #js-pusula-panel {
      left: 0; right: 0; bottom: 0; width: 100%; max-height: 92vh;
      border-radius: 20px 20px 0 0;
      transform: translateY(100%); padding-bottom: env(safe-area-inset-bottom, 0);
    }
    #js-pusula-panel.js-pusula-open { transform: translateY(0); }
    .js-pusula-handle { display: block; width: 36px; height: 4px; border-radius: 2px; background: #e5e7eb; margin: .6rem auto 0; flex-shrink: 0; }
    .js-pusula-header { padding-top: .5rem; }
  }
</style>

<script>
(function () {
  var toggleBtn = document.getElementById('js-pusula-toggle');
  var backdrop = document.getElementById('js-pusula-backdrop');
  var panel = document.getElementById('js-pusula-panel');
  var closeBtn = document.getElementById('js-pusula-close');
  var body = document.getElementById('js-pusula-body');
  if (!toggleBtn || !panel || !closeBtn || !body) return;

  var widgetUrl = @json(brand_route('care-advisor.form', ['widget' => 1]));
  // "henüz hiç yüklenmedi" durumu "varsayılan (null) bölüm zaten yüklü"
  // durumundan AYRI tutulmalı - yoksa ilk açılışta loadQuiz(null) çağrısı
  // "zaten yüklü" sanılıp hiç tetiklenmezdi. Bu yüzden undefined kullanılıyor.
  var loadedBolum;
  var loading = false;

  function loadQuiz(bolum) {
    if (loading || loadedBolum === bolum) return;
    loading = true;
    body.innerHTML = '<p class="text-sm text-gray-500 text-center py-6">Yükleniyor…</p>';
    var url = widgetUrl + (bolum ? '&bolum=' + encodeURIComponent(bolum) : '');
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
      .then(function (html) {
        body.innerHTML = html;
        loadedBolum = bolum;
        wireBolumSwitch();
        if (window.initCareAdvisorQuizStepper) window.initCareAdvisorQuizStepper(body);
      })
      .catch(function () {
        body.innerHTML = '<p class="text-sm text-red-600 text-center py-6">Yüklenemedi, lütfen <a class="underline font-bold" href="' + toggleBtn.href + '">Pusula sayfasını</a> doğrudan açın.</p>';
      })
      .finally(function () { loading = false; });
  }

  // Bölüm değiştirme linkleri normalde TÜM sayfayı Pusula'ya yönlendirirdi -
  // panel içindeyken bunun yerine sadece ilgili bölümün sorularını AJAX ile
  // yeniden yükleriz, kullanıcı panelden hiç çıkmaz.
  function wireBolumSwitch() {
    body.querySelectorAll('.js-bolum-switch').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        loadQuiz(link.getAttribute('data-bolum'));
      });
    });
  }

  function openPanel() {
    panel.classList.add('js-pusula-open');
    backdrop.classList.add('js-pusula-open');
    if (loadedBolum === undefined) loadQuiz(null);
  }
  function closePanel() {
    panel.classList.remove('js-pusula-open');
    backdrop.classList.remove('js-pusula-open');
  }

  toggleBtn.addEventListener('click', function (e) {
    e.preventDefault();
    if (panel.classList.contains('js-pusula-open')) closePanel(); else openPanel();
  });
  closeBtn.addEventListener('click', closePanel);
  backdrop.addEventListener('click', closePanel);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && panel.classList.contains('js-pusula-open')) {
      closePanel();
      toggleBtn.focus();
    }
  });
})();
</script>
