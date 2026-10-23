{{-- 23 Ekim 2026: admin kurum sitesi formunun üst kısmı (durum, adres, alan adı). --}}
@php
  $alan = 'display:block;width:100%;border:1.5px solid #d6dde0;border-radius:10px;padding:10px 12px;font:inherit;background:#fff;color:#111827;';
  $etiket = 'display:block;font-weight:800;font-size:14px;color:#111827;margin-bottom:4px;';
@endphp
  <div style="background:#f8fafc;border-radius:12px;padding:16px;display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
    <div>
      <label for="ks-durum" style="{{ $etiket }}">Durum</label>
      <select id="ks-durum" name="status" style="{{ $alan }}">
        @foreach(\App\Models\FacilityWebsite::STATUSES as $key => $label)
          <option value="{{ $key }}" @selected(old('status', $site->status) === $key)>{{ $label }}</option>
        @endforeach
      </select>
      @if($site->published_at)<div style="font-size:12px;color:#6b7280;margin-top:4px;">İlk yayın: {{ $site->published_at->format('d.m.Y H:i') }}</div>@endif
    </div>
    <div>
      <label for="ks-slug" style="{{ $etiket }}">Adres (bakimevleri.com/siteler/…)</label>
      <input id="ks-slug" name="slug" value="{{ old('slug', $site->slug) }}" required maxlength="120" style="{{ $alan }}">
      @error('slug')<div style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</div>@enderror
    </div>
    <div>
      <label for="ks-domain" style="{{ $etiket }}">Kurumun alan adı (varsa)</label>
      <input id="ks-domain" name="custom_domain" value="{{ old('custom_domain', $site->custom_domain) }}" maxlength="190" placeholder="gunesbakimevi.com" style="{{ $alan }}">
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">Alan adı hostinge (cPanel'de ana sitenin klasörüne) yönlendirildikten sonra girin. Site yayındaysa eski adres bu alan adına yönlenir.</div>
      @error('custom_domain')<div style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</div>@enderror
    </div>
    <div>
      <label for="ks-plan" style="{{ $etiket }}">Site paketi</label>
      <select id="ks-plan" name="plan" style="{{ $alan }}">
        @foreach(\App\Models\FacilityWebsite::PLANS as $key => $label)
          <option value="{{ $key }}" @selected(old('plan', $site->plan ?: 'standart') === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">Profesyonel Site: logolu, çok sayfalı premium tasarım + aylık rapor.</div>
    </div>
    <div>
      <label for="ks-pro-bitis" style="{{ $etiket }}">Profesyonel Site bitiş tarihi</label>
      <input id="ks-pro-bitis" type="date" name="pro_until" value="{{ old('pro_until', optional($site->pro_until)->format('Y-m-d')) }}" style="{{ $alan }}">
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">Ödeme dönemi bitince site kendiliğinden hazır tasarıma döner. Boş: süresiz.</div>
      @error('pro_until')<div style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</div>@enderror
    </div>
  </div>
  <div style="background:#f8fafc;border-radius:12px;padding:14px 16px;">
    <div style="font-weight:900;margin-bottom:6px;">Şu anki adres</div>
    <a href="{{ $site->publicUrl() }}" target="_blank" rel="noopener" style="color:#0d6e6a;font-weight:700;word-break:break-all;">{{ $site->publicUrl() }}</a>
  </div>
