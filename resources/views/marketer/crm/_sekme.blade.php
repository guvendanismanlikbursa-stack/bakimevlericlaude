  {{-- 25 Ekim 2026: kullanıcının talebi - Kurumlar / Doktorlar sekmesi. --}}
  <div class="max-w-6xl mx-auto px-4 pt-4 flex gap-2">
    <a href="{{ route('marketer.crm.index') }}" class="rounded-xl border px-4 py-2 text-sm font-bold {{ request()->routeIs('marketer.crm.index') ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">🏠 Kurumlar</a>
    <a href="{{ route('marketer.crm.doctors') }}" class="rounded-xl border px-4 py-2 text-sm font-bold {{ request()->routeIs('marketer.crm.doctors') ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">🩺 Doktorlar</a>
  </div>
