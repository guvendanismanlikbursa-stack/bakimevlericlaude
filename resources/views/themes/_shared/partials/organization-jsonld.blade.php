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
        {{-- 14 Agustos 2026: SEO denetiminde bulundu - hedef URL 'search'
             parametresi uretiyordu ama uygulama arama metnini 'q' parametre
             adiyla okuyor (bkz. FiltersFacilities) - Google Sitelinks Arama
             Kutusu bu sitede tetiklense, kullanici filtrelenmemis bos bir
             sonuc sayfasina duserdi. --}}
        'target' => brand_route('facilities.index').'?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
      ],
    ],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
