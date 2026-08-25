@extends('admin.layout')
@section('title', 'Aracılık Yönlendirmeleri')

@section('content')
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Aracılık Yönlendirmeleri</h1>
    <p class="text-sm text-gray-500 mt-1">Yönlendirdiğiniz ailelerin hangi kuruma, ne aşamada ve ne komisyonla olduğunu takip edin.</p>
  </div>
  <a href="{{ route('admin.broker.facilities') }}" class="bg-gray-900 text-white rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap">Anlaşmalı Kurumlar</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-4 mb-5">
  <div class="text-sm text-gray-500">Ödeme bekleyen toplam komisyon: <span class="font-bold text-gray-900">{{ number_format($pendingFeeTotal, 2, ',', '.') }} TL</span></div>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  @foreach($groups as $key => $def)
    <a href="{{ route('admin.broker.referrals', ['group' => $key]) }}"
       class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $group === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
      <span>{{ $def['title'] }}</span>
      <span class="text-xs {{ $group === $key ? 'text-gray-300' : 'text-gray-400' }}">{{ $groupCounts[$key] ?? 0 }}</span>
    </a>
  @endforeach
</div>

@if($managedFacilities->isEmpty())
  <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4 mb-5">
    Önce en az bir kurumu <a href="{{ route('admin.broker.facilities') }}" class="underline font-bold">Anlaşmalı Kurumlar</a> listesine eklemelisiniz, sonra buradan yönlendirme ekleyebilirsiniz.
  </div>
@else
  <details class="bg-white rounded-xl shadow-sm mb-5">
    <summary class="cursor-pointer list-none p-4 font-bold text-sm flex items-center justify-between">
      <span>+ Yeni Yönlendirme Ekle</span>
    </summary>
    <form method="POST" action="{{ route('admin.broker.referrals.store') }}" class="p-4 pt-0 grid grid-cols-1 md:grid-cols-3 gap-3">
      @csrf
      <select name="facility_id" required class="border rounded-lg px-3 py-2 text-sm">
        <option value="">Kurum seçin</option>
        @foreach($managedFacilities as $f)
          <option value="{{ $f->id }}">{{ $f->name }}</option>
        @endforeach
      </select>
      <input type="text" name="family_name" required placeholder="Aile adı" class="border rounded-lg px-3 py-2 text-sm">
      <input type="text" name="family_phone" placeholder="Telefon (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm">
      <input type="date" name="referred_at" required value="{{ now()->toDateString() }}" class="border rounded-lg px-3 py-2 text-sm">
      <input type="number" step="0.01" min="0" name="fee_amount" placeholder="Beklenen komisyon (TL)" class="border rounded-lg px-3 py-2 text-sm">
      <input type="text" name="notes" placeholder="Not (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm">
      <button type="submit" class="bg-green-600 text-white rounded-lg px-4 py-2 text-sm font-bold md:col-span-3">Ekle</button>
    </form>
  </details>
@endif

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr>
        <th class="p-3">Aile</th>
        <th class="p-3">Kurum</th>
        <th class="p-3">Yönlendirme Tarihi</th>
        <th class="p-3">Aşama</th>
        <th class="p-3">Komisyon</th>
        <th class="p-3">Not</th>
      </tr>
    </thead>
    <tbody class="divide-y align-top">
      @forelse($referrals as $referral)
        <tr>
          <td class="p-3">
            <div class="font-medium">{{ $referral->family_name }}</div>
            @if($referral->family_phone)<div class="text-xs text-gray-400">{{ $referral->family_phone }}</div>@endif
          </td>
          <td class="p-3">{{ $referral->facility->name ?? '(silinmiş kurum)' }}</td>
          <td class="p-3 text-gray-500">{{ $referral->referred_at->format('d.m.Y') }}</td>
          <td class="p-3">
            <form method="POST" action="{{ route('admin.broker.referrals.update', $referral) }}" class="flex flex-col gap-1.5">
              @csrf
              <select name="status" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-xs">
                @foreach($statusLabels as $key => $label)
                  <option value="{{ $key }}" @selected($referral->status === $key)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="flex items-center gap-1.5">
                <select name="fee_status" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-xs">
                  <option value="bekliyor" @selected($referral->fee_status === 'bekliyor')>Ödeme bekliyor</option>
                  <option value="odendi" @selected($referral->fee_status === 'odendi')>Ödendi</option>
                </select>
              </div>
              <input type="hidden" name="fee_amount" value="{{ $referral->fee_amount }}">
              <input type="hidden" name="placed_at" value="{{ optional($referral->placed_at)->toDateString() }}">
              <input type="hidden" name="notes" value="{{ $referral->notes }}">
            </form>
          </td>
          <td class="p-3">
            @if($referral->fee_amount)
              <div class="font-bold">{{ number_format($referral->fee_amount, 2, ',', '.') }} TL</div>
              <div class="text-xs {{ $referral->fee_status === 'odendi' ? 'text-green-600' : 'text-amber-600' }}">{{ $referral->fee_status === 'odendi' ? 'Ödendi' : 'Bekliyor' }}</div>
            @else
              <span class="text-gray-400 text-xs">-</span>
            @endif
          </td>
          <td class="p-3 text-gray-500 max-w-[200px]">{{ $referral->notes ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="p-6 text-center text-gray-400">Bu filtrede yönlendirme yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $referrals->links() }}</div>
@endsection
