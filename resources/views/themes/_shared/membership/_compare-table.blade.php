{{-- 22 Ekim 2026: kullanıcının talebi - "Paketler bir bakışta". Masaüstünde
     tablo; mobilde (yana kaydırma olmasın diye) her özellik bir satır kartı,
     altında üç paketin değeri yan yana. Fotoğraf, video ve site sayısı
     paketin gerçek ayarlarından gelir. Fiyat satırı yalnızca $showPrice iken.
     Parametreler: $packages (3 paket), $showPrice (bool) --}}
@include('themes._shared.membership._styles')
@php
  $showPrice = $showPrice ?? false;
  $cols = $packages->values();
  $ok = '<span class="ih-ok">✓</span>';
  $no = '<span class="ih-no">—</span>';
  $groups = [
      'Profil ve görünürlük' => [
          ['Yayınlandığı siteler (bakimevleri.com, bakimevibul.com, bakimeviara.com + kendi internet siteniz)', $cols->map(fn ($p) => $p->includesWebsite() ? '4 site' : '3 site')->all()],
          ['Tek kurum paneli, telefondan yönetim', [$ok, $ok, $ok]],
          ['"Doğrulanmış Üye Kurum" rozeti', [$ok, $ok, $ok]],
          ['Profil fotoğrafı', $cols->map(fn ($p) => $p->photo_limit ? e($p->photo_limit).' görsel' : 'Sınırsız')->all()],
          ['Tanıtım videosu', $cols->map(fn ($p) => $p->can_upload_video ? $ok : $no)->all()],
          ['Google yorumlarının profilde otomatik gösterimi', [$ok, $ok, $ok]],
          ['Boş yer, hizmet, fiyat ve yemek menüsü bilgisi', [$ok, $ok, $ok]],
          ['Telefon ve WhatsApp düğmeleri', [$ok, $ok, $ok]],
          ['Arama ve il sayfalarında öne çıkma', [$no, 'Öne çıkan kart', 'Vitrin + üst sıra']],
      ],
      'Aile talepleri' => [
          ['Ailelerden gelen fiyat ve ziyaret talepleri', [$ok, $ok, $ok]],
          ['Sınırsız ücretsiz teklif verme', [$ok, $ok, $ok]],
          ['Bölgenizde kurumunuz dışına gelen taleplerin yönlendirilmesi', ['Bildirim', 'Öncelikli', 'İlk sırada']],
          ['Anlık bildirim (panel + telefon)', [$ok, $ok, $ok]],
          ['Aileyle mesajlaşma ve yorumlara yanıt', [$ok, $ok, $ok]],
      ],
      'Dijital varlık' => [
          ['Kurum adına internet sitesi (alan adı, barındırma, SSL dahil)', [$no, $ok, $ok]],
          ['Siteden gelen taleplerin panele düşmesi', [$no, $ok, $ok]],
          ['360° sanal tur (çekim ekibimizle)', [$no, $no, $ok]],
      ],
      'Raporlama ve destek' => [
          ['Görüntülenme ve talep istatistikleri', ['Temel', 'Aylık rapor', 'Aylık detaylı rapor']],
          ['Destek', ['Panel içi destek', 'Öncelikli destek', 'Kişisel kurum temsilcisi']],
      ],
  ];
  if ($showPrice) {
      $groups['Ücret'] = [['Yıllık üyelik ücreti', $cols->map(fn ($p) => '<b style="color:#701a75;">'.number_format($p->price, 0, ',', '.').'₺</b>')->all()]];
  }
@endphp

{{-- Masaüstü --}}
<div class="ih-cmp-table ih-card" style="padding:0;overflow:hidden;">
  <table>
    <thead>
      <tr style="background:#faf5ff;">
        <th style="text-align:left;border-top:0;"></th>
        @foreach($cols as $package)
          <th style="border-top:0;text-align:center;vertical-align:bottom;padding:18px 10px;">
            <div style="font-size:24px;line-height:1;">{{ $package->badge_emoji }}</div>
            <div style="font-weight:900;color:#111827;margin-top:6px;">{{ $package->badge_label }}</div>
            <div style="font-size:12px;font-weight:800;color:{{ $package->is_premium ? '#b45309' : '#a21caf' }};">{{ $package->name }}</div>
          </th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($groups as $groupTitle => $rows)
        <tr class="ih-cmp-group"><td colspan="{{ $cols->count() + 1 }}">{{ $groupTitle }}</td></tr>
        @foreach($rows as [$label, $values])
          <tr>
            <td style="color:#374151;">{{ $label }}</td>
            @foreach($cols as $i => $package)
              <td class="v">{!! $values[$i] ?? $no !!}</td>
            @endforeach
          </tr>
        @endforeach
      @endforeach
    </tbody>
  </table>
</div>

{{-- Mobil --}}
<div class="ih-cmp-list">
  <p style="font-size:12px;color:#6b7280;text-align:center;margin:0 0 12px;">
    @foreach($cols as $package){{ $package->badge_emoji }} {{ $package->name }}@if(! $loop->last) &nbsp;·&nbsp; @endif @endforeach
  </p>

  @foreach($groups as $groupTitle => $rows)
    <div class="ih-card" style="margin-bottom:14px;padding:18px;">
      <div style="font-weight:900;color:#111827;margin-bottom:6px;">{{ $groupTitle }}</div>
      @foreach($rows as [$label, $values])
        <div class="ih-cmp-row">
          <div class="ih-cmp-label">{{ $label }}</div>
          <div class="ih-cmp-vals">
            @foreach($cols as $i => $package)
              <div class="ih-cmp-val"><i>{{ $package->badge_emoji }}</i>{!! $values[$i] ?? $no !!}</div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  @endforeach
</div>
