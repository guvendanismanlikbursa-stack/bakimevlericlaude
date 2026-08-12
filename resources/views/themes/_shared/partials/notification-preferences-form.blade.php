{{-- 12 Agustos 2026: kullanicinin talebi - "hangi olaylar icin e-posta/push
     gelsin secemiyorum, hepsi ya acik ya kapali". $notificationGroups
     (bkz. helpers.php notification_preference_groups()) ve $preferences
     (mevcut $user/$family->notification_preferences, bos olabilir) view'a
     gecirilir; her satir bir olay grubu, iki sutun (e-posta/push) her biri
     varsayilan ACIK (opt-out) checkbox. --}}
<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
  <h2 class="font-bold text-lg mb-1">Bildirim Tercihleri</h2>
  <p class="text-sm text-gray-500 mb-4">Hangi olaylar için e-posta veya anlık bildirim almak istediğinizi seçin.</p>

  <form method="POST" action="{{ $notificationFormAction }}">
    @csrf
    @method('PUT')
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
          <th class="py-2">Olay</th>
          <th class="py-2 w-24 text-center">E-posta</th>
          <th class="py-2 w-24 text-center">Anlık bildirim</th>
        </tr>
      </thead>
      <tbody>
        @foreach($notificationGroups as $group => $meta)
          @php
            $exampleType = $meta['types'][0];
            $emailOn = (bool) (($preferences[$exampleType]['email'] ?? true));
            $pushOn = (bool) (($preferences[$exampleType]['push'] ?? true));
          @endphp
          <tr class="border-b border-gray-50">
            <td class="py-3 font-semibold text-gray-800">{{ $meta['label'] }}</td>
            <td class="py-3 text-center"><input type="checkbox" name="notifications[{{ $group }}][email]" value="1" @checked($emailOn) class="w-4 h-4 rounded border-gray-300"></td>
            <td class="py-3 text-center"><input type="checkbox" name="notifications[{{ $group }}][push]" value="1" @checked($pushOn) class="w-4 h-4 rounded border-gray-300"></td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <button class="btn-primary rounded-lg px-5 py-2 font-semibold mt-4">Tercihleri Kaydet</button>
  </form>
</div>
