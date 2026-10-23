{{-- 23 Ekim 2026: Profesyonel Site içerik alanları (kurum sitesi formunun
     içinde; admin her zaman, kurum yalnızca paketi etkinse görür). Boş satır
     eklemek için kaydettikten sonra yeni boş satırlar çıkar. --}}
@php
  $L = \App\Models\FacilityWebsite::LIMITS;
  $satirlar = function (string $alan, int $bos) use ($site, $L) {
      $dolu = old($alan) !== null ? array_values((array) old($alan)) : $site->listOf($alan);
      $toplam = min($L[$alan], count($dolu) + $bos);
      return array_pad(array_slice($dolu, 0, $L[$alan]), $toplam, []);
  };
  $kutu = 'background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:16px;display:grid;gap:12px;';
  $baslikStil = 'font-size:16px;font-weight:900;color:#111827;margin:0;';
  $satirStil = 'display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;';
  $hataGoster = function ($anahtar) use ($errors) { return $errors->first($anahtar); };
@endphp
<input type="hidden" name="pro_form" value="1">
<div style="border:2px solid #b08d57;border-radius:16px;padding:18px;display:grid;gap:16px;background:linear-gradient(180deg,#fffdf8,#fff);">
  <div>
    <div style="font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#b08d57;">★ Profesyonel Site</div>
    <div style="font-size:13px;color:#6b7280;margin-top:4px;">Bu alanlar yalnızca Profesyonel Site tasarımında görünür. Doldurulmayan bölüm sitede çıkmaz.</div>
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Logo, renk ve yazı stili</h3>
    <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:start;">
      <div>
        <label style="{{ $etiket }}">Logo</label>
        @if($site->logo_path)<img src="{{ facility_asset($site->logo_path) }}" alt="Logo" style="max-height:60px;max-width:200px;object-fit:contain;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:6px;margin-bottom:8px;">@endif
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="{{ $ks }}">
        <div style="{{ $ipucu }}">Arka planı saydam PNG en iyi sonucu verir. Logo yoksa adın baş harfi kullanılır.</div>
        @if($site->logo_path)<label style="display:inline-flex;gap:6px;align-items:center;font-size:13px;margin-top:6px;"><input type="checkbox" name="remove_logo" value="1"> Logoyu kaldır</label>@endif
        @if($hataGoster('logo'))<div style="color:#b91c1c;font-size:13px;">{{ $hataGoster('logo') }}</div>@endif
      </div>
      <div>
        <label style="{{ $etiket }}" for="pf-renk">Kurumun rengi</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input id="pf-renk" type="color" value="{{ old('brand_color', $site->brand_color ?: $site->proColors()['ana']) }}" oninput="document.getElementById('pf-renk-kod').value=this.value" style="width:52px;height:44px;border:1.5px solid #d6dde0;border-radius:10px;padding:2px;background:#fff;">
          <input id="pf-renk-kod" name="brand_color" value="{{ old('brand_color', $site->brand_color) }}" maxlength="7" placeholder="Boş: seçili hazır renk" style="{{ $ks }}">
        </div>
        <div style="{{ $ipucu }}">Logonuzdaki ana renk. Boş bırakılırsa yukarıda seçilen hazır renk kullanılır.</div>
        @if($hataGoster('brand_color'))<div style="color:#b91c1c;font-size:13px;">{{ $hataGoster('brand_color') }}</div>@endif
      </div>
      <div>
        <label style="{{ $etiket }}">Yazı stili</label>
        @foreach(\App\Models\FacilityWebsite::FONT_STYLES as $k => $lbl)
          <label style="display:flex;gap:8px;align-items:center;font-size:14px;margin-bottom:4px;"><input type="radio" name="font_style" value="{{ $k }}" @checked(old('font_style', $site->font_style ?: 'zarif') === $k)> {{ $lbl }}</label>
        @endforeach
      </div>
    </div>
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Tanıtım videosu</h3>
    <input name="video_url" value="{{ old('video_url', $site->video_url) }}" maxlength="255" placeholder="https://www.youtube.com/watch?v=..." style="{{ $ks }}">
    <div style="{{ $ipucu }}">YouTube adresi. Boş bırakılırsa kurum profilindeki video (varsa) gösterilir.</div>
    @if($hataGoster('video_url'))<div style="color:#b91c1c;font-size:13px;">{{ $hataGoster('video_url') }}</div>@endif
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Rakamlarla biz <span style="font-weight:600;color:#6b7280;font-size:13px;">(en fazla {{ $L['stats'] }})</span></h3>
    <div style="{{ $ipucu }}margin-top:-6px;">Ör. "25+" — "Yıllık tecrübe", "7/24" — "Hemşire desteği". Yalnızca doğru bilgi yazın.</div>
    @foreach($satirlar('stats', 4) as $i => $r)
      <div style="{{ $satirStil }}">
        <input name="stats[{{ $i }}][deger]" value="{{ $r['deger'] ?? '' }}" maxlength="20" placeholder="Rakam (ör. 25+)" style="{{ $ks }}">
        <input name="stats[{{ $i }}][etiket]" value="{{ $r['etiket'] ?? '' }}" maxlength="60" placeholder="Açıklama (ör. Yıllık tecrübe)" style="{{ $ks }}">
      </div>
    @endforeach
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Neden biz? <span style="font-weight:600;color:#6b7280;font-size:13px;">(en fazla {{ $L['values_list'] }})</span></h3>
    @foreach($satirlar('values_list', 2) as $i => $r)
      <div style="{{ $satirStil }}">
        <input name="values_list[{{ $i }}][baslik]" value="{{ $r['baslik'] ?? '' }}" maxlength="80" placeholder="Başlık (ör. Aile sıcaklığında bakım)" style="{{ $ks }}">
        <textarea name="values_list[{{ $i }}][metin]" rows="2" maxlength="300" placeholder="Kısa açıklama" style="{{ $ks }}">{{ $r['metin'] ?? '' }}</textarea>
      </div>
    @endforeach
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Ekibimiz <span style="font-weight:600;color:#6b7280;font-size:13px;">(en fazla {{ $L['team'] }})</span></h3>
    <div style="{{ $ipucu }}margin-top:-6px;">Adı boş bırakılan satır kaydedilmez. Kişinin izni olmadan fotoğrafını yüklemeyin.</div>
    @foreach($satirlar('team', 2) as $i => $r)
      <div style="{{ $satirStil }}">
        <input name="team[{{ $i }}][ad]" value="{{ $r['ad'] ?? '' }}" maxlength="100" placeholder="Ad soyad" style="{{ $ks }}">
        <input name="team[{{ $i }}][unvan]" value="{{ $r['unvan'] ?? '' }}" maxlength="100" placeholder="Görevi (ör. Sorumlu Hemşire)" style="{{ $ks }}">
        <textarea name="team[{{ $i }}][aciklama]" rows="2" maxlength="400" placeholder="Kısa tanıtım (isteğe bağlı)" style="{{ $ks }}">{{ $r['aciklama'] ?? '' }}</textarea>
        <div>
          @if(! empty($r['foto']))
            <input type="hidden" name="team[{{ $i }}][foto]" value="{{ $r['foto'] }}">
            <img src="{{ facility_asset($r['foto']) }}" alt="" style="width:56px;height:70px;object-fit:cover;border-radius:8px;margin-bottom:6px;">
            <label style="display:flex;gap:6px;align-items:center;font-size:12px;"><input type="checkbox" name="team[{{ $i }}][foto_sil]" value="1"> Fotoğrafı kaldır</label>
          @endif
          <input type="file" name="team[{{ $i }}][foto_dosya]" accept="image/png,image/jpeg,image/webp" style="{{ $ks }}font-size:13px;">
          @if($hataGoster("team.$i.foto_dosya"))<div style="color:#b91c1c;font-size:13px;">{{ $hataGoster("team.$i.foto_dosya") }}</div>@endif
        </div>
      </div>
    @endforeach
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Sık sorulan sorular <span style="font-weight:600;color:#6b7280;font-size:13px;">(en fazla {{ $L['faqs'] }})</span></h3>
    @foreach($satirlar('faqs', 2) as $i => $r)
      <div style="{{ $satirStil }}">
        <input name="faqs[{{ $i }}][soru]" value="{{ $r['soru'] ?? '' }}" maxlength="200" placeholder="Soru (ör. Ziyaret saatleriniz nedir?)" style="{{ $ks }}">
        <textarea name="faqs[{{ $i }}][cevap]" rows="2" maxlength="1500" placeholder="Cevap" style="{{ $ks }}">{{ $r['cevap'] ?? '' }}</textarea>
      </div>
    @endforeach
  </div>

  <div style="{{ $kutu }}">
    <h3 style="{{ $baslikStil }}">Duyurular <span style="font-weight:600;color:#6b7280;font-size:13px;">(en fazla {{ $L['announcements'] }})</span></h3>
    @foreach($satirlar('announcements', 1) as $i => $r)
      <div style="{{ $satirStil }}">
        <input name="announcements[{{ $i }}][baslik]" value="{{ $r['baslik'] ?? '' }}" maxlength="150" placeholder="Başlık (ör. Bahar şenliğimiz yapıldı)" style="{{ $ks }}">
        <input type="date" name="announcements[{{ $i }}][tarih]" value="{{ $r['tarih'] ?? '' }}" style="{{ $ks }}">
        <textarea name="announcements[{{ $i }}][metin]" rows="2" maxlength="2000" placeholder="Metin" style="{{ $ks }}">{{ $r['metin'] ?? '' }}</textarea>
      </div>
    @endforeach
  </div>
</div>
