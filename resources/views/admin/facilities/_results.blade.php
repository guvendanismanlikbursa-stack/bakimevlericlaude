<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  {{-- 18 Agustos 2026: kullanicinin bildirdigi gercek hata - "kurumlar
       sekmesindeki islemler gorunmuyor". Kok neden: admin/layout.blade.php
       SAYFA YUKLENDIGINDE sayfadaki her tabloyu otomatik olarak yatay
       kaydirilabilir bir sarmalayiciya aliyor, AMA bu tablo AJAX anlik
       filtreyle (data-instant-filter) her degistiginde SIFIRDAN
       yeniden render ediliyor - o script tekrar CALISMADIGI icin yeni
       tablo sarmalanmadan kaliyor, dar ekranlarda son sutunlar (Durum +
       islem butonlari: Revize/Panelde Gor/Sil) erisilemez oluyor. Sarma
       artik dogrudan burada (server tarafinda, HER render'da) yapiliyor -
       JS'e hic bagli degil. --}}
  <div class="admin-table-scroll overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3 w-56">Ad</th><th class="p-3">Şehir</th><th class="p-3">Kategori</th><th class="p-3">Profil Kalitesi</th><th class="p-3">Sahiplenme</th><th class="p-3">Bakiye / Hak</th><th class="p-3">Durum</th><th class="p-3">Son Güncelleme</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($facilities as $f)
        <tr>
          {{-- 18 Agustos 2026: kullanicinin bildirdigi gercek hata - Ad
               sutunu asiri genis alan kapliyordu (bkz. .admin-table-scroll
               table { min-width: max-content } - yatay kaydirma duzeltmesi
               ayni zamanda hicbir sutunun sarilmasina izin vermiyordu).
               Sabit bir genislik + break-words ile uzun isimler artik alt
               satira geciyor, sutun tasmiyor. --}}
          <td class="p-3 font-medium w-56 break-words">{{ $f->name }}</td>
          <td class="p-3">{{ $f->city->name }}</td>
          <td class="p-3">
            {{ $f->category->name }}
            @if($f->ownership_type)
              <div class="text-[11px] text-gray-400 mt-0.5">{{ $ownershipTypes[$f->ownership_type] ?? $f->ownership_type }}</div>
            @endif
          </td>
          <td class="p-3">
            @php($quality = $f->profileQuality())
            <div class="flex items-center gap-2">
              <div class="h-2 w-20 rounded-full bg-gray-100 overflow-hidden"><div class="h-full {{ $quality['score'] >= 80 ? 'bg-green-500' : ($quality['score'] >= 55 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $quality['score'] }}%"></div></div>
              <span class="text-xs font-semibold text-gray-700">{{ $quality['score'] }}/100</span>
            </div>
            @if($quality['missing'])<div class="text-[11px] text-gray-400 mt-1">Eksik: {{ implode(', ', array_slice($quality['missing'], 0, 2)) }}</div>@endif
          </td>
          <td class="p-3">
            {{-- 3 Eylul 2026: kullanicinin talebi - admin/kurumlar filtresi
                 artik Ön Kayıt/Anlaşmalı/Sahipli 3 durumunu ayirt ediyor
                 (bkz. FacilityController::filteredQuery() ayni tarihli
                 yorum), bu sutun da ayni 3 durumu gostermeli - eskiden
                 Anlaşmalı kurumlar bile sadece "✓ Sahiplenilmiş" veya
                 "Ön Kayıtlı" gorunuyordu, ayirt edilemiyordu. --}}
            @if($f->is_broker_managed)
              <span class="text-amber-700 text-xs font-semibold">🤝 Anlaşmalı</span>
            @elseif($f->is_claimed)
              <span class="text-green-700 text-xs font-semibold">✓ Sahiplenilmiş</span>
            @else
              <span class="text-gray-400 text-xs">Ön Kayıtlı</span>
            @endif
            @php($mv = $f->ministryVerificationBadge())
            @if($mv)
              <div class="mt-1"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $mv['classes'] }}">{{ $mv['label'] }}</span></div>
            @endif
          </td>
          <td class="p-3">
            @if($f->is_claimed)
              <div class="text-gray-700 font-medium">{{ number_format($f->balance, 2, ',', '.') }} TL</div>
              <div class="text-[11px] text-gray-400">{{ $f->free_quote_credits }} ücretsiz hak</div>
            @else
              <span class="text-gray-300">—</span>
            @endif
          </td>
          <td class="p-3">{{ $f->is_published ? 'Yayında' : 'Taslak' }}</td>
          {{-- 18 Agustos 2026: kullanicinin talebi - kurumlara sirayla
               gorsel eklerken en son hangisine ekledigini takip edebilmek
               icin. Gorsel ekleme, tam duzenleme formunun (isim/adres/vb.
               ile ayni PUT istegi) bir parcasi oldugundan facilities.
               updated_at zaten bu ani dogru yansitir. --}}
          <td class="p-3 text-gray-500 whitespace-nowrap">{{ $f->updated_at?->format('d.m.Y H:i') ?? '—' }}</td>
          <td class="p-3 text-right space-x-2">
            <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600">Revize</a>
            @if($f->is_claimed && $f->facilityUsers->isNotEmpty())
              <form method="POST" action="{{ route('admin.users.facility-users.impersonate', $f->facilityUsers->first()) }}" class="inline" onsubmit="return confirm('{{ $f->facilityUsers->first()->name }} adına kurum paneline gireceksiniz. Devam edilsin mi?')">
                @csrf
                <button class="text-purple-600">Panelde Gör</button>
              </form>
            @endif
            @if($f->is_claimed)
              <form method="POST" action="{{ route('admin.facilities.revert', $f) }}" class="inline" onsubmit="return confirm('Bu kurumu ön kayıtlı hale getirmek istediğinize emin misiniz?');">
                @csrf
                <button class="text-orange-600">Ön Kayıt</button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.facilities.destroy', $f) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600">Sil</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="9">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
<div class="mt-6">
  @include('partials.pagination-info', ['paginator' => $facilities])
  {{ $facilities->links() }}
</div>
