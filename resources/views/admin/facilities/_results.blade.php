@php($membershipPackagesForSelect = \App\Models\SubscriptionPackage::membership()->active()->orderBy('sort_order')->get())
<div class="bg-white rounded-xl shadow-sm">
  {{-- 1 Ekim 2026: kullanicinin talebi - "hangi olcude olursa olsun (tam
       ekran, daraltilmis, mobil) butun islemleri gorebilmeliyim, saga sola
       kaydirmak istemiyorum". Eskiden bu bir <table> idi - 10 sutun +
       coklu buton genis ekranda bile tasiyor, admin-table-scroll sarmasi
       yatay kaydirmayi GEREKTIRIYORDU. Artik her kurum, icerigi flex-wrap
       ile satir kiran bir "kart" - ekran daraldikca yana tasmak yerine
       asagi dogru uzuyor. --}}
  <div class="divide-y divide-gray-100">
    @forelse($facilities as $f)
      <div class="p-4 flex flex-col gap-3">
        {{-- Ust satir: isim + sehir/kategori + islem butonlari (kendi icinde wrap) --}}
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div class="min-w-0">
            <div class="font-bold text-gray-900 break-words">{{ $f->name }}</div>
            <div class="text-xs text-gray-500 mt-0.5">
              {{ $f->city->name }} · {{ $f->category->name }}
              @if($f->ownership_type)
                · {{ $ownershipTypes[$f->ownership_type] ?? $f->ownership_type }}
              @endif
            </div>
            @if($f->website_status)<div class="mt-1">@include('partials.website-status-badge', ['facility' => $f])</div>@endif
          </div>
          <div class="flex items-center gap-2 flex-wrap justify-end">
            <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600 text-sm font-semibold whitespace-nowrap">Revize</a>
            @if($f->is_claimed || $f->is_broker_managed)
              <form method="POST" action="{{ route('admin.facilities.impersonate', $f) }}" class="inline" onsubmit="return confirm('{{ $f->name }} kurum paneline gireceksiniz. Devam edilsin mi?')">
                @csrf
                <button class="text-purple-600 text-sm font-semibold whitespace-nowrap">Panelde Gör</button>
              </form>
            @endif
            @if($f->is_claimed)
              <form method="POST" action="{{ route('admin.facilities.revert', $f) }}" class="inline" onsubmit="return confirm('Bu kurumun sahiplenmesini geri almak istediğinize emin misiniz?');">
                @csrf
                <button class="text-orange-600 text-sm font-semibold whitespace-nowrap">Sahiplenmeyi Geri Al</button>
              </form>
              <form method="POST" action="{{ route('admin.facilities.reset-registration', $f) }}" class="inline" onsubmit="return confirm('Bu kurumun sahiplenme kaydı SİLİNECEK (kurum yetkilisi hesabı kalıcı olarak silinir, bonus geri alınır) ve kurum yeniden sahiplenilebilir hale gelecek. Emin misiniz?');">
                @csrf
                <button class="text-red-700 text-sm font-semibold whitespace-nowrap" title="Yanlış bilgiyle sahiplenildiyse, hesabı silip sıfırdan sahiplendirmek için">Kaydı Sıfırla</button>
              </form>
            @endif
            @if($f->is_subscribed)
              <form method="POST" action="{{ route('admin.facilities.subscription.cancel', $f) }}" class="inline" onsubmit="return confirm('{{ $f->name }} aboneliği iptal edilsin mi?');">
                @csrf
                <button class="text-sm font-semibold whitespace-nowrap" style="color:#a21caf;">Aboneliği İptal Et</button>
              </form>
            @else
              <form method="POST" action="{{ route('admin.facilities.subscription.enable', $f) }}" class="inline-flex items-center gap-1 flex-wrap" onsubmit="return confirm('{{ $f->name }} abone yapılsın mı?');">
                @csrf
                <select name="plan" class="text-xs border rounded" onclick="event.stopPropagation()">
                  <option value="monthly">Aylık</option>
                  <option value="semiannual">6 Aylık</option>
                  <option value="yearly">Yıllık</option>
                </select>
                @if($membershipPackagesForSelect->isNotEmpty())
                  <select name="package_id" class="text-xs border rounded" onclick="event.stopPropagation()" title="İyilik Hareketi üyelik paketi seçilirse plan ve ücret paketten gelir">
                    <option value="">Paket yok</option>
                    @foreach($membershipPackagesForSelect as $mp)<option value="{{ $mp->id }}">{{ $mp->badge_emoji }} {{ $mp->badge_label }}</option>@endforeach
                  </select>
                @endif
                <input type="number" name="amount" step="0.01" min="0" placeholder="TL" class="text-xs border rounded w-16" onclick="event.stopPropagation()">
                <button class="text-sm font-semibold whitespace-nowrap" style="color:#a21caf;">Abone Yap</button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.facilities.destroy', $f) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button>
            </form>
          </div>
        </div>

        {{-- Alt satir: durum rozetleri + kalite + bakiye + goruntulenme + guncelleme - hepsi wrap --}}
        <div class="flex items-center gap-4 flex-wrap text-xs text-gray-500">
          <div class="flex items-center gap-2">
            @php($quality = $f->profileQuality())
            <div class="h-2 w-20 rounded-full bg-gray-100 overflow-hidden"><div class="h-full {{ $quality['score'] >= 80 ? 'bg-green-500' : ($quality['score'] >= 55 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $quality['score'] }}%"></div></div>
            <span class="font-semibold text-gray-700">{{ $quality['score'] }}/100</span>
            @if($quality['missing'])<span>· Eksik: {{ implode(', ', array_slice($quality['missing'], 0, 2)) }}</span>@endif
          </div>

          @if($f->is_broker_managed)
            <span class="text-amber-700 font-semibold">🤝 Anlaşmalı</span>
          @elseif($f->is_claimed)
            <span class="text-green-700 font-semibold">✓ Sahiplenilmiş</span>
          @else
            <span>Sahiplenilmemiş</span>
          @endif

          @if($f->is_subscribed)
            <span class="font-semibold px-2 py-0.5 rounded-full" style="background:#fae8ff;color:#a21caf;">
              ★ Abone{{ $f->isActiveSubscriber() ? '' : ($f->subscription_paid_until ? ' (süresi geçti)' : ' (ödeme bekliyor)') }}{{ $f->subscription_amount ? ' · '.number_format($f->subscription_amount, 0, ',', '.').' TL' : '' }}
            </span>
          @endif

          @php($mv = $f->ministryVerificationBadge())
          @if($mv)
            <span class="font-semibold px-2 py-0.5 rounded-full {{ $mv['classes'] }}">{{ $mv['label'] }}</span>
          @endif

          @if(! $f->lat)
            <span class="font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">📍 Konum eksik</span>
          @endif

          @if($f->is_claimed)
            <span>{{ number_format($f->balance, 2, ',', '.') }} TL ({{ $f->free_quote_credits }} ücretsiz hak)</span>
          @endif

          <span>{{ number_format($f->monthlyDisplayViewsCount(), 0, ',', '.') }}/ay görüntülenme (Gerçek: {{ number_format(max(0, $f->views_count - $f->views_count_at_month_start), 0, ',', '.') }} · Eklenen: {{ number_format($f->boosted_views_count, 0, ',', '.') }} · Toplam: {{ number_format($f->views_count, 0, ',', '.') }})</span>

          <span>{{ $f->is_published ? 'Yayında' : 'Taslak' }}</span>
          <span>Güncelleme: {{ $f->updated_at?->format('d.m.Y H:i') ?? '—' }}</span>
        </div>
      </div>
    @empty
      <div class="p-4 text-gray-400">Kayıt yok.</div>
    @endforelse
  </div>
</div>
<div class="mt-6">
  @include('partials.pagination-info', ['paginator' => $facilities])
  {{ $facilities->links() }}
</div>
