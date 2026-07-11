{{-- $facilities: liste sayfasindaki (o sayfada gorunen) Facility koleksiyonu
     veya paginator olabilir - collect() bir Paginator'a uygulanirsa
     toArray() meta verisini (current_page, total, ...) de collect eder,
     bu yuzden once items() ile duz bir diziye indirgiyoruz. --}}
@php
  $itemlistItems = $facilities instanceof \Illuminate\Contracts\Pagination\Paginator || $facilities instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
      ? $facilities->items()
      : (is_iterable($facilities ?? null) ? $facilities : []);
@endphp
@if(!empty($itemlistItems))
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => collect($itemlistItems)->values()->map(function ($facility, $index) {
      return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'name' => $facility->name,
          'url' => brand_route('facilities.show', ['slug' => $facility->slug]),
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
