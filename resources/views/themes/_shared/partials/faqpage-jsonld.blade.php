{{-- $questions: [[soru, cevap], [soru, cevap], ...] - config/site_content.php'deki
     bolum bazli questions[] dizisiyle ayni format (Faq modelinin question/answer
     property'lerinden farkli, indexed cift). --}}
@if(!empty($questions))
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => collect($questions)->map(function ($pair) {
      return [
          '@type' => 'Question',
          'name' => $pair[0],
          'acceptedAnswer' => [
              '@type' => 'Answer',
              'text' => $pair[1],
          ],
      ];
  })->values()->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
