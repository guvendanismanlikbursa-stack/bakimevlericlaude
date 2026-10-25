@extends('admin.layout')
@section('title', 'Site Satışları')

@section('content')
{{-- 25 Ekim 2026: kullanıcının talebi - kurum ve doktor sitelerinin satış takibi.
     Kart düzeni: her ekrana sığar. Sınıflar yerine inline stil. --}}
@php
  $btn = 'display:inline-block;border:1.5px solid #d6dde0;background:#fff;border-radius:10px;padding:5px 11px;font-weight:700;font-size:13px;text-decoration:none;color:#111827;white-space:nowrap;';
  $sec = 'border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;max-width:100%;';
  $kart = 'display:block;text-decoration:none;border-radius:14px;padding:14px 16px;border:1.5px solid #e5e7eb;background:#fff;color:#111827;';
  $sayi = 'display:block;font-size:24px;font-weight:900;line-height:1.1;';
  $etk = 'display:block;font-size:12.5px;font-weight:700;color:#6b7280;margin-top:4px;';
  $tl = fn ($v) => number_format((float) $v, 0, ',', '.').' ₺';
  $liste = fn (array $p = []) => route('admin.site-sales.index', $p);
  $odemeRenk = ['odendi' => 'background:#dcfce7;color:#166534;', 'kismi' => 'background:#fef3c7;color:#92400e;', 'bekliyor' => 'background:#fee2e2;color:#991b1b;'];
@endphp
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
  <div>
    <h1 class="text-2xl font-bold">💰 Site Satışları</h1>
    <p style="color:#6b7280;font-size:14px;margin-top:4px;">Kurum ve doktor sitelerinin kim tarafından, hangi paketle, hangi tarihler arasında ve kaça satıldığı.</p>
  </div>
  <a href="{{ route('admin.site-sales.create') }}" style="{{ $btn }}background:#0d6e6a;border-color:#0d6e6a;color:#fff;padding:9px 16px;">+ Satış ekle</a>
</div>
@if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:10px 14px;font-weight:700;margin-bottom:12px;">{{ session('success') }}</div>@endif

<div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:16px;">
  <a href="{{ $liste(['durum' => 'aktif']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#0d6e6a;">{{ $ozet['aktif'] }}</span><span style="{{ $etk }}">Aktif satış</span></a>
  <a href="{{ $liste(['durum' => 'bitiyor']) }}" style="{{ $kart }}{{ $ozet['bitiyor'] ? 'border-color:#fca5a5;background:#fff7f7;' : '' }}"><span style="{{ $sayi }}color:#b91c1c;">{{ $ozet['bitiyor'] }}</span><span style="{{ $etk }}">30 gün içinde bitiyor</span></a>
  <div style="{{ $kart }}"><span style="{{ $sayi }}">{{ $tl($ozet['bu_ay']) }}</span><span style="{{ $etk }}">Bu ay satış</span></div>
  <div style="{{ $kart }}"><span style="{{ $sayi }}">{{ $tl($ozet['bu_yil']) }}</span><span style="{{ $etk }}">Bu yıl satış</span></div>
  <a href="{{ $liste(['odeme' => 'bekliyor']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#92400e;">{{ $tl($ozet['bekleyen']) }}</span><span style="{{ $etk }}">Ödemesi tam alınmamış</span></a>
  <div style="{{ $kart }}"><span style="{{ $sayi }}">{{ $ozet['kurum'] }} / {{ $ozet['doktor'] }}</span><span style="{{ $etk }}">Kurum / doktor satışı</span></div>
</div>

