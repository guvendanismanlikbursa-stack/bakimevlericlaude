{{-- 23 Ekim 2026: kurum sitesi içerik formu - kurum panelinde ve admin
     panelinde aynı form kullanılır. Parametreler: $site, $facility, $images, $action
     Stiller satır içi: canlıda Tailwind yeniden derlenmiyor. --}}
@php
  $ks = 'display:block;width:100%;border:1.5px solid #d6dde0;border-radius:10px;padding:10px 12px;font:inherit;background:#fff;color:#111827;';
  $etiket = 'display:block;font-weight:800;font-size:14px;color:#111827;margin-bottom:4px;';
  $ipucu = 'font-size:12px;color:#6b7280;margin-top:4px;';
@endphp
<form method="POST" action="{{ $action }}" style="display:grid;gap:18px;">
  @csrf
  @method('PUT')
  @isset($ustAlanlar)
    @include($ustAlanlar)
  @endisset
  <div>
    <label for="ks-baslik" style="{{ $etiket }}">Sitenin ana başlığı</label>
    <input id="ks-baslik" name="hero_title" maxlength="150" value="{{ old('hero_title', $site->hero_title) }}" placeholder="{{ $facility->name }}" style="{{ $ks }}">
    <div style="{{ $ipucu }}">Boş bırakılırsa kurumunuzun adı görünür.</div>
  </div>
  <div>
    <label for="ks-alt" style="{{ $etiket }}">Alt yazı</label>
    <input id="ks-alt" name="hero_subtitle" maxlength="255" value="{{ old('hero_subtitle', $site->hero_subtitle) }}" placeholder="Ör. Sevdikleriniz için güvenli, sıcak ve huzurlu bir yuva" style="{{ $ks }}">
    <div style="{{ $ipucu }}">Başlığın altında görünen kısa cümle. Boş bırakılırsa kurum türü ve konumu yazar.</div>
  </div>
  <div>
    <label for="ks-hakkimizda" style="{{ $etiket }}">Hakkımızda</label>
    <textarea id="ks-hakkimizda" name="about_text" rows="7" maxlength="5000" style="{{ $ks }}">{{ old('about_text', $site->about_text) }}</textarea>
    <div style="{{ $ipucu }}">Boş bırakılırsa kurum profilinizdeki tanıtım yazısı kullanılır.</div>
  </div>

  <div>
    <span style="{{ $etiket }}">Öne çıkan fotoğraf</span>
    @if($images->isEmpty())
      <div style="{{ $ipucu }}">Henüz gerçek fotoğraf yok; sitede kategoriye uygun örnek görseller "Temsili görseldir" notuyla gösterilir. Gerçek fotoğraf eklendiğinde buradan seçilebilir.</div>
    @else
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;">
        <label style="display:flex;align-items:center;justify-content:center;text-align:center;border:2px solid #e5e7eb;border-radius:12px;padding:8px;font-size:13px;font-weight:700;cursor:pointer;min-height:90px;">
          <input type="radio" name="hero_image_id" value="" @checked(! old('hero_image_id', $site->hero_image_id)) style="margin-right:6px;"> Otomatik (ana fotoğraf)
        </label>
        @foreach($images as $img)
          <label style="position:relative;display:block;cursor:pointer;">
            <input type="radio" name="hero_image_id" value="{{ $img->id }}" @checked((int) old('hero_image_id', $site->hero_image_id) === $img->id) style="position:absolute;top:8px;left:8px;width:18px;height:18px;">
            <img src="{{ facility_thumb($img->path) }}" alt="Fotoğraf {{ $loop->iteration }}" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:12px;border:2px solid #e5e7eb;">
          </label>
        @endforeach
      </div>
    @endif
  </div>

  <div>
    <span style="{{ $etiket }}">Renk</span>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
      @foreach(\App\Models\FacilityWebsite::THEMES as $key => $t)
        <label style="display:inline-flex;align-items:center;gap:8px;border:2px solid #e5e7eb;border-radius:999px;padding:8px 14px;cursor:pointer;font-weight:700;font-size:14px;">
          <input type="radio" name="theme" value="{{ $key }}" @checked(old('theme', $site->theme ?: 'yesil') === $key)>
          <span style="width:18px;height:18px;border-radius:50%;background:{{ $t['ana'] }};display:inline-block;"></span>{{ $t['ad'] }}
        </label>
      @endforeach
    </div>
  </div>

  <div>
    <span style="{{ $etiket }}">Sitede görünecek bölümler</span>
    @php $bolumDurumu = $site->sectionStatus(); @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:8px;">
      @foreach(\App\Models\FacilityWebsite::SECTIONS as $key => $label)
        @php [$veriVar, $not] = $bolumDurumu[$key] ?? [true, '']; @endphp
        <label style="display:flex;align-items:flex-start;gap:8px;font-size:14px;border:1.5px solid {{ $veriVar ? '#e5e7eb' : '#fde68a' }};background:{{ $veriVar ? '#fff' : '#fffbeb' }};border-radius:10px;padding:8px 10px;">
          <input type="checkbox" name="sections[{{ $key }}]" value="1" @checked($site->sectionEnabled($key)) style="margin-top:3px;">
          <span><b style="font-weight:700;">{{ $label }}</b><span style="display:block;font-size:12px;color:{{ $veriVar ? '#047857' : '#92400e' }};">{{ $veriVar ? '✓ ' : '⚠ ' }}{{ $not }}</span></span>
        </label>
      @endforeach
    </div>
    <div style="{{ $ipucu }}">İşaretli olsa da bilgisi olmayan bölüm sitede görünmez. Fotoğraflar, hizmetler, yemek menüsü, kontenjan, telefon ve adres kurum profilinden otomatik gelir; onları profil sayfasından güncellersiniz.</div>
  </div>

  @if($errors->any())
    <div style="background:#fef2f2;color:#991b1b;border-radius:10px;padding:10px 12px;font-size:14px;">{{ $errors->first() }}</div>
  @endif
  <div>
    <button type="submit" style="background:#0d6e6a;color:#fff;font-weight:800;border:0;border-radius:12px;padding:12px 22px;cursor:pointer;">Kaydet</button>
  </div>
</form>
