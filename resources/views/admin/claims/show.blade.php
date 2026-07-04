@extends('admin.layout')
@section('title', 'Başvuru İncele')

@section('content')
<a href="{{ route('admin.claims.index') }}" class="text-sm text-gray-500">← Listeye dön</a>
<h1 class="text-2xl font-bold mt-2 mb-6">{{ $claim->facility->name }} — Sahiplenme Başvurusu</h1>

<div class="grid md:grid-cols-2 gap-6">
  <div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="font-bold mb-3">Başvuran Bilgileri</h2>
    <p class="text-sm"><strong>Ad Soyad:</strong> {{ $claim->applicant_name }}</p>
    <p class="text-sm"><strong>E-posta:</strong> {{ $claim->applicant_email }}</p>
    <p class="text-sm"><strong>Telefon:</strong> {{ $claim->applicant_phone }}</p>
    <p class="text-sm mt-2"><strong>Not:</strong> {{ $claim->note ?: '-' }}</p>
    <p class="text-sm mt-2"><strong>Durum:</strong> {{ $claim->status }}</p>

    <div class="mt-3 p-3 rounded-lg {{ $claim->distance_km !== null && $claim->distance_km > 50 ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50' }}">
      <p class="text-xs font-semibold text-gray-500 mb-1">Konum (sahtecilik kontrolü)</p>
      @if($claim->applicant_city_name)
        <p class="text-sm">Başvuru anında yaklaşık konum: <strong>{{ $claim->applicant_city_name }}</strong></p>
        @if($claim->distance_km !== null)
          <p class="text-sm">Kurum adresine mesafe: <strong>{{ number_format($claim->distance_km, 1) }} km</strong>
            @if($claim->distance_km > 50) <span class="text-amber-700">— uzak, dikkatli inceleyin</span>@endif
          </p>
        @else
          <p class="text-xs text-gray-400">Kurumun kayıtlı koordinatı olmadığı için mesafe hesaplanamadı.</p>
        @endif
      @else
        <p class="text-xs text-gray-400">Başvuran konum izni vermemiş, veri yok.</p>
      @endif
    </div>

    @if($claim->status === 'pending')
      <div class="flex gap-3 mt-6">
        <form method="POST" action="{{ route('admin.claims.approve', $claim) }}" onsubmit="return confirm('Onaylanırsa kurum hesabı otomatik oluşturulup e-posta gönderilecek. Emin misiniz?');">
          @csrf
          <button class="bg-green-600 text-white px-5 py-2 rounded-lg font-semibold text-sm">Onayla</button>
        </form>
        <form method="POST" action="{{ route('admin.claims.reject', $claim) }}">
          @csrf
          <input type="text" name="admin_note" placeholder="Red sebebi (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm">
          <button class="bg-red-600 text-white px-5 py-2 rounded-lg font-semibold text-sm">Reddet</button>
        </form>
      </div>
    @endif
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="font-bold mb-3">Yüklenen Evrak</h2>
    <img src="{{ asset('storage/'.$claim->document_path) }}" class="rounded-lg w-full">
  </div>
</div>
@endsection
