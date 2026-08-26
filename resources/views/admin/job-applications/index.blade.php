@extends('admin.layout')
@section('title', 'İş Başvuruları')

@section('content')
<div class="mb-6">
  <h1 class="text-2xl font-bold">İş Başvuruları</h1>
  <p class="text-sm text-gray-500 mt-1">Kurumlarda çalışmak isteyen kişilerin başvuruları. Kurumun kayıtlı WhatsApp'ına manuel olarak iletip "İletildi" işaretleyin.</p>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  @foreach(['yeni' => 'Yeni', 'iletildi' => 'İletildi', '' => 'Tümü'] as $key => $label)
    <a href="{{ route('admin.job-applications.index', array_filter(['status' => $key])) }}"
       class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $status === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
      <span>{{ $label }}</span>
      @if($key === 'yeni')<span class="text-xs {{ $status === $key ? 'text-gray-300' : 'text-gray-400' }}">{{ $newCount }}</span>@endif
    </a>
  @endforeach
</div>

<div class="space-y-2">
  @forelse($applications as $application)
    <details class="bg-white rounded-xl shadow-sm group">
      <summary class="cursor-pointer list-none p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          <div class="min-w-0">
            <div class="font-bold text-gray-950 truncate">{{ $application->applicant_name }}@if($application->desired_position) <span class="font-normal text-gray-400">· {{ $application->desired_position }}</span>@endif</div>
            <div class="text-xs text-gray-500 truncate">{{ $application->facility->name ?? '(silinmiş kurum)' }} · {{ $application->created_at->format('d.m.Y H:i') }}</div>
          </div>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-full whitespace-nowrap {{ $application->status === 'iletildi' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
          {{ $application->status === 'iletildi' ? 'İletildi' : 'Yeni' }}
        </span>
      </summary>

      <div class="px-4 pb-4 border-t pt-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1.5 text-sm mb-3">
          <div><span class="text-gray-400">Telefon:</span> {{ $application->applicant_phone }}</div>
          @if($application->applicant_email)<div><span class="text-gray-400">E-posta:</span> {{ $application->applicant_email }}</div>@endif
          @if($application->applicant_age)<div><span class="text-gray-400">Yaş:</span> {{ $application->applicant_age }}</div>@endif
          @if($application->applicant_location)<div><span class="text-gray-400">Konum:</span> {{ $application->applicant_location }}</div>@endif
        </div>
        @if($application->experience)
          <div class="text-sm mb-3"><span class="text-gray-400">Tecrübe:</span> {{ $application->experience }}</div>
        @endif
        @if($application->status === 'iletildi' && $application->forwarded_at)
          <p class="text-xs text-gray-400 mb-3">{{ $application->forwarded_at->format('d.m.Y H:i') }} tarihinde iletildi olarak işaretlendi{{ $application->forwardedBy?->name ? ' ('.$application->forwardedBy->name.')' : '' }}.</p>
        @endif

        <div class="flex flex-wrap items-center gap-2">
          @php
            $waMessage = "Merhaba, \"{$application->facility->name}\" kurumunuza bir iş başvurusu geldi:\n\n"
              ."Ad Soyad: {$application->applicant_name}\n"
              .($application->applicant_age ? "Yaş: {$application->applicant_age}\n" : '')
              .($application->applicant_location ? "Konum: {$application->applicant_location}\n" : '')
              ."Telefon: {$application->applicant_phone}\n"
              .($application->applicant_email ? "E-posta: {$application->applicant_email}\n" : '')
              .($application->desired_position ? "İlgilendiği pozisyon: {$application->desired_position}\n" : '')
              .($application->experience ? "\nTecrübe: {$application->experience}" : '');
            $waUrl = $application->facility ? facility_whatsapp_url_with_message($application->facility, $waMessage) : null;
          @endphp
          @if($waUrl)
            <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1.5 bg-[#25D366] text-white font-bold text-xs px-4 py-2 rounded-lg">📱 Kurumun WhatsApp'ına gönder</a>
          @else
            <span class="text-xs text-amber-700">Kurumun kayıtlı telefonu WhatsApp için uygun görünmüyor - bilgileri elle iletin.</span>
          @endif

          @if($application->status !== 'iletildi')
            <form method="POST" action="{{ route('admin.job-applications.mark-forwarded', $application) }}">
              @csrf
              <button type="submit" class="bg-gray-900 text-white rounded-lg px-4 py-2 text-xs font-bold">İletildi olarak işaretle</button>
            </form>
          @endif

          <form method="POST" action="{{ route('admin.job-applications.destroy', $application) }}" onsubmit="return confirm('Bu başvuru kalıcı olarak silinsin mi?');" class="ml-auto">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 text-xs font-bold hover:underline">Sil</button>
          </form>
        </div>
      </div>
    </details>
  @empty
    <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-400">Bu filtrede başvuru yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $applications->links() }}</div>
@endsection
