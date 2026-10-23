{{-- 23 Ekim 2026: Profesyonel Site - çağrı bandı ve iletişim bölümü.
     $tamSayfa: İletişim sayfası (form + kanallar + harita);
     $sadeceCagri: iç sayfaların sonunda yalnızca çağrı bandı. --}}
@php $sadeceCagri = $sadeceCagri ?? false; @endphp

@if(! $tamSayfa)
  <section class="bolum" style="{{ $sadeceCagri ? 'padding-top:0;' : '' }}">
    <div class="kap">
      <div class="cagri gorun">
        <div style="position:relative;z-index:1;">
          <h2>Kurumumuzu ziyaret etmek ister misiniz?</h2>
          <p>Sorularınızı yanıtlayalım, size uygun bir ziyaret günü planlayalım.</p>
        </div>
        <div class="btnler">
          @if($waLink)<a href="{{ $waLink }}" class="btn btn-beyaz" data-olay="whatsapp" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp</a>@endif
          @if($telLink)<a href="{{ $telLink }}" class="btn btn-cam" data-olay="arama">{!! $ikonTel !!} Hemen Arayın</a>@endif
          @if($sadeceCagri)<a href="{{ $link('iletisim') }}" class="btn btn-cam">Bilgi formu</a>@endif
        </div>
      </div>
    </div>
  </section>
@endif

@if(! $sadeceCagri)
  <section class="bolum {{ $tamSayfa ? '' : 'krem' }}" id="iletisim">
    <div class="kap iletisim-izgara">
      <div class="gorun">
        <div class="etiket">İletişim</div>
        <h2>Size nasıl yardımcı olabiliriz?</h2>
        <p class="giris-metni" style="margin-top:16px;">Formu doldurun ya da doğrudan bize ulaşın. Sizi en kısa sürede arayalım.</p>
        <div style="margin-top:22px;border-top:1px solid var(--cizgi);">
          @if($telLink)<a href="{{ $telLink }}" class="kanal" data-olay="arama"><span class="yuvar">{!! $ikonTel !!}</span><span><b>Telefon</b><span>{{ $telefon }}</span></span></a>@endif
          @if($waLink)<a href="{{ $waLink }}" class="kanal" data-olay="whatsapp" target="_blank" rel="noopener"><span class="yuvar" style="background:#e8f6ee;color:#1f9d55;">{!! $ikonWa !!}</span><span><b>WhatsApp</b><span>Mesaj gönderin, hızlıca dönelim</span></span></a>@endif
          @if($adres)<a href="{{ $haritaLink ?: '#' }}" class="kanal" target="_blank" rel="noopener"><span class="yuvar">{!! $ikonKonum !!}</span><span><b>Adres</b><span>{{ $adres }}</span></span></a>@endif
        </div>
      </div>
      <div class="form-kart gorun">
        <h3>Bilgi ve ziyaret talebi</h3>
        <form method="POST" action="{{ $formAction }}">
          @csrf
          @include('themes._shared.partials.honeypot')
          @if($tamSayfa)<input type="hidden" name="kaynak" value="iletisim">@endif
          @if(session('kurum_sitesi_basarili'))<div class="basari" role="status">{{ session('kurum_sitesi_basarili') }}</div>@endif
          <div>
            <label for="pk-ad">Adınız soyadınız</label>
            <input id="pk-ad" name="full_name" value="{{ old('full_name') }}" required maxlength="120" autocomplete="name">
            @error('full_name')<div class="hata">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="pk-tel">Telefonunuz</label>
            <input id="pk-tel" name="phone" value="{{ old('phone') }}" required maxlength="30" inputmode="tel" autocomplete="tel">
            @error('phone')<div class="hata">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="pk-mesaj">Mesajınız (isteğe bağlı)</label>
            <textarea id="pk-mesaj" name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
          </div>
          @if($whatsapp)
            <button type="submit" class="btn btn-wa" style="width:100%;">{!! $ikonWa !!} WhatsApp ile Gönder</button>
            <p class="not">Gönder'e basınca WhatsApp açılır, mesajınız hazır yazılı gelir; göndermeniz yeterli.</p>
          @else
            <button type="submit" class="btn btn-dolu" style="width:100%;">Gönder</button>
            <p class="not">Bilgileriniz yalnızca size dönüş yapılması için kullanılır.</p>
          @endif
        </form>
      </div>
    </div>
  </section>
  @if($tamSayfa && $haritaVar)
    <section class="bolum" style="padding-top:0;">
      <div class="kap gorun">
        <iframe class="harita" title="{{ $ad }} harita" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q={{ $f->lat }},{{ $f->lng }}&z=15&output=embed"></iframe>
      </div>
    </section>
  @endif
@endif
