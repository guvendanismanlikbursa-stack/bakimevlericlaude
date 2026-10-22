{{-- 22 Ekim 2026: kullanıcının talebi - "Paketler bir bakışta" karşılaştırma
     tablosu (Ekonomik / Standart / Premium). Sütunlar paket sırasına göredir;
     fotoğraf ve video satırları paketteki gerçek ayarlardan gelir. Fiyat
     satırı yalnızca $showPrice true iken görünür.
     Parametreler: $packages (3 paket), $showPrice (bool) --}}
@php
  $showPrice = $showPrice ?? false;
  $cols = $packages->values();
  $ok = '<span style="color:#15803d;font-weight:900;">✓</span>';
  $no = '<span style="color:#cbd5e1;">—</span>';
  $groups = [
      'Profil ve görünürlük' => [
          ['3 sitede kurum profili (bakimevleri.com, bakimevibul.com, bakimeviara.com)', [$ok, $ok, $ok]],
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
@endphp
<div class="overflow-x-auto bg-white" style="border-radius:16px;border:1px solid #e5e7eb;">
  <table class="w-full text-sm" style="border-collapse:collapse;min-width:640px;">
    <thead>
      <tr style="background:#faf5ff;">
        <th class="text-left" style="padding:14px 16px;width:46%;"></th>
        @foreach($cols as $package)
          <th style="padding:14px 10px;text-align:center;vertical-align:bottom;">
            <div style="font-size:22px;">{{ $package->badge_emoji }}</div>
            <div class="font-black text-gray-900">{{ $package->badge_label }}</div>
            <div class="text-xs font-bold" style="color:{{ $package->is_premium ? '#b45309' : '#a21caf' }};">{{ $package->name }}</div>
          </th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($groups as $groupTitle => $rows)
        <tr><td colspan="{{ $cols->count() + 1 }}" class="font-black text-gray-900" style="padding:14px 16px 6px;background:#f9fafb;">{{ $groupTitle }}</td></tr>
        @foreach($rows as [$label, $values])
          <tr style="border-top:1px solid #f1f5f9;">
            <td class="text-gray-700" style="padding:10px 16px;">{{ $label }}</td>
            @foreach($cols as $i => $package)
              <td class="text-gray-800 font-semibold" style="padding:10px;text-align:center;">{!! $values[$i] ?? $no !!}</td>
            @endforeach
          </tr>
        @endforeach
      @endforeach
      @if($showPrice)
        <tr style="border-top:2px solid #e5e7eb;background:#fdf4ff;">
          <td class="font-black text-gray-900" style="padding:14px 16px;">Yıllık üyelik ücreti</td>
          @foreach($cols as $package)
            <td class="font-black" style="padding:14px 10px;text-align:center;color:#701a75;font-size:16px;">{{ number_format($package->price, 0, ',', '.') }}₺</td>
          @endforeach
        </tr>
      @endif
    </tbody>
  </table>
</div>
