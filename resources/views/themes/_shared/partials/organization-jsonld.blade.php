<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@graph' => [
    [
      '@type' => 'Organization',
      'name' => $brand['name'],
      'url' => url('/'),
      'logo' => seo_og_image(),
      'description' => $brand['tagline'],
    ],
    [
      '@type' => 'WebSite',
      'name' => $brand['name'],
      'url' => url('/'),
      'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => brand_route('facilities.index').'?search={search_term_string}',
        'query-input' => 'required name=search_term_string',
      ],
    ],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
