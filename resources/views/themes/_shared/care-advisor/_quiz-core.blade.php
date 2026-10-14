{{-- 13 Ekim 2026 (4): kullanıcının talebi - "sorular açılır Pusula
     panelinde de ilerleyecek, en son sonuç Pusula'nın büyük sayfasında
     gösterilecek". Bu parçalı (partial) dosya artık HEM tam sayfada
     (form.blade.php) HEM DE kayan widget panelinde (AJAX ile CareAdvisorController::
     form() 'widget=1' isteğiyle) AYNEN kullanılır - tek kaynak, kopya yok.
     Adım geçiş JS'i BURADA değil, sitewide paylaşılan care-advisor-quiz-stepper
     script'inde (bkz. layouts/brand.blade.php) - çünkü widget panelinde bu
     HTML innerHTML ile enjekte edilir ve enjekte edilen <script> hiç
     çalışmaz; çağıran taraf (form.blade.php veya widget JS'i) init
     fonksiyonunu kendisi tetikler.

     13 Ekim 2026 (4-devam): kullanıcının 2 ek talebi - (1) "soruların
     başında il ilçe sorulması gerekiyor, bunlar işaretlenmeden
     ilerleyememeli" - bu yüzden il/ilçe artık 1. adım ve
     data-require="city,district" taşıyor. (2) "sorulan sorulardan en az 1
     tanesi tıklanması şartı da gerekli" - durum/öncelik adımı artık
     data-require-any="concerns[]" taşıyor; "belirli bir durumum yok"
     seçeneği de eklendi ki gerçekten özel bir durumu olmayan kullanıcı da
     bu şartı anlamlı şekilde geçebilsin.

     13 Ekim 2026 (6): kullanıcının talebi - "ilk açılış ekranı Pusula'da
     ne işe yaradığı bile yazmıyor" - panel AJAX ile sadece bu dosyayı
     yüklediği için (form.blade.php'deki H1/açıklama widget'ta hiç
     görünmüyordu), kısa bir tanıtım artık BURADA (tek kaynak, hem tam
     sayfada hem widget panelinde görünür). Ayrıca "panelde emir verir
     gibi sert bir üslup var" - zorunluluk/kilit metinleri daha davetkâr
     bir dile çevrildi (bkz. aşağıdaki metinler ve care-advisor-quiz-stepper.blade.php'deki
     dinamik hata mesajları).

     13 Ekim 2026 (7): kullanıcının talebi - "hiçbir şey kaydırma
     gerektirmeden görünmeli, ilk açılış hangi bölümdeyse o bölümün
     soruları gelsin, tekrar seçtirmek anlamsız ve yer kaplıyor". Widget
     panelinde ($isWidget) bölüm seçim ızgarası artık GÖSTERİLMİYOR -
     panel zaten aktif bölümün sorularıyla açılıyor, tekrar seçtirmek
     gereksiz yer kaplıyordu. Tam sayfada ($isWidget yok/false) bu ızgara
     gerçek bir gezinme özelliği olduğu için kalmaya devam ediyor. --}}
@php
  $ageExamples = ['yasli-bakim' => '78', 'cocuk' => '3', 'rehabilitasyon' => '45'];
  $ageEx = $ageExamples[$activeSection['slug']] ?? '40';
  $ageLabel = $activeSection['slug'] === 'cocuk' ? 'Çocuğun yaşı' : 'Hasta/kullanıcı yaşı';
  $conditionExamples = [
    'yasli-bakim' => 'Örn: Parkinson, diyabet, Alzheimer...',
    'cocuk' => 'Örn: Otizm, gelişimsel gecikme, DEHB...',
    'rehabilitasyon' => 'Örn: Felç sonrası, omurilik yaralanması, inme...',
  ];
  $conditionEx = $conditionExamples[$activeSection['slug']] ?? 'Varsa belirtin...';
  $districtMap = $cities->mapWithKeys(fn ($c) => [$c->slug => districts_for_city($c->name)]);
@endphp
<div class="text-center mb-5">
  <div class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black mb-2" style="background: {{ $activeSection['theme']['soft'] }}; color: {{ $activeSection['theme']['primary'] }};">🧭 Pusula</div>
  <p class="text-sm text-gray-600">Birkaç soruyu yanıtlayın; bölgenizdeki en uygun kurumlar sizi bulsun, size özel ücret bilgisi versin. Siz aramayın, onlar sizi arasın.</p>
</div>

@unless($isWidget ?? false)
  <div class="grid sm:grid-cols-2 gap-2 mb-5">
    @foreach($sections as $slug => $section)
      @php $active = $activeSection['slug'] === $slug; @endphp
      <a href="{{ brand_route('care-advisor.form', ['bolum' => $slug]) }}" data-bolum="{{ $slug }}" class="js-bolum-switch rounded-xl border px-4 py-3 text-sm font-black text-center {{ $active ? 'text-white' : 'bg-white text-gray-700 border-gray-200' }}" style="{{ $active ? 'background: '.$section['theme']['primary'].'; border-color: '.$section['theme']['primary'].';' : '' }}">{{ $section['title'] }}</a>
    @endforeach
  </div>
@endunless

<div id="quiz-progress-wrap" class="hidden mb-4">
  <div class="flex items-center justify-between text-xs font-black text-gray-500 mb-1.5">
    <span id="quiz-progress-label">Adım 1/5</span>
    <span id="quiz-progress-pct">%20</span>
  </div>
  <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
    <div id="quiz-progress-bar" class="h-full rounded-full transition-all" style="width: 20%; background: {{ $activeSection['theme']['primary'] }};"></div>
  </div>
</div>

<form method="GET" action="{{ brand_route('care-advisor.results') }}" id="care-advisor-quiz" data-district-map="{{ json_encode($districtMap) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
  <input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">
  {{-- 13 Ekim 2026 (4): kullanıcının talebi - "yarıda bırakılanlar da
       anketler sayfasında listelenecek". JS bu alanı doldurup her "Devam
       Et"te AYNI token ile ilerleme kaydı gönderir (bkz.
       care-advisor-quiz-stepper.blade.php) - böylece panel/sekme
       kapatılsa bile o ana kadar girilenler kaybolmaz. --}}
  <input type="hidden" name="progress_token" class="js-progress-token">

  <div class="quiz-step" data-step="1" data-require="city,district">
    <div class="text-lg font-black text-gray-950 mb-4">Nerede arıyorsunuz?</div>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block"><span class="text-xs font-black text-gray-500">İl</span>
        <select name="city" required class="js-city mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">İlinizi seçer misiniz?</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">İlçe</span>
        <select name="district" required class="js-district mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">Önce ilinizi seçin</option></select>
      </label>
    </div>
  </div>

  <div class="quiz-step" data-step="2">
    <div class="text-lg font-black text-gray-950 mb-4">Kimin için arıyorsunuz?</div>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block"><span class="text-xs font-black text-gray-500">{{ $ageLabel }}</span>
        <input type="number" name="patient_age" min="0" max="120" placeholder="Örn: {{ $ageEx }}" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3">
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">Cinsiyet</span>
        <select name="gender" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white">
          <option value="">Belirtmek istemiyorum</option>
          <option value="kadın">Kadın</option>
          <option value="erkek">Erkek</option>
        </select>
      </label>
    </div>
  </div>

  <div class="quiz-step" data-step="3" data-require-any="concerns[]">
    <div class="text-lg font-black text-gray-950 mb-4">İhtiyaç ve öncelikleriniz neler?</div>
    <label class="block mb-4"><span class="text-xs font-black text-gray-500">Durum / hastalık (varsa)</span>
      <input type="text" name="condition" placeholder="{{ $conditionEx }}" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3">
    </label>
    <div class="grid sm:grid-cols-3 gap-3">
      @foreach($activeSection['advisor_concerns'] ?? [] as $concern)
        <label class="flex items-center gap-2 text-sm font-semibold bg-gray-50 rounded-xl px-3 py-3"><input type="checkbox" name="concerns[]" value="{{ $concern['key'] }}"> {{ $concern['label'] }}</label>
      @endforeach
      <label class="flex items-center gap-2 text-sm font-semibold bg-gray-50 rounded-xl px-3 py-3"><input type="checkbox" name="concerns[]" value="none_specific"> Belirli bir durumum yok, genel bakıyorum</label>
    </div>
  </div>

  <div class="quiz-step" data-step="4">
    <div class="text-lg font-black text-gray-950 mb-4">Kurum türü ve bütçeniz nedir?</div>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block"><span class="text-xs font-black text-gray-500">Kurum türü</span>
        <select name="category" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">Farketmez</option>@foreach($categories as $category)<option value="{{ $category->slug }}">{{ $category->name }}</option>@endforeach</select>
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">Bütçe segmenti (üst sınır)</span>
        <select name="budget_max" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white">
          <option value="">Belirtmek istemiyorum</option>
          @foreach($priceTiers as $tier)
            <option value="{{ $tier['value'] }}">{{ $tier['label'] }}</option>
          @endforeach
        </select>
      </label>
    </div>
  </div>

  <div class="quiz-step" data-step="5">
    <div class="text-lg font-black text-gray-950 mb-1">Son adım: sonucunuzu görmeye hazır mısınız?</div>
    <div class="rounded-xl p-3 mb-4 text-sm font-semibold" style="background: {{ $activeSection['theme']['soft'] }}; color: {{ $activeSection['theme']['primary'] }};">
      💡 Adınızı ve telefonunuzu bırakırsanız <strong>kurumlar sizi arar, siz aramazsınız</strong> ve tüm uygun kurumları görürsünüz. Dilerseniz boş da bırakabilirsiniz, o zaman size birkaç örnek kurum gösteririz.
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block"><span class="text-xs font-black text-gray-500">Adınız</span>
        <input type="text" name="name" placeholder="Adınız Soyadınız" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3">
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">Telefon</span>
        <input type="tel" name="phone" placeholder="05xx xxx xx xx" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3">
      </label>
    </div>
  </div>

  {{-- 13 Ekim 2026 (6): metin JS tarafından (care-advisor-quiz-stepper.blade.php)
       hangi şartın eksik olduğuna göre doldurulur - burada sadece nazik bir
       varsayılan var, gerçek mesaj daha spesifik ve davetkâr olur. --}}
  <p id="quiz-step-error" class="hidden text-sm font-bold text-amber-700 text-center bg-amber-50 rounded-lg px-3 py-2">Devam edebilmemiz için birkaç bilgi daha rica edeceğiz 🙂</p>

  <div class="flex items-center gap-3 pt-2">
    <button type="button" id="quiz-back-btn" class="hidden rounded-xl border border-gray-200 text-gray-600 font-black px-5 py-4 text-sm">← Geri</button>
    <button type="button" id="quiz-next-btn" class="flex-1 rounded-xl text-white font-black px-5 py-4 text-lg" style="background: {{ $activeSection['theme']['primary'] }};">Devam Edelim →</button>
    <button type="submit" id="quiz-submit-btn" class="hidden flex-1 rounded-xl text-white font-black px-5 py-4 text-lg" style="background: {{ $activeSection['theme']['primary'] }};">Sonucumu Gör 🎯</button>
  </div>
  <p class="text-xs text-gray-400 text-center">Bu sonuçlar, girdiğiniz bilgilerle kurum profillerindeki hizmet bilgilerinin eşleşmesine göre puanlanır; kesin tıbbi/idari bir değerlendirme değildir.</p>
</form>
