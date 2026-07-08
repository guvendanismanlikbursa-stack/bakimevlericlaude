{{-- $items: [['name' => 'İstanbul', 'url' => '...'], ...] --}}
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => collect($items)->values()->map(function ($item, $index) {
      return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'name' => $item['name'],
          'item' => $item['url'],
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
