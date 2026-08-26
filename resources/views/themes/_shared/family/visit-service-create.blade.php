@extends('layouts.brand')

@section('content')
<div class="max-w-xl mx-auto py-10 px-4">
  <a href="{{ brand_route('family.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 mb-4">← Panelime dön</a>

  <div class="bg-white rounded-xl shadow-sm p-6">
    <h1 class="text-xl font-black text-gray-950 mb-1">Yakınımı Ziyaret Et</h1>
    <p class="text-sm text-gray-600 mb-6">Kurumda kalan yakınınızı ekibimiz düzenli aralıklarla ziyaret eder, durumunu size raporlar. Talebinizi aldıktan sonra ekibimiz sizi telefonla arayıp sıklık ve ücreti netleştirir.</p>

    @if($facilities->isEmpty())
      <p class="text-sm text-amber-700 bg-amber-50 rounded-lg p-4">Şu anda bu hizmete açık bir kurum bulunmuyor. Yakınınızın kaldığı kurum için bu hizmeti talep etmek isterseniz bize ulaşın, kurumla görüşelim.</p>
    @else
      <form method="POST" action="{{ brand_route('family.visit-service.store') }}" class="space-y-4">
        @csrf
        <div>
          <label class="text-sm font-medium">Kurum</label>
          <select name="facility_id" required class="border rounded-lg px-3 py-2 w-full mt-1">
            <option value="">Seçiniz</option>
            @foreach($facilities as $facility)
              <option value="{{ $facility->id }}" @selected(old('facility_id', $selectedFacilityId) == $facility->id)>{{ $facility->name }}</option>
            @endforeach
          </select>
          @error('facility_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="text-sm font-medium">Yakınınızın Adı Soyadı</label>
          <input type="text" name="patient_name" value="{{ old('patient_name') }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
          @error('patient_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium">Yaşı</label>
            <input type="number" min="0" max="120" name="patient_age" value="{{ old('patient_age') }}" class="border rounded-lg px-3 py-2 w-full mt-1">
          </div>
          <div>
            <label class="text-sm font-medium">Aylık İstenen Sıklık</label>
            <input type="text" name="desired_frequency" value="{{ old('desired_frequency') }}" placeholder="Örn. Ayda 2 kez" class="border rounded-lg px-3 py-2 w-full mt-1">
          </div>
        </div>

        <div>
          <label class="text-sm font-medium">Genel Durumu / Özel İhtiyaçları</label>
          <textarea name="patient_condition" rows="3" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="Varsa sağlık durumu, dikkat edilmesi gereken noktalar...">{{ old('patient_condition') }}</textarea>
        </div>

        <div>
          <label class="text-sm font-medium">Telefon</label>
          <input type="tel" name="phone" value="{{ old('phone') }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
          @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full bg-primary text-white font-black py-3 rounded-lg">Talebi Gönder</button>
      </form>
    @endif
  </div>
</div>
@endsection