<form method="GET" style="background:#fff;border-radius:14px;padding:14px;display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;border:1px solid #f1f5f9;">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Kurum / doktor adıyla ara..." style="{{ $sec }}flex:1;min-width:160px;padding:8px 12px;">
  <select name="tur" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Kurum + doktor</option>
    @foreach(\App\Models\SiteSale::TURLER as $k => $v)<option value="{{ $k }}" @selected(request('tur') === $k)>{{ $v }}</option>@endforeach
  </select>
  <select name="plan" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Paket: tümü</option>
    @foreach(\App\Models\SiteSale::PLANLAR as $k => $v)<option value="{{ $k }}" @selected(request('plan') === $k)>{{ $v }}</option>@endforeach
  </select>
  <select name="durum" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Durum: tümü</option>
    <option value="aktif" @selected(request('durum') === 'aktif')>Aktif</option>
    <option value="bitiyor" @selected(request('durum') === 'bitiyor')>30 gün içinde bitiyor</option>
    <option value="bitti" @selected(request('durum') === 'bitti')>Süresi bitmiş</option>
  </select>
  <select name="odeme" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Ödeme: tümü</option>
    @foreach(\App\Models\SiteSale::ODEME as $k => $v)<option value="{{ $k }}" @selected(request('odeme') === $k)>{{ $v }}</option>@endforeach
  </select>
  <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;flex-wrap:wrap;">Bitiş
    <input type="date" name="bitis_bas" value="{{ request('bitis_bas') }}" onchange="this.form.submit()" style="{{ $sec }}max-width:150px;"> –
    <input type="date" name="bitis_son" value="{{ request('bitis_son') }}" onchange="this.form.submit()" style="{{ $sec }}max-width:150px;">
  </label>
  <button type="submit" style="{{ $btn }}">Ara</button>
  @if(request()->hasAny(['q', 'tur', 'plan', 'durum', 'odeme', 'bitis_bas', 'bitis_son']))<a href="{{ $liste() }}" style="{{ $btn }}align-self:center;">Temizle</a>@endif
</form>

<div style="font-size:14px;color:#374151;margin-bottom:8px;"><b>{{ number_format($satislar->total(), 0, ',', '.') }}</b> satış · toplam <b>{{ $tl($toplamFiltre) }}</b></div>
<div style="background:#fff;border-radius:14px;border:1px solid #f1f5f9;">
  @forelse($satislar as $s)
    <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:10px 16px;align-items:flex-start;justify-content:space-between;{{ $loop->first ? '' : 'border-top:1px solid #f1f5f9;' }}">
      <div style="min-width:0;flex:1 1 280px;">
        <div style="font-weight:700;color:#111827;overflow-wrap:anywhere;">{{ $s->owner_type === 'doktor' ? '🩺' : '🏠' }} {{ $s->owner_name }}</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;align-items:center;">
          <span style="display:inline-block;font-size:11px;font-weight:800;padding:2px 9px;border-radius:9999px;{{ $s->plan === 'profesyonel' ? 'background:#fdf4e3;color:#8a6a35;' : 'background:#e0f2f1;color:#0d6e6a;' }}">{{ $s->plan === 'profesyonel' ? '★ ' : '' }}{{ \App\Models\SiteSale::PLANLAR[$s->plan] ?? $s->plan }}</span>
          <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;{{ $odemeRenk[$s->odeme] ?? '' }}">{{ \App\Models\SiteSale::ODEME[$s->odeme] ?? $s->odeme }}</span>
          @if($s->aktifMi())<span style="font-size:11px;font-weight:700;color:#166534;">● Aktif</span>@elseif($s->bitis && $s->bitis->isPast())<span style="font-size:11px;font-weight:700;color:#6b7280;">Süresi bitti</span>@endif
        </div>
        <div style="font-size:13px;color:#374151;margin-top:6px;">📅 {{ $s->baslangic->format('d.m.Y') }} – {{ $s->bitis ? $s->bitis->format('d.m.Y') : 'süresiz' }} · 💵 <b>{{ $tl($s->ucret) }}</b>@if($s->marketer) · 👤 {{ $s->marketer->name }}@endif</div>
        @if($s->notlar)<div style="font-size:12.5px;color:#6b7280;margin-top:4px;white-space:pre-line;overflow-wrap:anywhere;">📝 {{ $s->notlar }}</div>@endif
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
        <a href="{{ $s->siteDuzenleUrl() }}" style="{{ $btn }}">🌐 Site</a>
        <a href="{{ route('admin.site-sales.edit', $s) }}" style="{{ $btn }}border-color:#0d6e6a;color:#0d6e6a;">Düzenle</a>
        <form method="POST" action="{{ route('admin.site-sales.destroy', $s) }}" onsubmit="return confirm('Bu satış kaydı silinsin mi? (Site ayarlarına dokunulmaz)')" style="display:inline;margin:0;">@csrf @method('DELETE')<button type="submit" style="{{ $btn }}cursor:pointer;color:#b91c1c;">Sil</button></form>
      </div>
    </div>
  @empty
    <div style="padding:24px;text-align:center;color:#6b7280;">Henüz satış kaydı yok. Kurum Siteleri ya da Doktor Siteleri listesindeki "💰 Satış" düğmesiyle ya da yukarıdan ekleyebilirsiniz.</div>
  @endforelse
</div>
<div style="margin-top:16px;">{{ $satislar->links() }}</div>
@endsection
