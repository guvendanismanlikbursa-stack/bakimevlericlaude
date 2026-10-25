@extends('admin.layout')
@section('title', 'Doktor Sitesi')

@section('content')
{{-- 23 Ekim 2026: kullanıcının talebi - doktor siteleri yalnızca admin
     panelinden düzenlenir. Hukuki nedenle sitede hasta yorumu gösterilmez.
     Sınıflar yerine inline stil: yeni Tailwind sınıfları FTP deploy'da derlenmiyor. --}}
@php
  $alan = 'display:block;width:100%;border:1.5px solid #d6dde0;border-radius:10px;padding:10px 12px;font:inherit;background:#fff;color:#111827;';
  $etiket = 'display:block;font-weight:800;font-size:14px;color:#111827;margin-bottom:4px;';
  $ipucu = 'font-size:12px;color:#6b7280;margin-top:4px;';
  $hataStil = 'color:#b91c1c;font-size:13px;margin-top:4px;';
  $alanAdlari = ['hero_title' => 'Başlık', 'hero_subtitle' => 'Alt yazı', 'about_text' => 'Hakkımda', 'ilgi_alanlari' => 'İlgi alanları', 'calisma_saatleri' => 'Çalışma saatleri', 'photo_path' => 'Fotoğraf', 'theme' => 'Renk', 'sections' => 'Bölümler'];
@endphp
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
  <div>
    <h1 class="text-2xl font-bold">Doktor Sitesi</h1>
    <div style="color:#6b7280;font-size:14px;">{{ $site->fullName() }} · <a href="{{ route('admin.experts.edit', $expert) }}" style="text-decoration:underline;">Doktor kaydına dön</a></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="{{ url('/dr/'.$site->slug) }}?onizleme=1&tasarim=standart" target="_blank" rel="noopener" style="border:2px solid #0d6e6a;color:#0d6e6a;font-weight:800;border-radius:12px;padding:9px 16px;text-decoration:none;">👁 Standart önizle</a>
    <a href="{{ url('/dr/'.$site->slug) }}?onizleme=1&tasarim=pro" target="_blank" rel="noopener" style="border:2px solid #b08d57;color:#8a6a35;font-weight:800;border-radius:12px;padding:9px 16px;text-decoration:none;">★ Profesyonel önizle</a>
  </div>
</div>

@if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:16px;">{{ session('success') }}</div>@endif
@if($errors->any())<div style="background:#fef2f2;color:#991b1b;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:16px;">Kaydedilemedi, işaretli alanları kontrol edin.</div>@endif

<form method="POST" action="{{ route('admin.experts.website.update', $expert) }}" enctype="multipart/form-data" style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);display:grid;gap:18px;">
  @csrf
  @method('PUT')

  <div style="background:#f8fafc;border-radius:12px;padding:16px;display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
    <div>
      <label for="ds-durum" style="{{ $etiket }}">Yayın durumu</label>
      <select id="ds-durum" name="status" style="{{ $alan }}">
        @foreach(\App\Models\ExpertWebsite::STATUSES as $key => $label)
          <option value="{{ $key }}" @selected(old('status', $site->status) === $key)>{{ $label }}</option>
        @endforeach
      </select>
      @if($site->published_at)<div style="{{ $ipucu }}">İlk yayın: {{ $site->published_at->format('d.m.Y H:i') }}</div>@endif
    </div>
    <div>
      <label for="ds-slug" style="{{ $etiket }}">Adres (bakimevleri.com/dr/…)</label>
      <input id="ds-slug" name="slug" value="{{ old('slug', $site->slug) }}" required maxlength="120" style="{{ $alan }}">
      @error('slug')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label for="ds-domain" style="{{ $etiket }}">Doktorun alan adı (varsa)</label>
      <input id="ds-domain" name="custom_domain" value="{{ old('custom_domain', $site->custom_domain) }}" maxlength="190" placeholder="drayseyilmaz.com" style="{{ $alan }}">
      <div style="{{ $ipucu }}">Alan adı hostinge (cPanel'de ana sitenin klasörüne) yönlendirildikten sonra girin. Site yayındaysa eski adres bu alan adına yönlenir.</div>
      @error('custom_domain')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label for="ds-plan" style="{{ $etiket }}">Site paketi</label>
      <select id="ds-plan" name="plan" style="{{ $alan }}">
        @foreach(\App\Models\ExpertWebsite::PLANS as $key => $label)
          <option value="{{ $key }}" @selected(old('plan', $site->plan ?: 'standart') === $key)>{{ $label === 'Hazır site' ? 'Standart site' : $label }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="ds-pro-bitis" style="{{ $etiket }}">Profesyonel Site bitiş tarihi</label>
      <input id="ds-pro-bitis" type="date" name="pro_until" value="{{ old('pro_until', optional($site->pro_until)->format('Y-m-d')) }}" style="{{ $alan }}">
      <div style="{{ $ipucu }}">Süre bitince site kendiliğinden standart tasarıma döner. Boş: süresiz.</div>
    </div>
    {{-- 25 Ekim 2026: Klasik ya da Hareketli (doktorlarda sade sürüm) tasarımı admin seçer. --}}
    <div>
      <label for="ds-pro-tasarim" style="{{ $etiket }}">Profesyonel tasarım</label>
      <select id="ds-pro-tasarim" name="pro_tasarim" style="{{ $alan }}">
        @foreach(\App\Models\ExpertWebsite::PRO_TASARIMLAR as $key => $label)
          <option value="{{ $key }}" @selected(old('pro_tasarim', $site->pro_tasarim ?: 'klasik') === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <div style="{{ $ipucu }}">Yalnızca Profesyonel pakette geçerli. Hareketli tasarım doktorlarda sade sürümdür.</div>
    </div>
  </div>
  <div style="background:#f8fafc;border-radius:12px;padding:14px 16px;">
    <div style="font-weight:900;margin-bottom:6px;">Şu anki adres</div>
    <a href="{{ $site->publicUrl() }}" target="_blank" rel="noopener" style="color:#0d6e6a;font-weight:700;word-break:break-all;">{{ $site->publicUrl() }}</a>
  </div>

  <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
    <div>
      <label for="ds-baslik" style="{{ $etiket }}">Başlık</label>
      <input id="ds-baslik" name="hero_title" value="{{ old('hero_title', $site->hero_title) }}" maxlength="150" placeholder="{{ $site->fullName() }}" style="{{ $alan }}">
      <div style="{{ $ipucu }}">Boş bırakılırsa unvan ve ad gösterilir.</div>
      @error('hero_title')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label for="ds-alt" style="{{ $etiket }}">Alt yazı</label>
      <input id="ds-alt" name="hero_subtitle" value="{{ old('hero_subtitle', $site->hero_subtitle) }}" maxlength="255" placeholder="{{ $site->heroSubtitle() }}" style="{{ $alan }}">
      <div style="{{ $ipucu }}">Boş bırakılırsa branş ve konum gösterilir.</div>
      @error('hero_subtitle')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
    </div>
  </div>

  <div>
    <label for="ds-hakkimda" style="{{ $etiket }}">Hakkımda</label>
    <textarea id="ds-hakkimda" name="about_text" rows="7" maxlength="5000" style="{{ $alan }}" placeholder="{{ $site->aboutText() }}">{{ old('about_text', $site->about_text) }}</textarea>
    <div style="{{ $ipucu }}">Boş bırakılırsa branş, klinik ve konumdan kısa, tarafsız bir tanıtım yazılır. Tedavi garantisi, "en iyi" gibi iddialar ve hasta yorumu yazmayın.</div>
    @error('about_text')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
  </div>

  <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
    <div>
      <label for="ds-ilgi" style="{{ $etiket }}">İlgi alanları</label>
      <textarea id="ds-ilgi" name="ilgi_alanlari" rows="6" maxlength="3000" style="{{ $alan }}" placeholder="Her satıra bir konu yazın&#10;Örn: Diyabet takibi">{{ old('ilgi_alanlari', $site->ilgi_alanlari) }}</textarea>
      <div style="{{ $ipucu }}">Her satıra (veya virgülle ayırarak) bir konu.</div>
      @error('ilgi_alanlari')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label for="ds-saat" style="{{ $etiket }}">Çalışma saatleri</label>
      <textarea id="ds-saat" name="calisma_saatleri" rows="6" maxlength="1000" style="{{ $alan }}" placeholder="Hafta içi: 09:00 – 18:00&#10;Cumartesi: 09:00 – 13:00">{{ old('calisma_saatleri', $site->calisma_saatleri) }}</textarea>
      @error('calisma_saatleri')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
    </div>
  </div>

  <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
    @if($site->photo_path)
      <img src="{{ facility_asset($site->photo_path) }}" alt="" style="width:96px;height:120px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb;">
    @endif
    <div style="flex:1;min-width:240px;">
      <label for="ds-foto" style="{{ $etiket }}">Fotoğraf {{ $site->photo_path ? '(değiştirmek için yeni dosya seçin)' : '' }}</label>
      <input id="ds-foto" type="file" name="photo" accept="image/jpeg,image/png,image/webp" style="{{ $alan }}">
      <div style="{{ $ipucu }}">Dikey vesikalık tarzı fotoğraf en iyi görünür. Fotoğraf yoksa adın baş harfi gösterilir.</div>
      @error('photo')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
      @if($site->photo_path)
        <label style="display:inline-flex;gap:8px;align-items:center;margin-top:8px;font-size:14px;"><input type="checkbox" name="remove_photo" value="1"> Fotoğrafı kaldır</label>
      @endif
    </div>
  </div>

  <div>
    <div style="{{ $etiket }}">Renk</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      @foreach(\App\Models\ExpertWebsite::THEMES as $key => $t)
        <label style="display:inline-flex;gap:8px;align-items:center;border:1.5px solid #d6dde0;border-radius:12px;padding:8px 12px;cursor:pointer;">
          <input type="radio" name="theme" value="{{ $key }}" @checked(old('theme', $site->theme) === $key)>
          <span style="width:18px;height:18px;border-radius:50%;background:{{ $t['ana'] }};display:inline-block;"></span>{{ $t['ad'] }}
        </label>
      @endforeach
    </div>
  </div>

  <div>
    <div style="{{ $etiket }}">Sitede görünen bölümler</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      @foreach(\App\Models\ExpertWebsite::SECTIONS as $key => $label)
        <label style="display:inline-flex;gap:8px;align-items:center;border:1.5px solid #d6dde0;border-radius:12px;padding:8px 12px;cursor:pointer;">
          <input type="checkbox" name="sections[{{ $key }}]" value="1" @checked(old('sections') ? ! empty(old('sections')[$key]) : $site->sectionEnabled($key))> {{ $label }}
        </label>
      @endforeach
    </div>
    <div style="{{ $ipucu }}">Telefon, adres, klinik ve branş doktor kaydından gelir; değiştirmek için doktor kaydını düzenleyin. Hasta yorumları hukuki nedenle sitede yer almaz.</div>
  </div>


  {{-- 25 Ekim 2026: Profesyonel Site içerikleri (isteğe bağlı, kapalı bölme). --}}
  @php
    $L = \App\Models\ExpertWebsite::LIMITS;
    $satirlar = function (string $alan, int $bos) use ($site, $L) {
        $dolu = old($alan) !== null ? array_values((array) old($alan)) : $site->listOf($alan);
        return array_pad(array_slice($dolu, 0, $L[$alan]), min($L[$alan], count($dolu) + $bos), []);
    };
    $satirStil = 'display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;';
    $proHata = collect($errors->keys())->contains(fn ($k) => \Illuminate\Support\Str::startsWith($k, ['logo', 'brand_color', 'font_style', 'video_url', 'stats', 'faqs', 'announcements']));
  @endphp
  <input type="hidden" name="pro_form" value="1">
  <details @if($proHata) open @endif style="border:1.5px solid #e9d3ab;border-radius:14px;background:#fffdf8;">
    <summary style="cursor:pointer;padding:14px 16px;font-weight:800;color:#8a6a35;">★ Gelişmiş ayarları göster <span style="font-weight:600;color:#6b7280;font-size:13px;">(Profesyonel tasarımda görünür: logo, renk, video, rakamlar, S.S.S., duyurular)</span></summary>
    <div style="padding:0 16px 16px;display:grid;gap:16px;">
      <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:start;">
        <div>
          <label style="{{ $etiket }}">Logo</label>
          @if($site->logo_path)<img src="{{ facility_asset($site->logo_path) }}" alt="Logo" style="max-height:56px;max-width:200px;object-fit:contain;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:6px;margin-bottom:8px;">@endif
          <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="{{ $alan }}">
          @if($site->logo_path)<label style="display:inline-flex;gap:6px;align-items:center;font-size:13px;margin-top:6px;"><input type="checkbox" name="remove_logo" value="1"> Logoyu kaldır</label>@endif
          @error('logo')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
        </div>
        <div>
          <label style="{{ $etiket }}" for="dp-renk">Renk</label>
          <div style="display:flex;gap:8px;align-items:center;">
            <input id="dp-renk" type="color" value="{{ old('brand_color', $site->brand_color ?: $site->proColors()['ana']) }}" oninput="document.getElementById('dp-renk-kod').value=this.value" style="width:52px;height:44px;border:1.5px solid #d6dde0;border-radius:10px;padding:2px;background:#fff;">
            <input id="dp-renk-kod" name="brand_color" value="{{ old('brand_color', $site->brand_color) }}" maxlength="7" placeholder="Boş: seçili hazır renk" style="{{ $alan }}">
          </div>
          @error('brand_color')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
        </div>
        <div>
          <label style="{{ $etiket }}">Yazı stili</label>
          @foreach(\App\Models\ExpertWebsite::FONT_STYLES as $k => $lbl)
            <label style="display:flex;gap:8px;align-items:center;font-size:14px;margin-bottom:4px;"><input type="radio" name="font_style" value="{{ $k }}" @checked(old('font_style', $site->font_style ?: 'zarif') === $k)> {{ $lbl }}</label>
          @endforeach
        </div>
      </div>
      <div>
        <label style="{{ $etiket }}">Tanıtım videosu (YouTube)</label>
        <input name="video_url" value="{{ old('video_url', $site->video_url) }}" maxlength="255" placeholder="https://www.youtube.com/watch?v=..." style="{{ $alan }}">
        @error('video_url')<div style="{{ $hataStil }}">{{ $message }}</div>@enderror
      </div>
      <div style="display:grid;gap:8px;">
        <div style="{{ $etiket }}">Rakamlarla (en fazla {{ $L['stats'] }}) <span style="font-weight:600;color:#6b7280;font-size:12px;">ör. "15+" — "Yıllık tecrübe". Yalnızca doğru bilgi yazın.</span></div>
        @foreach($satirlar('stats', 4) as $i => $r)
          <div style="{{ $satirStil }}"><input name="stats[{{ $i }}][deger]" value="{{ $r['deger'] ?? '' }}" maxlength="20" placeholder="Rakam" style="{{ $alan }}"><input name="stats[{{ $i }}][etiket]" value="{{ $r['etiket'] ?? '' }}" maxlength="60" placeholder="Açıklama" style="{{ $alan }}"></div>
        @endforeach
      </div>
      <div style="display:grid;gap:8px;">
        <div style="{{ $etiket }}">Sık sorulan sorular (en fazla {{ $L['faqs'] }})</div>
        @foreach($satirlar('faqs', 2) as $i => $r)
          <div style="{{ $satirStil }}"><input name="faqs[{{ $i }}][soru]" value="{{ $r['soru'] ?? '' }}" maxlength="200" placeholder="Soru (ör. Randevu nasıl alınır?)" style="{{ $alan }}"><textarea name="faqs[{{ $i }}][cevap]" rows="2" maxlength="1500" placeholder="Cevap" style="{{ $alan }}">{{ $r['cevap'] ?? '' }}</textarea></div>
        @endforeach
      </div>
      <div style="display:grid;gap:8px;">
        <div style="{{ $etiket }}">Duyurular (en fazla {{ $L['announcements'] }})</div>
        @foreach($satirlar('announcements', 1) as $i => $r)
          <div style="{{ $satirStil }}"><input name="announcements[{{ $i }}][baslik]" value="{{ $r['baslik'] ?? '' }}" maxlength="150" placeholder="Başlık" style="{{ $alan }}"><input type="date" name="announcements[{{ $i }}][tarih]" value="{{ $r['tarih'] ?? '' }}" style="{{ $alan }}"><textarea name="announcements[{{ $i }}][metin]" rows="2" maxlength="2000" placeholder="Metin" style="{{ $alan }}">{{ $r['metin'] ?? '' }}</textarea></div>
        @endforeach
      </div>
    </div>
  </details>

  <div><button type="submit" style="background:#0d6e6a;color:#fff;border:0;border-radius:12px;padding:12px 22px;font-weight:800;font-size:15px;cursor:pointer;">Kaydet</button></div>
</form>

<div style="margin-top:18px;">@include('themes._shared.kurum-sitesi.pro._rapor', ['site' => $site])</div>

<div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-top:18px;">
  <h2 style="font-size:18px;font-weight:900;margin:0 0 10px;">Değişiklik geçmişi</h2>
  @forelse($revisions as $rev)
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;padding:10px 0;border-top:1px solid #f1f5f9;">
      <div style="font-size:14px;">
        <b>{{ $rev->created_at->format('d.m.Y H:i') }}</b> · {{ $rev->editor_name ?: 'Admin' }}
        <div style="font-size:12px;color:#6b7280;">Değişen: {{ collect($rev->before)->keys()->map(fn ($k) => $alanAdlari[$k] ?? $k)->implode(', ') }}</div>
      </div>
      <form method="POST" action="{{ route('admin.experts.website.revert', [$expert, $rev]) }}">
        @csrf
        <button type="submit" style="border:1.5px solid #d6dde0;background:#fff;border-radius:10px;padding:6px 12px;font-weight:700;font-size:13px;cursor:pointer;">Bu değişiklikten önceki hale dön</button>
      </form>
    </div>
  @empty
    <div style="color:#6b7280;font-size:14px;">Henüz değişiklik yok.</div>
  @endforelse
</div>
@endsection
