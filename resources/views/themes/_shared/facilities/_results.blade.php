@if(request()->filled('q') && ! empty($sectionBreakdown ?? []))
  <div class="mb-6 flex flex-wrap items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
    <span class="font-black text-gray-500">Tüm bölümlerde "{{ request('q') }}" için bulunanlar:</span>
    @foreach($sectionBreakdown as $item)
      <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 font-black text-gray-700">
        {{ $item['title'] }} <span class="text-gray-400">·</span> {{ $item['total'] }}
      </span>
    @endforeach
  </div>
@endif

@if($facilities->isEmpty())
  <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
    <div class="text-4xl font-black mb-4">0</div>
    @if(request()->filled('budget'))
      <p>Aramış olduğunuz {{ number_format((float) request('budget'), 0, ',', '.') }} TL bütçede kurum bulunamadı.</p>
    @else
      <p>Kriterlere uygun kurum bulunamadı.</p>
    @endif
    <a href="{{ brand_route('facilities.index', ['bolum' => $activeSection['slug']]) }}" class="text-primary underline mt-2 inline-block">Filtreleri temizle</a>
  </div>
@endif

<div class="grid md:grid-cols-3 gap-6">
  @foreach($facilities as $facility)
    @include('themes._shared.partials.facility-card', ['facility' => $facility])
  @endforeach
</div>

<div class="mt-8">
  @include('partials.pagination-info', ['paginator' => $facilities])
  {{ $facilities->links() }}
</div>
