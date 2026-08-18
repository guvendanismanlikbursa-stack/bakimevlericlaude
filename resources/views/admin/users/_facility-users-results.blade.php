<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- 18 Agustos 2026: bkz. admin/facilities/_results.blade.php ayni
       tarihli yorum - AJAX anlik filtre sonrasi tablo yeniden render
       edilince islem sutunu dar ekranlarda erisilemez oluyordu. --}}
  <div class="admin-table-scroll overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Kurum</th><th class="p-3">Telefon</th><th class="p-3">E-posta Durumu</th><th class="p-3">Hesap Durumu</th><th class="p-3">Kayıt Tarihi</th><th class="p-3 text-right">İşlem</th></tr></thead>
    <tbody class="divide-y">
      @forelse($facilityUsers as $fu)
        <tr>
          <td class="p-3"><div class="font-semibold">{{ $fu->name }}</div><div class="text-xs text-gray-400">{{ $fu->email }}</div></td>
          <td class="p-3 text-gray-500">{{ $fu->facility?->name ?? '—' }}</td>
          <td class="p-3 text-gray-500">{{ $fu->phone }}</td>
          <td class="p-3">
            @if($fu->hasVerifiedEmail())
              <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Doğrulandı</span>
            @else
              <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Bekliyor</span>
            @endif
          </td>
          <td class="p-3">
            @if($fu->status === 'active')
              <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Aktif</span>
            @else
              <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">Askıya Alınmış</span>
            @endif
          </td>
          <td class="p-3 text-gray-400">{{ $fu->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right space-x-2 whitespace-nowrap">
            @if($fu->facility)
              <a href="{{ route('admin.facilities.edit', $fu->facility) }}" class="text-primary text-xs font-semibold">Kurumu Gör →</a>
            @endif
            <form method="POST" action="{{ route('admin.users.facility-users.impersonate', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->name }} adına kurum paneline gireceksiniz. Devam edilsin mi?')">
              @csrf
              <button class="text-xs font-semibold text-blue-600">Panelde Gör</button>
            </form>
            <form method="POST" action="{{ route('admin.users.facility-users.reset-password', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->name }} için yeni bir geçici şifre oluşturulacak ve e-postayla gönderilecek. E-posta ulaşmazsa bu ekranda göreceğiniz şifreyi kullanıcıya siz iletebilirsiniz. Devam edilsin mi?')">
              @csrf
              <button class="text-xs font-semibold text-blue-600">Şifre Sıfırla</button>
            </form>
            <form method="POST" action="{{ route('admin.users.facility-users.toggle-status', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->status === 'active' ? 'Bu kurum yetkilisi hesabını askıya almak istediğinize emin misiniz?' : 'Bu kurum yetkilisi hesabını yeniden aktifleştirmek istediğinize emin misiniz?' }}')">
              @csrf
              <button class="text-xs font-semibold {{ $fu->status === 'active' ? 'text-red-600' : 'text-green-700' }}">{{ $fu->status === 'active' ? 'Askıya Al' : 'Aktifleştir' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.users.facility-users.destroy', $fu) }}" class="inline" onsubmit="return confirm('{{ $fu->name }} ({{ $fu->email }}) hesabı KALICI OLARAK silinecek, geri alınamaz. Devam edilsin mi?')">
              @csrf @method('DELETE')
              <button class="text-xs font-semibold text-red-800">Sil</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="p-8 text-center text-gray-500">Kayıt bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
<div class="mt-6">{{ $facilityUsers->links() }}</div>
