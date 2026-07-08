@extends('admin.layout')
@section('title', 'Kurum Kayıt Başvurusu İncele')

@section('content')
<a href="{{ route('admin.registrations.index') }}" class="text-sm text-gray-500">← Listeye dön</a>
<h1 class="text-2xl font-bold mt-2 mb-6">{{ $registration->name }} — Kurum Kayıt Başvurusu</h1>

<div class="grid md:grid-cols-2 gap-6">
  <div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="font-bold mb-3">Kurum Bilgileri</h2>
    <p class="text-sm"><strong>Kategori:</strong> {{ $registration->category->name ?? '-' }}</p>
    <p class="text-sm"><strong>İl / İlçe:</strong> {{ $registration->city->name ?? '-' }} / {{ $registration->district ?: '-' }}</p>
    <p class="text-sm"><strong>Adres:</strong> {{ $registration->address ?: '-' }}</p>
    <p class="text-sm"><strong>Telefon:</strong> {{ $registration->phone ?: '-' }}</p>
    <p class="text-sm"><strong>Kapasite:</strong> {{ $registration->capacity ?? '-' }}</p>
    <p class="text-sm"><strong>Fiyat Aralığı:</strong> {{ $registration->price_min ?? '-' }} - {{ $registration->price_max ?? '-' }}</p>
    <p class="text-sm mt-2"><strong>Açıklama:</strong> {{ $registration->description ?: '-' }}</p>
    <p class="text-sm mt-2"><strong>Durum:</strong> {{ $registration->status }}</p>

    @if($registration->admin_note)
      <div class="mt-3 p-3 rounded-lg bg-amber-50 border border-amber-200">
        <p class="text-xs font-semibold text-amber-700 mb-1">Gönderilen düzeltme notu</p>
        <p class="text-sm text-amber-800">{{ $registration->admin_note }}</p>
      </div>
    @endif

    @if($registration->status === 'pending')
      <div class="flex flex-wrap gap-3 mt-6">
        <form method="POST" action="{{ route('admin.registrations.approve', $registration) }}" onsubmit="return confirm('Onaylanırsa kurum ve hesabı otomatik oluşturulup e-posta gönderilecek. Emin misiniz?');">
          @csrf
          <button class="bg-green-600 text-white px-5 py-2 rounded-lg font-semibold text-sm">Onayla</button>
        </form>
        <form method="POST" action="{{ route('admin.registrations.request-revision', $registration) }}" class="flex items-start gap-2">
          @csrf
          <textarea name="admin_note" placeholder="Düzeltilmesi gereken nokta (zorunlu)" required rows="2" class="border rounded-lg px-3 py-2 text-sm"></textarea>
          <button class="bg-amber-500 text-white px-5 py-2 rounded-lg font-semibold text-sm">Revize İste</button>
        </form>
        <form method="POST" action="{{ route('admin.registrations.destroy', $registration) }}" onsubmit="return confirm('Silinsin mi?');">
          @csrf @method('DELETE')
          <button class="bg-red-600 text-white px-5 py-2 rounded-lg font-semibold text-sm">Sil</button>
        </form>
      </div>
    @endif
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="font-bold mb-3">Yetkili Bilgileri</h2>
    <p class="text-sm"><strong>Ad Soyad:</strong> {{ $registration->applicant_name }}</p>
    <p class="text-sm"><strong>E-posta:</strong> {{ $registration->applicant_email }}</p>
    <p class="text-sm"><strong>Telefon:</strong> {{ $registration->applicant_phone }}</p>
    <p class="text-sm mt-2"><strong>Başvuru Markası:</strong> {{ $registration->brand }}</p>
  </div>
</div>
@endsection
