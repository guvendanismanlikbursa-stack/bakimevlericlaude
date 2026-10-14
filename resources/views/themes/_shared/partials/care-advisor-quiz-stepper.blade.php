{{-- 13 Ekim 2026 (4): kullanıcının talebi - "sorular açılır Pusula panelinde
     de ilerleyecek, sonuç Pusula'nın büyük sayfasında gösterilecek". Hem tam
     sayfadaki (form.blade.php) hem de kayan widget panelindeki (AJAX ile
     enjekte edilen _quiz-core.blade.php) adım/ilerleme mantığı AYNI - tek
     kaynak burada, her iki taraf da kendi kök elemanını vererek çağırır. Bu
     script tamamen VERİSİZ (sitewide, ücretsiz dahil edilir), hiçbir sorgu
     tetiklemez.

     13 Ekim 2026 (4-devam): kullanıcının 2 ek talebi karşılığı eklendi -
     (1) il/ilçe adımı işaretlenmeden ilerlenemez (data-require), (2) durum
     adımında en az 1 kutu işaretlenmeden ilerlenemez (data-require-any).
     (3) "yarıda bırakılanlar da anketler sayfasında listelenecek" -
     tarayıcıda üretilen bir progress_token ile her "Devam Et"te
     CareAdvisorController::progress()'a fire-and-forget kayıt gönderilir;
     form hiç bitirilmese bile admin o ana kadarki cevapları görebilir.

     13 Ekim 2026 (8): kullanıcının talebi - "birden fazla seçmeli sorular
     hariç kullanıcı işaretledikçe otomatik diğer soruya geçsin". Bir
     <select> değiştiğinde (checkbox'lar - "birden fazla seçmeli" - hariç,
     onlar <select> değil zaten otomatik dışlanıyor) o adımın şartı
     (varsa) karşılanıyorsa SESSİZCE (hata göstermeden) bir sonraki adıma
     geçilir. İl/ilçe gibi İKİ seçimli adımlarda, ikisi de dolana kadar
     geçilmez (data-require kontrolü sayesinde). --}}
