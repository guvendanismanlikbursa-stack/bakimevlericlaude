@if(! $organizations)
  <div class="rounded-2xl border-2 border-dashed border-pink-200 bg-pink-50/40 p-8 text-center">
    <div class="text-4xl mb-3">🎉</div>
    <h2 class="text-lg font-black text-gray-950 mb-2">Organizasyon Şirketleri Bölümüne Hoş Geldiniz</h2>
    <p class="text-gray-600 max-w-xl mx-auto mb-4">
      Doğum günü, kına, düğün, nişan, sünnet ve mezuniyet gibi özel günleriniz için organizasyon şirketlerini burada bulabilirsiniz.
    </p>
    <p class="text-sm font-semibold text-pink-700">
      Başlamak için yukarıdan il, ilçe veya hizmet türü seçin — ya da firma adını yazın.
    </p>
  </div>
@else
  <div class="space-y-4" id="js-org-list">
    @forelse($organizations as $org)
      @php($isOpen = $activeOrgSlug === $org->slug)
      <div class="js-org-card rounded-2xl bg-white overflow-hidden transition-all duration-200 border-2" data-slug="{{ $org->slug }}" style="border-color: {{ $isOpen ? '#db2777' : '#e5e7eb' }}">
        <button type="button" class="js-org-toggle w-full flex items-center justify-between gap-3 p-4 text-left js-org-header {{ $isOpen ? 'bg-pink-50' : 'bg-white' }}">
          <div class="flex items-center gap-3 min-w-0">
            <div class="js-org-avatar shrink-0 h-11 min-w-[44px] px-2 rounded-2xl flex items-center justify-center font-black text-white text-[11px] tracking-wide" style="background: {{ $org->avatarColor() }}">
              {{ $org->avatarAbbreviation() }}
            </div>
            <div class="min-w-0">
              <div class="font-black text-gray-950 truncate">{{ $org->name }}</div>
              <div class="text-sm text-gray-500 mt-0.5 truncate">{{ $org->service_type }} <span class="text-gray-400">({{ $org->service_type_label }})</span> · {{ $org->city?->name }}@if($org->district) / {{ $org->district }}@endif</div>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            @if($org->rating)
              <span class="text-xs font-black text-amber-600 bg-amber-50 rounded-full px-2 py-1">★ {{ number_format($org->rating, 1) }}</span>
            @endif
            <svg class="js-org-chevron w-5 h-5 transition-transform {{ $isOpen ? 'rotate-180' : '' }}" style="color: {{ $isOpen ? '#db2777' : '#9ca3af' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
          </div>
        </button>
        <div class="js-org-detail {{ $isOpen ? '' : 'hidden' }} p-5 bg-pink-50/40" style="border-top: 2px solid #db2777;">
          <div class="grid sm:grid-cols-2 gap-3 text-sm mb-4">
            @if($org->clinic_name)
              <div class="bg-white rounded-lg px-3 py-2 border border-gray-100"><span class="text-gray-400 text-xs block">Firma Unvanı</span><span class="font-bold text-gray-800">{{ $org->clinic_name }}</span></div>
            @endif
            @if($org->address)
              <div class="bg-white rounded-lg px-3 py-2 border border-gray-100"><span class="text-gray-400 text-xs block">Adres</span><span class="font-bold text-gray-800">{{ $org->address }}</span></div>
            @endif
          </div>

          @if($org->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $org->phone) }}" class="flex items-center justify-center gap-2 w-full sm:w-auto rounded-xl text-white font-black px-6 py-3 mb-5 hover:brightness-110 transition" style="background: #db2777;">
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-.826 1.656L4.78 8.95a9.953 9.953 0 0 0 6.27 6.27l.896-1.223a1.5 1.5 0 0 1 1.656-.826l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13v-1Z"/></svg>
              {{ $org->phone }}
            </a>
          @endif

          @if($org->reviews && count($org->reviews))
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-black text-gray-500 uppercase tracking-wider">Google'daki Son Yapılan Yorumlar</span>
              @if($org->google_maps_url)
                <a href="{{ $org->google_maps_url }}" target="_blank" rel="noopener" class="text-xs font-bold hover:underline shrink-0 ml-2" style="color: #db2777;">Tüm yorumları gör →</a>
              @endif
            </div>
            <div class="space-y-2 overflow-y-auto pr-1" style="max-height: 260px;">
              @foreach(array_slice($org->reviews, 0, 5) as $r)
                <div class="bg-white rounded-lg border border-gray-100 p-3">
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-bold text-gray-800">{{ $r['author_name'] ?? 'Google Kullanıcısı' }}</span>
                    <span class="text-gray-400 shrink-0 ml-2">{{ $r['relative_time_text'] ?? '' }}</span>
                  </div>
                  @if(!empty($r['rating']))
                    <div class="text-amber-500 text-xs mb-1">{{ str_repeat('★', (int) $r['rating']) }}{{ str_repeat('☆', 5 - (int) $r['rating']) }}</div>
                  @endif
                  @if(!empty($r['body']))
                    <p class="text-sm text-gray-600 break-words">{{ $r['body'] }}</p>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <p class="text-sm text-gray-400">Henüz yorum yok.</p>
          @endif
        </div>
      </div>
    @empty
      <p class="text-gray-400">Bu kritere uygun kayıt bulunamadı.</p>
    @endforelse
  </div>

  <div class="mt-6">{{ $organizations->links() }}</div>
@endif
