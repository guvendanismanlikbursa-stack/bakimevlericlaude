@extends('layouts.brand')
@section('title', '"'.$facility->name.'" Kurumunu Sahiplen')
{{--
  24 Agustos 2026: kullanicinin "sahiplenme basvurusu gelmiyor" sikayeti
  uzerine erisim loglari incelendi - son ~10 gunde bu formu 992 FARKLI
  kurum icin GERCEK insanlar goruntulemis (1140 goruntuleme) ama TEK BIR
  gercek basvuru bile yapilmamis. Sebep: bu sayfa index,follow olarak
  Google'da GORUNUYORDU (ayni gunku SEO denetiminde "kopya icerik"
  listesinde de cikmisti) - kurum sahibi olmayan, sadece kurum HAKKINDA
  arama yapan kisiler dogrudan bu FORMA dusuyor, sahiplenme niyeti
  olmadigi icin hemen ayriliyordu. Form artik aramada gorunmez (noindex)
  ama linkler takip edilir (follow) - gercek kurum sahipleri hala kendi
  kurum profil sayfasindaki "Sahiplen" butonuyla buraya ulasir.
--}}
@section('robots_meta', 'noindex,follow')

@section('content')
@php
  $brand = current_brand();
  $section = service_section_for_scope($facility->category->brand_scope);
  $colors = $section['theme'] ?? ['primary' => $brand['primary_color'], 'secondary' => $brand['secondary_color'], 'soft' => '#f8fafc'];

  // 28 Agustos 2026: kullanicinin talebi - "31 Agustos'a kadar sahiplenen
  // kurumlar Öne Çıkanlar listesinde yayinlanacak" kampanya bildirimi
  // (sure dolmak uzereyken) beklenen etkiyi yaratmadigi icin bu sayfadan
  // kaldirildi. Admin onay akisindaki GERCEK is kurali (facility_featured_
  // campaign_active(), Admin\FacilityClaimController/FacilityController)
  // BILEREK dokunulmadan birakildi - sadece bu sayfadaki tanitim metni
  // silindi.
  $benefits = [
    ['title' => 'Profilinizi siz yönetin', 'text' => 'Görsel, açıklama, hizmet ve fiyat bilgilerini istediğiniz zaman güncelleyin — yanlış bilgi varsa da düzeltme yetkisi sadece sahiplenince size geçer.'],
    ['title' => 'Ailelerden doğrudan talep alın, aramanıza gerek kalmaz', 'text' => 'Google Haritalar\'da sadece görünürsünüz; burada aileler fiyat/ziyaret talebini doğrudan panelinize bırakır, siz de doğrudan panelden yanıtlarsınız.'],
    ['title' => 'Doğrulanmış rozeti kazanın', 'text' => 'Sahiplenilen kurumlar ziyaretçilere "Onaylı" rozetiyle gösterilir, güven artar.'],
  ];

  $benefits[] = ['title' => 'Tamamen ücretsiz', 'text' => 'Onay sonrası hesabınıza ücretsiz teklif hakkı tanımlanır. Sizinle önceden konuşulmadan hiçbir ücret kesilmez, sürpriz fatura çıkmaz.'];
  $benefits[] = ['title' => 'Telefonunuzdan da rahatça yönetin', 'text' => 'Panelinizi bilgisayardan olduğu kadar telefonunuzdan da kullanabilirsiniz — sahada olsanız bile talepleri kaçırmazsınız.'];
  $benefits[] = ['title' => 'Hiçbir taahhüt yok', 'text' => 'Sözleşme veya kilitlenme söz konusu değil, istediğiniz zaman kullanmayı bırakabilirsiniz.'];

  // 1 Eylul 2026: kullanicinin talebi - "sahiplenme ve anlasmali calismalari
  // icin cok tesvik edici, komisyon orani yazmayacak sekilde, kurum turune
  // gore ayrilmis" metinler. Kampanya (ucretsiz Öne Cikan son tarihi) zaten
  // sona erdigi icin ikna gucu artik SOYUT vaatten degil, o kurum turunun
  // GERCEK gunluk derdine (bos yatak/kontenjan, kayit sezonu rekabeti, dogru
  // terapi eslesmesi) dogrudan hitap eden metinden gelmeli. "komisyon"/
  // "yuzde"/rakamsal ucret kelimesi kasitli olarak hicbir yerde yok - ayni
  // asagidaki broker kutusu gibi her sey iletisime yonlendiriliyor.
  $categoryPitch = match ($section['slug'] ?? null) {
    'yasli-bakim' => [
      'kicker' => 'Yaşlı bakım kurumları için',
      'hook' => 'Boş kalan her yatak bir kayıptır — biz o yatakları dolduracak aileleri bulur, kurumunuzu onlara biz anlatırız.',
      'points' => [
        ['label' => '🛏️ Doluluk', 'text' => 'Sabit giderleriniz yatak boşken de devam eder. Amacımız kontenjanınızı sürekli dolu tutacak, düzenli bir aile akışı sağlamak.'],
        ['label' => '💬 Güven', 'text' => 'Yaşlı bir yakını emanet etmek ailenin en zor kararlarından biridir; kurumunuzu bu güveni kazandıracak şekilde biz anlatırız.'],
        ['label' => '⏱️ Zaman', 'text' => 'Telefonda aile ikna etmeye, soru cevaplamaya vaktiniz kalmasın — siz bakıma odaklanın, aile bulma emeğini biz üstlenelim.'],
      ],
    ],
    'cocuk' => [
      'kicker' => 'Kreş ve anaokulları için',
      'hook' => 'Kayıt sezonu başlamadan kontenjanınızı doldurun, boş sıraların telaşını bu yıl yaşamayın.',
      'points' => [
        ['label' => '📅 Kayıt Sezonu', 'text' => 'Aileler kayıt döneminde birçok kreşi aynı anda karşılaştırır; sizi öne çıkaracak, fark yaratacak tanıtımı biz üstleniriz.'],
        ['label' => '👨‍👩‍👧 Doğru Eşleşme', 'text' => 'Yaş grubunuza ve programınıza gerçekten uygun aileleri buluruz — rastgele değil, kurumunuza uygun talep.'],
        ['label' => '⭐ İtibar', 'text' => 'Özenli tanıtımla bölgenizde konuşulan, aranan, tercih edilen bir kurum haline gelirsiniz.'],
      ],
    ],
    'rehabilitasyon' => [
      'kicker' => 'Rehabilitasyon merkezleri için',
      'hook' => 'Hangi terapiye ihtiyacı olduğunu bilmeyen aileleri, doğru anlatımla kurumunuza biz yönlendiririz.',
      'points' => [
        ['label' => '🎯 Doğru Yönlendirme', 'text' => 'Aileler çoğu zaman hangi terapi branşına ihtiyaçları olduğunu bilmez; ihtiyaca uygun kurumu biz anlatarak yönlendiririz.'],
        ['label' => '🧑‍⚕️ Dolu Program', 'text' => 'Boş seans saatleri yerine, uzmanlarınızın zamanını değerlendirecek düzenli bir danışan akışı sağlarız.'],
        ['label' => '📈 Bilinirlik', 'text' => 'Özenli tanıtımla bölgenizde daha çok aile tarafından bilinen, ilk akla gelen bir merkez olun.'],
      ],
    ],
    default => [
      'kicker' => 'İsteyen kurumlar için',
      'hook' => 'Aile bulma ve tanıtım emeğini biz üstlenelim, siz sadece kurumunuza odaklanın.',
      'points' => [
        ['label' => '💰 Maddi', 'text' => 'Sizin için bulunan, sizin için tanıtılan aile talepleri, daha çok dolu kontenjan demektir.'],
        ['label' => '🤝 Manevi', 'text' => 'Aile bulma ve tanıtım emeğini siz harcamazsınız, bu özenli süreci sizin için biz yürütürüz.'],
        ['label' => '⭐ Statü', 'text' => 'Özenli tanıtımla bölgenizde öne çıkan, tercih edilen bir kurum olun.'],
      ],
    ],
  };
