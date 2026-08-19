@extends('admin.layout')
@section('title', 'Hesap Silme Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-2">Hesap Silme Talepleri</h1>
<p class="text-sm text-gray-500 mb-6">Aile/kurum yetkilisi kendi panelinden hesabının silinmesini talep etti. Onaylarsanız kişisel verileri (ad/e-posta/telefon) anonimleştirilir, hesap giriş yapamaz hale gelir - geçmiş talep/mesaj gibi başka tarafları ilgilendiren kayıtlar silinmez.</p>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="completed" @selected(request('status')==='completed')>Silinenler</option>
    <option value="rejected" @selected(request('status')==='rejected')>Reddedilenler</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <div class="admin-table-scroll overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Tip</th><th class="p-3">Ad</th><th class="p-3">E-posta</th><th class="p-3">Talep Tarihi</th><th class="p-3">Durum</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($requests as $req)
        @php($user = $req->requestable)
        <tr>
          <td class="p-3 text-gray-500">{{ $req->requestable_type === \App\Models\FamilyUser::class ? 'Aile' : 'Kurum Yetkilisi' }}</td>
          <td class="p-3 font-medium">{{ $user->name ?? '(hesap bulunamadı)' }}</td>
          <td class="p-3 text-gray-500">{{ $user->email ?? '—' }}</td>
          <td class="p-3 text-gray-400">{{ $req->requested_at->format('d.m.Y H:i') }}</td>
          <td class="p-3">
            @if($req->status === 'pending')
              <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">Bekliyor</span>
            @elseif($req->status === 'completed')
              <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Silindi</span>
            @else
              <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Reddedildi</span>
            @endif
          </td>
          <td class="p-3 text-right space-x-2 whitespace-nowrap">
            @if($req->status === 'pending')
              <form method="POST" action="{{ route('admin.account-deletions.approve', $req) }}" class="inline" onsubmit="return confirm('Bu hesabı kalıcı olarak silmek (kişisel verileri anonimleştirmek) istediğinize emin misiniz? Geri alınamaz.');">
                @csrf
                <button class="text-red-600 font-semibold">Sil</button>
              </form>
              <form method="POST" action="{{ route('admin.account-deletions.reject', $req) }}" class="inline" onsubmit="return confirm('Talep reddedilsin mi?');">
                @csrf
                <button class="text-gray-500">Reddet</button>
              </form>
            @else
              <span class="text-xs text-gray-400">{{ $req->processedBy?->name ?? '—' }} &middot; {{ $req->processed_at?->format('d.m.Y H:i') }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
