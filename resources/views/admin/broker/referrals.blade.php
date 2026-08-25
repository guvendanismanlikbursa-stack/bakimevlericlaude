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
    <form method="POST" action="{{ route('admin.broker.referrals.store') }}" class="p-4 pt-0">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <select name="facility_id" required class="border rounded-lg px-3 py-2 text-sm">
          <option value="">Kurum seçin</option>
          @foreach($managedFacilities as $f)
            <option value="{{ $f->id }}">{{ $f->name }}</option>
          @endforeach
        </select>
        <input type="date" name="referred_at" required value="{{ now()->toDateString() }}" title="Yönlendirme tarihi" class="border rounded-lg px-3 py-2 text-sm">
        <input type="number" step="0.01" min="0" name="fee_amount" placeholder="Beklenen komisyon (TL)" class="border rounded-lg px-3 py-2 text-sm">
      </div>

      <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-4 mb-2">Aile / İletişim Kişisi</div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <input type="text" name="family_name" required placeholder="Aile / iletişim kişisi adı" class="border rounded-lg px-3 py-2 text-sm">
        <input type="text" name="family_phone" placeholder="Telefon (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm">
      </div>

      {{--
        25 Agustos 2026: kullanicinin talebi - ucretsiz ziyaret hizmeti
        (Guven Bakim Hizmetleri sehir hastanesi ekibi tarafindan) icin KIME
        gidilecegi belli olmali. Aile iletisim kisisiyle hasta/sakin AYRI
        kisiler oldugu icin ayri bir bolum.
      --}}
      <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-4 mb-2">Hasta / Sakin Bilgileri (ücretsiz ziyaret için)</div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="patient_name" placeholder="Hasta adı soyadı" class="border rounded-lg px-3 py-2 text-sm md:col-span-2">
        <input type="number" min="0" max="130" name="patient_age" placeholder="Yaş" class="border rounded-lg px-3 py-2 text-sm">
        <select name="patient_mobility" class="border rounded-lg px-3 py-2 text-sm">
          <option value="">Hareket durumu</option>
          @foreach($mobilityLabels as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex items-center gap-4 mt-3 text-sm">
        <span class="text-gray-500">Ücretsiz ziyaret istiyor mu?</span>
        <label class="flex items-center gap-1.5"><input type="radio" name="wants_visit" value="1"> Evet</label>
        <label class="flex items-center gap-1.5"><input type="radio" name="wants_visit" value="0"> Hayır</label>
        <label class="flex items-center gap-1.5"><input type="radio" name="wants_visit" checked> Henüz sorulmadı</label>
      </div>

      <input type="text" name="notes" placeholder="Not (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm w-full mt-3">
      <button type="submit" class="bg-green-600 text-white rounded-lg px-4 py-2 text-sm font-bold mt-3">Ekle</button>
    </form>
  </details>
@endif

<div class="space-y-3">
  @forelse($referrals as $referral)
    <form id="ref-{{ $referral->id }}" method="POST" action="{{ route('admin.broker.referrals.update', $referral) }}">@csrf</form>
    <div class="bg-white rounded-xl shadow-sm p-4">
      <div class="flex flex-wrap items-start justify-between gap-3 mb-3 pb-3 border-b">
        <div>
          <div class="font-bold text-gray-950">{{ $referral->family_name }}</div>
          @if($referral->family_phone)<div class="text-xs text-gray-400">{{ $referral->family_phone }}</div>@endif
          <div class="text-sm text-gray-500 mt-1">{{ $referral->facility->name ?? '(silinmiş kurum)' }}</div>
        </div>
        <div class="text-right text-xs text-gray-400">
          Yönlendirme: {{ $referral->referred_at->format('d.m.Y') }}
          <label class="block mt-1">Yerleşme tarihi</label>
          <input type="date" name="placed_at" form="ref-{{ $referral->id }}" value="{{ optional($referral->placed_at)->toDateString() }}" class="border rounded-lg px-2 py-1 text-xs mt-0.5">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Hasta adı</label>
          <input type="text" name="patient_name" form="ref-{{ $referral->id }}" value="{{ $referral->patient_name }}" placeholder="Hasta adı soyadı" class="border rounded-lg px-2 py-1.5 text-sm w-full">
        </div>
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Yaş</label>
          <input type="number" min="0" max="130" name="patient_age" form="ref-{{ $referral->id }}" value="{{ $referral->patient_age }}" class="border rounded-lg px-2 py-1.5 text-sm w-full">
        </div>
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Hareket durumu</label>
          <select name="patient_mobility" form="ref-{{ $referral->id }}" class="border rounded-lg px-2 py-1.5 text-sm w-full">
            <option value="">-</option>
            @foreach($mobilityLabels as $key => $label)
              <option value="{{ $key }}" @selected($referral->patient_mobility === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Ücretsiz ziyaret</label>
          <select name="wants_visit" form="ref-{{ $referral->id }}" class="border rounded-lg px-2 py-1.5 text-sm w-full">
            <option value="" @selected(is_null($referral->wants_visit))>Henüz sorulmadı</option>
            <option value="1" @selected($referral->wants_visit === true)>İstiyor</option>
            <option value="0" @selected($referral->wants_visit === false)>İstemiyor</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Aşama</label>
          <select name="status" form="ref-{{ $referral->id }}" class="border rounded-lg px-2 py-1.5 text-sm w-full">
            @foreach($statusLabels as $key => $label)
              <option value="{{ $key }}" @selected($referral->status === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Komisyon (TL)</label>
          <input type="number" step="0.01" min="0" name="fee_amount" form="ref-{{ $referral->id }}" value="{{ $referral->fee_amount }}" placeholder="Tutar" class="border rounded-lg px-2 py-1.5 text-sm w-full">
        </div>
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Ödeme durumu</label>
          <select name="fee_status" form="ref-{{ $referral->id }}" class="border rounded-lg px-2 py-1.5 text-sm w-full">
            <option value="bekliyor" @selected($referral->fee_status === 'bekliyor')>Ödeme bekliyor</option>
            <option value="odendi" @selected($referral->fee_status === 'odendi')>Ödendi</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] text-gray-400 mb-0.5">Not</label>
          <input type="text" name="notes" form="ref-{{ $referral->id }}" value="{{ $referral->notes }}" placeholder="Not ekleyin..." class="border rounded-lg px-2 py-1.5 text-sm w-full">
        </div>
      </div>

      <div class="mt-3 text-right">
        <button type="submit" form="ref-{{ $referral->id }}" class="bg-gray-900 text-white rounded-lg px-4 py-2 text-xs font-bold">Kaydet</button>
      </div>
    </div>
  @empty
    <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-400">Bu filtrede yönlendirme yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $referrals->links() }}</div>
@endsection
