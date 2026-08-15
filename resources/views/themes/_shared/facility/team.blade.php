@extends('layouts.brand')
@section('title', 'Ekip Yönetimi | Kurum Paneli')
@section('content')

@include('themes._shared.partials.facility-panel-header', ['title' => 'Ekip Yönetimi', 'subtitle' => 'Kurumunuzda çalışan diğer kişiler için ayrı giriş hesapları oluşturun.'])

<div class="max-w-2xl mx-auto px-4 py-10">
  @if($errors->any())
    <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm font-semibold">{{ $errors->first() }}</div>
  @endif

  <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm mb-6">
    <div class="font-black text-gray-950 mb-3">Ekip üyeleri</div>
    <div class="space-y-2">
      @foreach($team as $member)
        <div class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
          <div>
            <div class="font-bold text-gray-900">{{ $member->name }} @if($member->id === $owner->id)<span class="text-xs text-gray-400">(siz)</span>@endif</div>
            <div class="text-xs text-gray-500">{{ $member->email }} · {{ $member->role === 'owner' ? 'Sahip' : 'Personel' }}</div>
          </div>
          @if($member->role !== 'owner')
            <form method="POST" action="{{ brand_route('facility.team.destroy', $member) }}" onsubmit="return confirm('Bu ekip üyesini kaldırmak istediğinize emin misiniz?');">
              @csrf
              @method('DELETE')
              <button class="text-xs font-black text-red-600">Kaldır</button>
            </form>
          @endif
        </div>
      @endforeach
    </div>
  </div>

  <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
    <div class="font-black text-gray-950 mb-3">Yeni ekip üyesi ekle</div>
    <form method="POST" action="{{ brand_route('facility.team.store') }}" class="space-y-3">
      @csrf
      <label for="team-name" class="sr-only">Ad Soyad</label>
      <input type="text" id="team-name" name="name" value="{{ old('name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2 w-full">
      <label for="team-email" class="sr-only">E-posta</label>
      <input type="email" id="team-email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
      <button class="btn-primary rounded-lg px-4 py-2 font-black">Ekip Üyesi Ekle</button>
      <p class="text-xs text-gray-400">Giriş bilgileri otomatik olarak e-posta ile gönderilir.</p>
    </form>
  </div>
</div>
@endsection
