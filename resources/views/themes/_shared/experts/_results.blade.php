@if(! $experts)
  {{-- 7 Ekim 2026: kullanicinin talebi - hicbir filtre/arama yokken
       rastgele doktor listesi yerine bolumu tanitan bilgilendirme. --}}
  <div class="rounded-2xl border-2 border-dashed border-teal-200 bg-teal-50/40 p-8 text-center" style="background:rgba(255,255,255,.88);">
    <div class="text-4xl mb-3">🩺</div>
    <h2 class="text-lg font-black text-gray-950 mb-2">Özel Doktorlar Bölümüne Hoş Geldiniz</h2>
    <p class="text-gray-600 max-w-xl mx-auto mb-4">
      Çocuk ve yaşlı sağlığı alanında kendi muayenehanesinde çalışan özel doktorları burada bulabilirsiniz — Pediatri (Çocuk Doktoru),
      Geriatri (Yaşlılık Uzmanı), Fiziksel Tıp ve Rehabilitasyon (Fizik Tedavi Doktoru) ve daha fazlası.
    </p>
    <p class="text-sm font-semibold text-teal-700">
      Başlamak için yukarıdan il, ilçe veya branş seçin — ya da doktor adını yazın.
    </p>
  </div>
@else
  <div class="space-y-4" id="js-expert-list">
    @forelse($experts as $expert)
      @php($isOpen = $activeExpertSlug === $expert->slug)
      <div class="js-expert-card rounded-2xl bg-white overflow-hidden transition-all duration-200 border-2" data-slug="{{ $expert->slug }}" style="border-color: {{ $isOpen ? '#0d9488' : '#e5e7eb' }}">
        <button type="button" class="js-expert-toggle w-full flex items-center justify-between gap-3 p-4 text-left js-expert-header {{ $isOpen ? 'bg-teal-50' : 'bg-white' }}">
          <div class="flex items-center gap-3 min-w-0">
            <div class="js-expert-avatar shrink-0 h-11 min-w-[44px] px-2 rounded-2xl flex items-center justify-center font-black text-white text-[11px] tracking-wide" style="background: {{ $expert->titleRankColor() }}">
              {{ $expert->titleAbbreviation() }}
            </div>
            <div class="min-w-0">
              <div class="font-black text-gray-950 truncate">{{ $expert->title }} {{ $expert->name }}</div>
              <div class="text-sm text-gray-500 mt-0.5 truncate">{{ $expert->branch }} <span class="text-gray-400">({{ $expert->branch_label }})</span> · {{ $expert->city?->name }}@if($expert->district) / {{ $expert->district }}@endif</div>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            @if(doctor_reviews_visible() && $expert->rating)
              <span class="text-xs font-black text-amber-600 bg-amber-50 rounded-full px-2 py-1">★ {{ number_format($expert->rating, 1) }}</span>
            @endif
            <svg class="js-expert-chevron w-5 h-5 transition-transform {{ $isOpen ? 'rotate-180' : '' }}" style="color: {{ $isOpen ? '#0d9488' : '#9ca3af' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
          </div>
        </button>
        <div class="js-expert-detail {{ $isOpen ? '' : 'hidden' }} p-5 bg-teal-50/40" style="border-top: 2px solid #0d9488;">
          {{-- 28 Ekim 2026: TTB mevzuatı - açıklama metni (içinde hasta yorumu alıntıları ve
               denkliği belirsiz unvan/sertifikalar olabiliyor) ve destekçi etiketi gösterilmez;
               yalnızca unvan, ad, uzmanlık, adres ve iletişim. --}}
          {{-- 25 Ekim 2026: doktorun kendi sayfası (arama motorları için de ayrı adres). --}}
          <a href="{{ brand_route('experts.show', $expert->slug) }}" style="display:inline-block;margin-bottom:12px;font-size:13.5px;font-weight:800;color:#0d6e6a;text-decoration:none;">Doktor sayfası →</a>
          <div class="grid sm:grid-cols-2 gap-3 text-sm mb-4">
            @if($expert->clinic_name)
              <div class="bg-white rounded-lg px-3 py-2 border border-gray-100"><span class="text-gray-400 text-xs block">Klinik/Hastane</span><span class="font-bold text-gray-800">{{ $expert->clinic_name }}</span></div>
            @endif
            @if($expert->address)
              <div class="bg-white rounded-lg px-3 py-2 border border-gray-100"><span class="text-gray-400 text-xs block">Adres</span><span class="font-bold text-gray-800">{{ $expert->address }}</span></div>
            @endif
            {{-- 30 Ekim 2026: kullanıcının talebi - site satışı yapılmış (yayındaki) doktorun sitesi. --}}
            @if($doktorSiteAdresi = \App\Models\ExpertWebsite::liveUrlMap()[(int) $expert->id] ?? null)
              <div class="bg-white rounded-lg px-3 py-2 border border-gray-100"><span class="text-gray-400 text-xs block">İnternet sitesi</span><a href="{{ $doktorSiteAdresi }}" target="_blank" rel="noopener" class="font-bold" style="color:#0d6e6a;word-break:break-all;">{{ \App\Models\ExpertWebsite::displayAddress($doktorSiteAdresi) }}</a></div>
            @endif
          </div>

          @if($expert->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $expert->phone) }}" class="flex items-center justify-center gap-2 w-full sm:w-auto rounded-xl text-white font-black px-6 py-3 mb-5 hover:brightness-110 transition" style="background: #0d9488;">
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-.826 1.656L4.78 8.95a9.953 9.953 0 0 0 6.27 6.27l.896-1.223a1.5 1.5 0 0 1 1.656-.826l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13v-1Z"/></svg>
              {{ $expert->phone }}
            </a>
          @endif

          {{-- 28 Ekim 2026: puan/yorumlar varsayılan olarak gizli (bkz. doctor_reviews_visible). --}}
          @if(! doctor_reviews_visible())
            @if($expert->google_maps_url)
              <a href="{{ $expert->google_maps_url }}" target="_blank" rel="noopener nofollow" class="inline-block mt-3 text-sm font-bold text-teal-700 underline">Google'daki yorumları gör →</a>
            @endif
          @elseif($expert->reviews && count($expert->reviews))
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-black text-gray-500 uppercase tracking-wider">Google'daki Son Yapılan Yorumlar</span>
              @if($expert->google_maps_url)
                <a href="{{ $expert->google_maps_url }}" target="_blank" rel="noopener" class="text-xs font-bold hover:underline shrink-0 ml-2" style="color: #0d9488;">Tüm yorumları gör →</a>
              @endif
            </div>
            <div class="space-y-2 overflow-y-auto pr-1" style="max-height: 260px;">
              @foreach(array_slice($expert->reviews, 0, 5) as $r)
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

  <div class="mt-6">{{ $experts->links() }}</div>
@endif