<script>
  window.initCareAdvisorQuizStepper = function (root) {
    var steps = Array.prototype.slice.call(root.querySelectorAll('.quiz-step'));
    if (!steps.length) return;
    var total = steps.length;
    var current = 0;

    var form = root.querySelector('#care-advisor-quiz');
    var progressWrap = root.querySelector('#quiz-progress-wrap');
    var progressBar = root.querySelector('#quiz-progress-bar');
    var progressLabel = root.querySelector('#quiz-progress-label');
    var progressPct = root.querySelector('#quiz-progress-pct');
    var backBtn = root.querySelector('#quiz-back-btn');
    var nextBtn = root.querySelector('#quiz-next-btn');
    var submitBtn = root.querySelector('#quiz-submit-btn');
    var errorEl = root.querySelector('#quiz-step-error');
    if (!form || !progressWrap || !backBtn || !nextBtn || !submitBtn) return;

    // Yarıda bırakılan ilerlemeyi de kaydedebilmek için her kullanıcıya
    // (her quiz denemesine) özel, tarayıcıda üretilen bir kimlik. Aynı
    // token'la gönderilen her ilerleme kaydı AYNI satırı güncelleyecek -
    // bkz. CareAdvisorController::progress().
    var tokenEl = form.querySelector('.js-progress-token');
    if (tokenEl && !tokenEl.value) {
      tokenEl.value = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : ('pg-' + Date.now() + '-' + Math.random().toString(36).slice(2));
    }
    var progressUrl = @json(brand_route('care-advisor.progress'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    // FormData'daki "concerns[]" gibi çoklu-değerli alanlar Object.fromEntries
    // ile dönüştürülürse SADECE SON işaretli kutu kalır (anahtar çakışması) -
    // backend 'concerns' alanını dizi olarak beklediği için burada "[]" ekli
    // anahtarlar elle gerçek bir JS dizisine topluyoruz.
    function formDataToJsonBody(formEl) {
      var obj = {};
      new FormData(formEl).forEach(function (value, key) {
        if (key.slice(-2) === '[]') {
          var baseKey = key.slice(0, -2);
          if (!obj[baseKey]) obj[baseKey] = [];
          obj[baseKey].push(value);
        } else {
          obj[key] = value;
        }
      });
      return obj;
    }
    function saveProgress() {
      if (!tokenEl || !csrfToken) return;
      fetch(progressUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify(formDataToJsonBody(form)),
      }).catch(function () {});
    }

    // İl seçilince ilçe listesini doldurur (sitede zaten var olan admin
    // il/ilçe filtresiyle AYNI veri kaynağı: districts_for_city() ->
    // data-district-map). Form "city" değerini SLUG olarak tutar (admin
    // tarafındaki id tabanlı haritadan farklı), bu yüzden harita da slug
    // anahtarlı üretiliyor (bkz. _quiz-core.blade.php).
    var cityEl = form.querySelector('.js-city');
    var districtEl = form.querySelector('.js-district');
    if (cityEl && districtEl) {
      var districtMap = {};
      try { districtMap = JSON.parse(form.dataset.districtMap || '{}'); } catch (e) {}
      var fillDistricts = function () {
        var values = districtMap[cityEl.value] || [];
        districtEl.innerHTML = values.length ? '<option value="">İlçenizi seçer misiniz?</option>' : '<option value="">Önce ilinizi seçin</option>';
        values.forEach(function (name) {
          var opt = document.createElement('option');
          opt.value = name;
          opt.textContent = name;
          districtEl.appendChild(opt);
        });
        districtEl.disabled = values.length === 0;
      };
      cityEl.addEventListener('change', fillDistricts);
      fillDistricts();
    }

    function clearError() {
      if (errorEl) errorEl.classList.add('hidden');
      steps[current].querySelectorAll('.border-red-500').forEach(function (el) { el.classList.remove('border-red-500'); });
    }

    // data-require="ad1,ad2": hepsi doldurulmalı. data-require-any="ad[]":
    // o isimdeki kutulardan en az biri işaretli olmalı. İkisi de yoksa
    // adımda zorunluluk yok (serbestçe ilerlenir).
    //
    // 13 Ekim 2026 (6): kullanıcının talebi - "panelde emir verir gibi sert
    // bir üslup var". Tek genel/sabit uyarı yerine, hangi şart eksikse ona
    // göre daha spesifik ve davetkâr bir cümle gösteriliyor.
    function showStepError(message) {
      if (!errorEl) return;
      errorEl.textContent = message;
      errorEl.classList.remove('hidden');
    }
    // showErrors=false: sadece kontrol eder, ekranda hiçbir şey göstermez
    // (otomatik geçiş denemesi henüz şartı karşılamıyorsa kullanıcıyı
    // rahatsız etmemek için - örn. il seçilip ilçe henüz seçilmediyse).
    // showErrors=true: "Devam Et"e basılınca veya form gönderilince,
    // eksikse nazikçe uyarır (bkz. 13 Ekim 2026 (6) yorumu).
    function isStepValid(showErrors) {
      var stepEl = steps[current];
      var requireAll = stepEl.getAttribute('data-require');
      if (requireAll) {
        var firstInvalid = null;
        requireAll.split(',').forEach(function (name) {
          var el = form.querySelector('[name="' + name + '"]');
          if (!el) return;
          if (!el.value) {
            if (showErrors) el.classList.add('border-red-500');
            if (!firstInvalid) firstInvalid = el;
          } else if (showErrors) {
            el.classList.remove('border-red-500');
          }
        });
        if (firstInvalid) {
          if (showErrors) { showStepError('Size en uygun kurumları gösterebilmemiz için il ve ilçenizi seçer misiniz? 🙂'); firstInvalid.focus(); }
          return false;
        }
      }
      var requireAny = stepEl.getAttribute('data-require-any');
      if (requireAny) {
        var boxes = form.querySelectorAll('[name="' + requireAny + '"]');
        var anyChecked = Array.prototype.some.call(boxes, function (b) { return b.checked; });
        if (!anyChecked) {
          if (showErrors) showStepError('Size daha iyi yardımcı olabilmemiz için en az bir seçeneği işaretler misiniz? Uygun olanı yoksa "Belirli bir durumum yok" seçeneği de var.');
          return false;
        }
      }
      return true;
    }

    function render() {
      steps.forEach(function (el, i) { el.style.display = (i === current) ? '' : 'none'; });
      var pct = Math.round(((current + 1) / total) * 100);
      progressBar.style.width = pct + '%';
      var remaining = total - (current + 1);
      var label = 'Adım ' + (current + 1) + '/' + total;
      // Kullanıcı cevap verdikçe devam etmeye teşvik eden mesaj ("son 2 adım kaldı" tarzı).
      if (remaining === 0) label += ' · Son adım! 🎉';
      else if (remaining === 1) label += ' · Son 1 adım kaldı!';
      else if (remaining === 2) label += ' · Sadece 2 adım kaldı!';
      progressLabel.textContent = label;
      progressPct.textContent = '%' + pct;
      backBtn.classList.toggle('hidden', current === 0);
      var isLast = current === total - 1;
      nextBtn.classList.toggle('hidden', isLast);
      submitBtn.classList.toggle('hidden', !isLast);
    }

    function goNext(showErrors) {
      if (!isStepValid(showErrors)) return false;
      clearError();
      if (current >= total - 1) return false;
      saveProgress();
      current++;
      render();
      // Sadece tam sayfadaki form sayfanın kendi akışında kaydırma yapar;
      // dar widget panelinde kaydırma gerekmiyor (panel zaten sabit boyda).
      if (root === document) window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
      return true;
    }

    nextBtn.addEventListener('click', function () { goNext(true); });
    backBtn.addEventListener('click', function () {
      clearError();
      if (current > 0) { current--; render(); }
    });
    form.addEventListener('submit', function (e) {
      if (!isStepValid(true)) e.preventDefault();
    });

    // 13 Ekim 2026 (8): bir <select> değiştiğinde (checkbox'lar hariç) ve
    // bulunduğu adımın şartı (varsa) artık karşılanıyorsa otomatik olarak
    // bir sonraki soruya geçilir - kullanıcı "Devam Et"e basmak zorunda
    // kalmaz. Henüz şart karşılanmıyorsa (örn. il seçildi, ilçe bekleniyor)
    // sessizce hiçbir şey olmaz, kullanıcı diğer alanı da doldurunca aynı
    // mantık tekrar devreye girer.
    form.addEventListener('change', function (e) {
      if (e.target.tagName === 'SELECT' && steps[current] && steps[current].contains(e.target)) {
        goNext(false);
      }
    });

    progressWrap.classList.remove('hidden');
    render();
  };
</script>
