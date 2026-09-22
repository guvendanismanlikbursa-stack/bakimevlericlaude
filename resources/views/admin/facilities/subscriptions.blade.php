@extends('admin.layout')
@section('title', 'Abone Kurumlar')

@section('content')
<div class="flex items-center justify-between mb-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">Abone Kurumlar</h1>
    <span class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1">{{ number_format($subscriptions->total(), 0, ',', '.') }} abone</span>
  </div>
  <a href="{{ route('admin.facilities.index') }}" class="text-sm font-semibold text-gray-700 underline">Tüm kurumlara dön</a>
</div>

<p class="text-sm text-gray-500 mb-6">Aylık/yıllık ücret karşılığında telefon/WhatsApp butonları aktif olan ve içerikleri admin tarafından güncellenen kurumlar. Ödeme süresi dolan kurumların butonları otomatik olarak (bir sonraki sayfa yüklemesinde) gizlenir.</p>

<form method="GET" class="mb-4 flex gap-2 flex-wrap">
  <select name="plan" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">Plan: Tümü</option>
    <option value="monthly" @selected(request('plan')==='monthly')>Aylık</option>
    <option value="yearly" @selected(request('plan')==='yearly')>Yıllık</option>
  </select>
  <select name="durum" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">Durum: Tümü</option>
    <option value="aktif" @selected(request('durum')==='aktif')>Aktif</option>
    <option value="suresi_gecti" @selected(request('durum')==='suresi_gecti')>Süresi Geçti</option>
  </select>
  @if(request('plan') || request('durum'))
    <a href="{{ route('admin.facilities.subscriptions.index') }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreleri temizle</a>
  @endif
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <div class="admin-table-scroll overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr>
        <th class="p-3 w-56">Kurum</th>
        <th class="p-3">Şehir</th>
        <th class="p-3">Plan</th>
        <th class="p-3">Başlangıç</th>
        <th class="p-3">Ödeme Bitiş Tarihi</th>
        <th class="p-3">Durum</th>
        <th class="p-3"></th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($subscriptions as $f)
        <tr>
          <td class="p-3 font-medium w-56 break-words">
            <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600">{{ $f->name }}</a>
          </td>
          <td class="p-3">{{ $f->city->name }}</td>
          <td class="p-3">{{ $f->subscription_plan === 'yearly' ? 'Yıllık' : 'Aylık' }}</td>
          <td class="p-3 text-gray-500 whitespace-nowrap">{{ $f->subscription_started_at?->format('d.m.Y') ?? '—' }}</td>
          <td class="p-3 text-gray-700 whitespace-nowrap">{{ $f->subscription_paid_until?->format('d.m.Y') ?? '—' }}</td>
          <td class="p-3">
            @if($f->isActiveSubscriber())
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#15803d;">Aktif</span>
            @else
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Süresi Geçti</span>
            @endif
          </td>
          <td class="p-3 text-right space-x-2 whitespace-nowrap">
            <form method="POST" action="{{ route('admin.facilities.subscription.extend', $f) }}" class="inline" onsubmit="return confirm('{{ $f->name }} için ödeme alındı mı? Bitiş tarihi 1 {{ $f->subscription_plan === 'yearly' ? 'yıl' : 'ay' }} uzatılacak.');">
              @csrf
              <button class="text-green-700 font-semibold">Ödeme Aldım</button>
            </form>
            <form method="POST" action="{{ route('admin.facilities.subscription.cancel', $f) }}" class="inline" onsubmit="return confirm('{{ $f->name }} aboneliği iptal edilsin mi?');">
              @csrf
              <button style="color:#a21caf;">İptal Et</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Henüz abone kurum yok.</td></tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
<div class="mt-6">
  @include('partials.pagination-info', ['paginator' => $subscriptions])
  {{ $subscriptions->links() }}
</div>
@endsection
