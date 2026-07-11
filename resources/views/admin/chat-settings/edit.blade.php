@extends('admin.layout')
@section('title', 'Canlı Sohbet Ayarları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Canlı Sohbet Ayarları</h1>

<form method="POST" action="{{ route('admin.chat-settings.update') }}" class="bg-white rounded-xl shadow-sm p-6 space-y-6 max-w-2xl">
  @csrf
  @method('PUT')

  <div>
    <label class="block text-sm font-bold text-gray-700 mb-2">Çalışma Saatleri</label>
    <div class="space-y-2">
      @foreach($weekdayLabels as $weekday => $label)
        @php $day = $hours->get($weekday); @endphp
        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 w-32 text-sm">
            <input type="checkbox" name="days[{{ $weekday }}][is_active]" value="1" @checked($day?->is_active)>
            {{ $label }}
          </label>
          <input type="time" name="days[{{ $weekday }}][open_time]" value="{{ $day?->open_time ? \Illuminate\Support\Carbon::parse($day->open_time)->format('H:i') : '09:00' }}" class="border rounded-lg px-2 py-1.5 text-sm">
          <span class="text-gray-400 text-sm">—</span>
          <input type="time" name="days[{{ $weekday }}][close_time]" value="{{ $day?->close_time ? \Illuminate\Support\Carbon::parse($day->close_time)->format('H:i') : '18:00' }}" class="border rounded-lg px-2 py-1.5 text-sm">
        </div>
      @endforeach
    </div>
  </div>

  <div>
    <label class="block text-sm font-bold text-gray-700 mb-2">Çevrimdışı Karşılama Mesajı</label>
    <textarea name="offline_message" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('offline_message', $offlineMessage) }}</textarea>
    <p class="text-xs text-gray-400 mt-1">Çalışma saatleri dışında sohbet widget'ında bu mesaj gösterilir.</p>
  </div>

  <button class="rounded-lg px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700">Kaydet</button>
</form>
@endsection
