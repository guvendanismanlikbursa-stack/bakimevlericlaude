{{-- 18 Ekim 2026: kullanıcının talebi - İyilik Hareketi üyelik paketleri
     (yalnızca membership_live() iken controller'dan dolu gelir). Fiyat, süre,
     rozet ve metinlerin tamamı admin "Paketler" ekranından yönetilir. --}}
<div class="mb-10">
  <div class="rounded-2xl p-5 mb-5" style="background:linear-gradient(135deg,#fdf4ff,#fef3c7);">
    <div class="text-xs font-black uppercase tracking-wide" style="color:#a21caf;">İyilik Hareketi Üyeliği</div>
    <h2 class="text-xl font-black text-gray-900 mt-1">Kurumunuzu büyütürken bir yaşlımızın bakımına destek olun</h2>
    <p class="text-sm text-gray-700 mt-2">Üyelik süresince <strong>teklifleriniz ücretsiz ve sınırsızdır</strong>, kurum sayfanızda üyelik rozetiniz görünür. Üyelik gelirinin bir bölümü Güven Bakım Hizmetleri iş birliğiyle Bursa Şehir Hastanesi'ndeki kimsesiz yaşlı hastaların bakımına, bir bölümü de kurumunuzun tanıtımına harcanır. <a href="{{ brand_route('pages.membership') }}" class="underline font-semibold">Ayrıntılı bilgi</a></p>
    @if($currentMembership)
      <p class="mt-3 text-sm font-bold text-green-800">✓ Aktif üyeliğiniz: {{ $currentMembership->badge_emoji }} {{ $currentMembership->badge_label }} — bitiş {{ $user->facility->subscription_paid_until?->format('d.m.Y') }}. Aşağıdan erken yenileyebilirsiniz; kalan günleriniz kaybolmaz.</p>
    @endif
  </div>

  <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach($membershipPackages as $package)
      <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col border-2 {{ $package->is_premium ? 'border-amber-300' : '' }}" style="{{ $package->is_premium ? '' : 'border-color:transparent;' }}">
        <div class="text-xs font-black px-2 py-1 rounded-full self-start" style="background:#fae8ff;color:#a21caf;">{{ $package->badge_emoji }} {{ $package->badge_label }}</div>
        <div class="font-bold text-lg mt-3">{{ $package->name }}</div>
        <div class="text-2xl font-black text-primary my-1">{{ number_format($package->price, 0, ',', '.') }}₺</div>
        @if($package->free_months_label)<div class="text-xs font-bold text-green-700 mb-2">🎁 {{ $package->free_months_label }}</div>@endif
        @if($package->tagline)<p class="text-sm text-gray-600 mb-3">{{ $package->tagline }}</p>@endif
        <ul class="text-sm text-gray-600 space-y-1 mb-4">
          <li>✓ {{ $package->months }} ay boyunca ücretsiz ve sınırsız teklif</li>
          <li>✓ Sahipli kurumların tüm panel özellikleri</li>
          @foreach($package->featureList() as $feature)<li>✓ {{ $feature }}</li>@endforeach
        </ul>
        <form method="POST" action="{{ brand_route('facility.packages.store', $package) }}" enctype="multipart/form-data" class="mt-auto space-y-2" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').textContent='Gönderiliyor...';">
          @csrf
          <label for="membership-receipt-{{ $package->id }}" class="text-xs text-gray-500 block">Havale/EFT dekontu</label>
          <input type="file" id="membership-receipt-{{ $package->id }}" name="receipt" accept=".jpg,.jpeg,.png,.webp" required class="border rounded-lg px-3 py-2 w-full text-sm">
          <button type="submit" class="btn-primary w-full py-2 rounded-lg font-semibold text-sm">Dekont Yükle, Üye Ol</button>
        </form>
      </div>
    @endforeach
  </div>
  <p class="text-xs text-gray-500 mt-3">Ödeme bilgileri için <a href="{{ brand_route('facility.wallet.index') }}" class="underline">Bakiyem</a> sayfasındaki banka hesabını kullanabilirsiniz. Dekontunuz onaylandığında üyeliğiniz başlar ve size bildirim gelir.</p>

  <h2 class="font-bold mt-10 mb-3">Bakiye Paketleri</h2>
  <p class="text-sm text-gray-500 mb-3">Üye olmadan teklif başına ödeme yapmak isteyen kurumlar için.</p>
</div>
