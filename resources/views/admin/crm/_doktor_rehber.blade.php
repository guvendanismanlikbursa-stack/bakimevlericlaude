{{-- 30 Ekim 2026: kullanıcının talebi - doktor görüşme rehberi; yalnızca admin CRM'inde
     (pazarlamacı paneline eklenmedi). Metni admin "Düzenle" ile kendisi değiştirir
     (Setting crm_doktor_rehber, boşsa CrmController::varsayilanRehber()).
     Biçim: "## " kutu başlığı, "! " uyarı notu, "- " madde, **kalın**;
     {temel_fiyat} ve {aylik_fiyat} paket fiyatından doldurulur. --}}
@php
  $temelFiyat = \App\Http\Controllers\Public\HekimPaketController::fiyat('temel');
  $temelSayi = (int) preg_replace('/\D+/', '', explode('₺', $temelFiyat)[0]);
  $aylik = $temelSayi > 0 ? number_format((int) ceil($temelSayi / 12 / 50) * 50, 0, ',', '.').' ₺' : '';
  $hamMetin = \App\Http\Controllers\Admin\CrmController::rehberMetni();
  $metin = strtr($hamMetin, ['{temel_fiyat}' => $temelFiyat, '{aylik_fiyat}' => $aylik]);
  $kalin = fn ($s) => preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', e($s));
  // Satırları kutulara ayır.
  $kutular = [];
  $simdiki = null;
  foreach (preg_split('/\r\n|\r|\n/', $metin) as $satir) {
      $t = trim($satir);
      if (str_starts_with($t, '## ')) {
          if ($simdiki) { $kutular[] = $simdiki; }
          $simdiki = ['baslik' => trim(substr($t, 3)), 'satirlar' => []];
          continue;
      }
      if ($t === '') { continue; }
      $simdiki ??= ['baslik' => '', 'satirlar' => []];
      $simdiki['satirlar'][] = $t;
  }
  if ($simdiki) { $kutular[] = $simdiki; }
  $adim = 'background:#f8fafc;border-left:4px solid #0d6e6a;border-radius:10px;padding:10px 14px;';
  $baslikStil = 'display:block;font-size:12px;font-weight:900;letter-spacing:.06em;text-transform:uppercase;color:#0d6e6a;margin-bottom:4px;';
  $soz = 'font-size:14.5px;color:#111827;line-height:1.55;';
  $not = 'display:block;font-size:12.5px;color:#92400e;margin-top:4px;font-weight:700;';
@endphp
<details style="background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:14px 16px;margin-bottom:14px;border:1.5px solid #cfe7e4;" @if($errors->has('rehber') || session('rehber_kaydedildi')) open @endif>
  <summary style="cursor:pointer;font-weight:900;font-size:15px;color:#0d6e6a;">📋 Görüşme rehberi — doktorla konuşurken açık tutun</summary>
  <div style="display:grid;gap:10px;margin-top:12px;">
    @foreach($kutular as $kutu)
      <div style="{{ $adim }}">
        @if($kutu['baslik'] !== '')<span style="{{ $baslikStil }}">{{ $kutu['baslik'] }}</span>@endif
        @foreach($kutu['satirlar'] as $s)
          @if(str_starts_with($s, '! '))
            <span style="{{ $not }}">{!! $kalin(substr($s, 2)) !!}</span>
          @elseif(str_starts_with($s, '- '))
            <div style="{{ $soz }}padding-left:14px;text-indent:-12px;margin-top:4px;">• {!! $kalin(substr($s, 2)) !!}</div>
          @else
            <div style="{{ $soz }}">{!! $kalin($s) !!}</div>
          @endif
        @endforeach
      </div>
    @endforeach

    <details style="margin-top:4px;" @if($errors->has('rehber')) open @endif>
      <summary style="cursor:pointer;font-size:13.5px;font-weight:900;color:#374151;">✏️ Rehberi düzenle</summary>
      <form method="POST" action="{{ route('admin.crm.guide') }}" style="margin-top:10px;display:grid;gap:8px;">
        @csrf
        <div style="font-size:12.5px;color:#4b5563;background:#f3f4f6;border-radius:10px;padding:10px 12px;line-height:1.6;">
          <b>Yazım kuralları:</b> <code>## Başlık</code> yeni kutu açar · <code>! metin</code> sarı uyarı notu · <code>- metin</code> madde · <code>**kalın**</code> kalın yazı ·
          <code>{temel_fiyat}</code> ve <code>{aylik_fiyat}</code> paket fiyatından kendiliğinden dolar.
        </div>
        <textarea name="rehber" rows="22" style="width:100%;border:1.5px solid #d6dde0;border-radius:10px;padding:10px 12px;font:13.5px/1.55 ui-monospace,Consolas,monospace;color:#111827;">{{ old('rehber', $hamMetin) }}</textarea>
        @error('rehber')<div style="color:#b91c1c;font-size:13px;font-weight:700;">{{ $message }}</div>@enderror
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button type="submit" style="background:#0d6e6a;color:#fff;font-weight:800;border:0;border-radius:10px;padding:9px 20px;cursor:pointer;">Kaydet</button>
          <button type="submit" name="varsayilan" value="1" onclick="return confirm('Rehber ilk haline döndürülsün mü? Yaptığınız değişiklikler silinir.')" style="background:#fff;color:#6b7280;font-weight:800;border:1.5px solid #d6dde0;border-radius:10px;padding:9px 16px;cursor:pointer;">İlk haline döndür</button>
        </div>
      </form>
    </details>
  </div>
</details>
