{{-- 11 Ekim 2026: kullanicinin talebi - "hangi olcude olursa olsun butun
     islemleri gorebilmeliyim, kaydirmak istemiyorum" - eskiden burada
     yatay kaydirmali (admin-table-scroll) bir <table> vardi, AYNI sebeple
     (bkz. admin/facilities/_results.blade.php 1 Ekim 2026 yorumu) flex-wrap
     karta cevrildi - artik kaydirma GEREKMIYOR. --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
  @forelse($facilityUsers as $fu)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900 break-words">{{ $fu->name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ $fu->email }} · {{ $fu->facility?->name ?? 'kurum yok' }} · {{ $fu->created_at->format('d.m.Y H:i') }}</div>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          @if($fu->facility)
            <a href="{{ route('admin.facilities.edit', $fu->facility) }}" class="text-primary text-sm font-semibold whitespace-nowrap">Kurumu Gör →</a>
          @endif
          <form method="POST" action="{{ route('admin.users.facility-users.impersonate', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->name }} adına kurum paneline gireceksiniz. Devam edilsin mi?')">
            @csrf
            <button class="text-sm font-semibold text-blue-600 whitespace-nowrap">Panelde Gör</button>
          </form>
          <form method="POST" action="{{ route('admin.users.facility-users.reset-password', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->name }} için yeni bir geçici şifre oluşturulacak ve e-postayla gönderilecek. E-posta ulaşmazsa bu ekranda göreceğiniz şifreyi kullanıcıya siz iletebilirsiniz. Devam edilsin mi?')">
            @csrf
            <button class="text-sm font-semibold text-blue-600 whitespace-nowrap">Şifre Sıfırla</button>
          </form>
          <form method="POST" action="{{ route('admin.users.facility-users.toggle-status', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->status === 'active' ? 'Bu kurum yetkilisi hesabını askıya almak istediğinize emin misiniz?' : 'Bu kurum yetkilisi hesabını yeniden aktifleştirmek istediğinize emin misiniz?' }}')">
            @csrf
            <button class="text-sm font-semibold whitespace-nowrap {{ $fu->status === 'active' ? 'text-red-600' : 'text-green-700' }}">{{ $fu->status === 'active' ? 'Askıya Al' : 'Aktifleştir' }}</button>
          </form>
          <form method="POST" action="{{ route('admin.users.facility-users.destroy', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->name }} ({{ $fu->email }}) hesabı KALICI OLARAK silinecek, geri alınamaz. Devam edilsin mi?')">
            @csrf @method('DELETE')
            <button class="text-sm font-semibold text-red-800 whitespace-nowrap">Sil</button>
          </form>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap text-xs">
        <span class="text-gray-600">📞 {{ $fu->phone ?: '—' }}</span>
        @if($fu->hasVerifiedEmail())
          <span class="bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">E-posta Doğrulandı</span>
        @else
          <span class="bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-full">E-posta Bekliyor</span>
        @endif
        @if($fu->status === 'active')
          <span class="bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Aktif</span>
        @else
          <span class="bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded-full">Askıya Alınmış</span>
        @endif
      </div>
    </div>
  @empty
    <div class="p-8 text-center text-gray-500">Kayıt bulunamadı.</div>
  @endforelse
</div>
<div class="mt-6">{{ $facilityUsers->links() }}</div>