@endphp
<div class="max-w-5xl mx-auto px-4 py-12">
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="text-sm font-semibold text-gray-500">← {{ $facility->name }} sayfasına dön</a>

  <div class="mt-4 grid lg:grid-cols-[1.05fr_1fr] gap-8 items-start">
    {{-- 12 Agustos 2026: kullanicinin talebi - bu sayfa sadece bir form
         kutusuydu, hicbir gorsel/renk/aciklama yoktu. Artik sol tarafta
         "neden sahiplenmeli" faydalari + kurum ozeti, sagda form var -
         diger premium sayfalarla ayni gorsel dil. --}}
    <div>
      <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm mb-6">
        <div class="relative h-40" style="background: {{ $colors['primary'] }};">
          @if($section['hero_image'] ?? null)
            <img src="{{ $section['hero_image'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40">
          @endif
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
          <div class="relative h-full flex flex-col justify-end p-5 text-white">
            @if($section)<span class="text-xs font-black uppercase tracking-wide bg-white/20 border border-white/25 rounded-full px-3 py-1 inline-block mb-2 w-fit">{{ $section['title'] }}</span>@endif
            <div class="font-black text-xl">{{ $facility->name }}</div>
            <div class="text-sm text-white/80">{{ $facility->city->name ?? '' }} · {{ $facility->district ?? '' }}</div>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 mb-3">
        <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">Kurumunuzu Sahiplenin</div>
        <div class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-800 px-3 py-1 text-xs font-black">🎉 Tamamen ücretsiz</div>
      </div>
      <h1 class="text-2xl md:text-3xl font-black text-gray-950 mb-2">"{{ $facility->name }}" kurumunu sahiplenerek profilin kontrolünü alın</h1>
      <p class="text-base font-bold leading-snug mb-4" style="color: {{ $colors['primary'] }};">{{ $categoryPitch['hook'] }}</p>

      {{-- 13 Agustos 2026: kullanicinin talebi - "kurumu goren mutlaka
           sahiplenmek istemeli" - soyut vaat yerine bu kurumun KENDI
           gercek goruntulenme rakamini ve bolgesindeki sosyal kaniti
           (kac benzer kurum zaten sahiplenildi) on plana cikarir. --}}
      @if($facility->views_count > 0 || $nearbyClaimedCount > 0 || $categoryClaimedCount > 0)
        <div class="rounded-xl border p-4 mb-5" style="background: {{ $colors['soft'] }}; border-color: {{ $colors['primary'] }}33;">
          @if($facility->views_count > 0)
            <p class="text-sm font-bold" style="color: {{ $colors['primary'] }};">
              👀 Kurumunuz şu ana kadar <span class="text-lg">{{ number_format($facility->views_count, 0, ',', '.') }}</span> kez görüntülendi — ama sahiplenmediğiniz için ailelerden gelen fiyat/ziyaret taleplerini göremiyorsunuz.
            </p>
          @endif
          @if($nearbyClaimedCount > 0)
            <p class="text-sm text-gray-600 mt-1.5">📍 {{ $facility->city->name ?? 'Bölgenizde' }}'de aynı kategoride <strong>{{ $nearbyClaimedCount }}</strong> kurum sahiplenildi ve aktif teklif alıyor.</p>
          @elseif($categoryClaimedCount > 0)
            <p class="text-sm text-gray-600 mt-1.5">📍 Aynı kategoride Türkiye genelinde <strong>{{ $categoryClaimedCount }}</strong> kurum sahiplenildi ve aktif teklif alıyor.</p>
          @endif
        </div>
      @endif

      <div class="space-y-3">
        @foreach($benefits as $b)
          <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <span class="inline-flex rounded-lg p-2 shrink-0" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </span>
            <div>
              <div class="font-black text-gray-950 text-sm">{{ $b['title'] }}</div>
              <p class="text-sm text-gray-500 mt-0.5">{{ $b['text'] }}</p>
            </div>
          </div>
        @endforeach
      </div>

      {{-- 13 Agustos 2026: kullanicinin talebi - "panelin nasil gorunecegini
           gormeden karar veriyorum" - gercek dashboard'un sadelestirilmis,
           gercekci bir onizlemesi (gercek ekran goruntusu degil, ayni
           gorsel dili tasiyan bir maket). --}}
      <div class="mt-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <p class="text-xs font-black uppercase tracking-wide text-gray-400 mb-3">Sahiplenince Panelinizde Göreceğiniz Bazı Şeyler</p>
        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
          <div class="grid grid-cols-3 gap-2 mb-3">
            <div class="bg-white rounded-lg p-2.5 text-center border border-gray-100">
              <div class="text-lg font-black" style="color: {{ $colors['primary'] }};">{{ max($facility->views_count, 42) }}</div>
              <div class="text-[10px] text-gray-400 font-semibold">Görüntülenme</div>
            </div>
            <div class="bg-white rounded-lg p-2.5 text-center border border-gray-100">
              <div class="text-lg font-black" style="color: {{ $colors['primary'] }};">3</div>
              <div class="text-[10px] text-gray-400 font-semibold">Teklif Talebi</div>
            </div>
            <div class="bg-white rounded-lg p-2.5 text-center border border-gray-100">
              <div class="text-lg font-black" style="color: {{ $colors['primary'] }};">5</div>
              <div class="text-[10px] text-gray-400 font-semibold">Ücretsiz Hak</div>
            </div>
          </div>
          <div class="bg-white rounded-lg p-2.5 border border-gray-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-600">Ayşe Y. — "Fiyat bilgisi alabilir miyim?"</span>
            <span class="text-[10px] font-black text-white px-2 py-0.5 rounded-full" style="background: {{ $colors['primary'] }};">Yanıtla</span>
          </div>
        </div>
      </div>

      {{-- 28 Agustos 2026: kullanicinin talebi - kurum yetkilisinin aklinda
           "sahiplendikten sonra nasil daha fazla aileye ulasirim" konusunda
           hic soru isareti kalmamali. is_broker_managed durumunun GERCEKTEN
           ne sagladigi (bkz. OfferRequestNotificationService, VideoCompressionService)
           uzerinden, "komisyon" kelimesi HIC KULLANILMADAN anlatildi - ucret/
           sart detaylari kasitli olarak sayfada YOK, hepsi iletisime
           yonlendiriliyor (kullanicinin acik talebi: "sadece ucret olayini
           bana atman gerekiyor"). --}}
      <div class="mt-6 rounded-2xl border-2 p-5" style="border-color: {{ $colors['primary'] }}33; background: {{ $colors['soft'] }};">
        <p class="text-xs font-black uppercase tracking-wide mb-2" style="color: {{ $colors['primary'] }};">{{ $categoryPitch['kicker'] }}: Aile Bulma ve Tanıtımı Biz Üstlenelim</p>
        <p class="text-sm text-gray-700 leading-relaxed mb-3">
          Sahiplenme her zaman <strong>tamamen ücretsizdir</strong> ve size profil yönetimiyle doğrudan teklif talebi akışını sağlar. Bunun ötesinde, isteyen kurumlarımıza sunduğumuz daha kapsamlı bir işbirliği seçeneğimiz de var: sizin için uygun aileleri <strong>biz buluyor</strong>, kurumunuzun sunduğu imkanları ve öne çıkan özelliklerini bu ailelere <strong>biz anlatıyoruz</strong> — siz bu süreçte yorulmazsınız. Kurumunuza <strong>tanıtım videosu</strong> ekleme gibi ek görünürlük imkanlarıyla desteklenen bu özenli tanıtımın ardından, <strong>size sadece uygun bulduğumuz aileyi kurumunuza kayıt etmek kalır.</strong>
        </p>
        <div class="grid sm:grid-cols-3 gap-2 mb-3">
          @foreach($categoryPitch['points'] as $p)
            <div class="bg-white rounded-lg p-3 border border-gray-100">
              <div class="text-xs font-black text-gray-950 mb-0.5">{{ $p['label'] }}</div>
              <p class="text-xs text-gray-500">{{ $p['text'] }}</p>
            </div>
          @endforeach
        </div>
        <p class="text-xs text-gray-500">Bu seçenek tamamen isteğe bağlıdır, temel sahiplenme her koşulda ücretsiz kalmaya devam eder. Şartlar ve detaylı bilgi için lütfen sağ alttaki 💬 sohbet ikonundan bizimle iletişime geçin.</p>
      </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:sticky lg:top-24">
      <h2 class="font-black text-gray-950 text-lg mb-1">Başvuru Formu</h2>
      <p class="text-gray-500 text-sm mb-1">Ad, e-posta ve telefon bilgilerinizi girip başvurun — evrak olmadan da başlatabilirsiniz.</p>
      {{-- 14 Agustos 2026: kullanicinin talebi - "uzun surer" tereddudunu
           kirmak icin somut bir zaman tahmini; onay suresi zaten vardi,
           formu DOLDURMA suresi eksikti. --}}
      <p class="text-xs font-semibold mb-1" style="color: {{ $colors['primary'] }};">⏱️ Formu doldurmak yaklaşık 1 dakika sürer, onay genellikle 24 saat içinde tamamlanır.</p>

      <form method="POST" action="{{ brand_route('facility-claim.store', ['slug' => $facility->slug]) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @include('themes._shared.partials.honeypot')
        <input type="hidden" name="lat" id="js-claim-lat">
        <input type="hidden" name="lng" id="js-claim-lng">
        <label for="claim-applicant-name" class="sr-only">Ad Soyad</label>
        <input type="text" id="claim-applicant-name" name="applicant_name" value="{{ old('applicant_name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2.5 w-full">
        <label for="claim-applicant-email" class="sr-only">E-posta</label>
        <input type="email" id="claim-applicant-email" name="applicant_email" value="{{ old('applicant_email') }}" placeholder="E-posta (giriş bilgileri buraya gönderilecek)" required class="border rounded-lg px-3 py-2.5 w-full">
        <label for="claim-applicant-phone" class="sr-only">Telefon</label>
        <input type="text" id="claim-applicant-phone" name="applicant_phone" value="{{ old('applicant_phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2.5 w-full">
        <div>
          <label for="claim-document" class="text-sm font-medium block mb-1">Evrak / Fatura Görseli <span class="text-gray-400 font-normal">(opsiyonel, daha sonra da ekleyebilirsiniz)</span></label>
          <input type="file" id="claim-document" name="document" accept="image/*" class="border rounded-lg px-3 py-2.5 w-full text-sm">
          {{-- 13 Agustos 2026: kullanicinin talebi - belge neden istendigi
               ve nasil kullanildigi acikca yazilmali, aksi halde yabanci
               bir platforma kimlik belgesi yuklerken tereddut olusur. --}}
          <p class="text-xs text-gray-400 mt-1">Bu belge sadece kurum yetkilisi olduğunuzu doğrulamak için kullanılır, sitede yayınlanmaz, sadece yetkili adminler görebilir. Şimdi eklemezseniz 24 saat içinde WhatsApp veya e-posta ile gönderebilirsiniz — aksi halde başvurunuz otomatik iptal edilir (kurum sahibi olmayan kişilerin kurumları ele geçirmesini önlemek için).</p>
        </div>
        <label for="claim-note" class="sr-only">Not (opsiyonel)</label>
        <textarea id="claim-note" name="note" placeholder="Eklemek istediğiniz not (opsiyonel)" rows="3" class="border rounded-lg px-3 py-2.5 w-full">{{ old('note') }}</textarea>

        {{-- 19 Agustos 2026: kullanicinin bildirdigi gercek eksik - aile
             kaydinda KVKK acik riza onay kutusu vardi, burada yoktu. --}}
        <label class="flex items-start gap-2 text-xs text-gray-600 leading-relaxed">
          <input type="checkbox" name="consent" required value="1" class="mt-0.5">
          <span>
            <a href="{{ brand_route('pages.show', ['slug' => 'kvkk']) }}" target="_blank" class="text-primary underline font-semibold">Açık Rıza Metni ve Kişisel Verilerin Korunması Aydınlatma Metni</a>'ni
            okudum, kişisel verilerimin belirtilen amaçlarla işlenmesine açıkça rıza gösteriyorum.
            <span class="font-semibold">Bu kutuyu işaretlemek zorunludur.</span>
          </span>
        </label>

        <button class="w-full py-3 rounded-lg font-black text-white" style="background: {{ $colors['primary'] }};">Başvuruyu Gönder</button>
        {{-- 14 Agustos 2026: kullanicinin talebi - "hicbir taahhut yok" zaten
             fayda listesinde vardi ama diger 6 maddenin arasinda kayboluyordu;
             asil tereddut anı (gonder butonuna basmadan hemen once) tam
             burada - somut ve gorunur bir guvence tam bu noktada olmali. --}}
        <p class="text-xs text-center font-semibold" style="color: {{ $colors['primary'] }};">🔓 Sözleşme veya ücret zorunluluğu yok — istediğiniz an destek hattından profilin kaldırılmasını talep edebilirsiniz.</p>
        <p class="text-xs text-gray-400">Tarayıcınız konum izni isteyebilir; bu, başvurunuzun kurum adresine yakınlığını admin incelemesinde göstermek içindir. İzin vermezseniz başvurunuz yine de gönderilir.</p>
        {{-- 13 Agustos 2026: kullanicinin talebi - genel bir KVKK/veri
             guvenligi guvencesi hic yoktu (belgeye ozel guvence disinda). --}}
        <p class="text-xs text-gray-400">Girdiğiniz kişisel bilgiler <a href="{{ brand_route('pages.show', ['slug' => 'kvkk']) }}" class="underline" target="_blank" rel="noopener">KVKK</a> kapsamında korunur, üçüncü taraflarla paylaşılmaz.</p>
      </form>

      {{-- 13 Agustos 2026: kullanicinin talebi - "bir sorun olursa kime
           ulasirim" belirsizligi. Sag-alt canli sohbet zaten TUM sitede
           mevcut (bkz. layouts/brand.blade.php), burada sadece varligina
           acikca dikkat cekiliyor. --}}
      <p class="text-xs text-gray-400 mt-4 text-center">Bir sorunuz mu var? Sağ alttaki 💬 sohbet ikonuna tıklayıp bize anında ulaşabilirsiniz.</p>
    </div>
  </div>
</div>
<script>
(function () {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(function (position) {
    document.getElementById('js-claim-lat').value = position.coords.latitude;
    document.getElementById('js-claim-lng').value = position.coords.longitude;
  }, function () { /* izin verilmedi, sessizce yoksay */ });
})();
</script>
@endsection
