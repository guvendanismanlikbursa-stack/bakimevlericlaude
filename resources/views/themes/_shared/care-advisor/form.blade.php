@extends('layouts.brand')
{{-- 13 Ekim 2026: kullanıcının talebi - "kurumlar sizin için teklif
     hazırlasın" çerçevesi (ters pazar yeri psikolojisi, Thumbtack/Bark.com
     örneği) - "arama/form" değil "kurumlar sizi bulsun" vurgusu. Bu artık
     SADECE bir metin oyunu değil: iletişim bırakılırsa GERÇEKTEN premium
     kurumlar Fırsat Havuzu'ndan görüyor ve ulaşabiliyor (bkz.
     CareAdvisorLead / Facility\CareAdvisorLeadController).

     13 Ekim 2026 (2): kullanıcının talebi - "sıradan olmamalı", yurt dışı
     örneği Noom'un adım-adım "test" akışı gibi: TEK form yerine, tek
     seferde tek soru gösteren, ilerleme çubuklu bir "quiz" deneyimi.

     13 Ekim 2026 (3): kullanıcının talebi - isim "Pusula" oldu.

     13 Ekim 2026 (4): kullanıcının talebi - "sorular açılır Pusula
     panelinde de ilerleyecek, sonuç Pusula'nın büyük sayfasında
     gösterilecek" - soru/adım HTML'i artık _quiz-core.blade.php'de (TEK
     kaynak), bu sayfa HEM onu include eder HEM DE adım geçiş JS'ini
     (sitewide paylaşılan initCareAdvisorQuizStepper - bkz. layouts/brand.blade.php
     > care-advisor-quiz-stepper partial) kendi kopyasına bağlar. JS
     ÇALIŞMAZSA init hiç çağrılmaz, progress-wrap gizli kalır, ama TÜM
     .quiz-step'ler zaten varsayılan olarak görünür - form olduğu gibi
     çalışır.

     13 Ekim 2026 (6): kullanıcının talebi - "ilk açılış ekranı Pusula'da
     ne işe yaradığı bile yazmıyor" sorununu çözerken, tanıtım rozeti ve
     kısa açıklama artık _quiz-core.blade.php'nin İÇİNE taşındı (hem tam
     sayfada hem widget panelinde görünsün diye) - burada MÜKERRER
     göstermemek için sadece H1 kaldı (sayfa yapısı/SEO için). --}}
@section('title', 'Pusula - Kurumlar Size Teklif Versin')
@section('meta_description', 'Birkaç soruyu yanıtlayın; bölgenizdeki uygun kurumlar sizi bulsun ve doğrudan size ulaşsın.')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
  <h1 class="text-3xl md:text-4xl font-black text-gray-950 text-center mb-6">Siz aramayın, kurumlar sizi bulsun</h1>

  @include('themes._shared.care-advisor._quiz-core')
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.initCareAdvisorQuizStepper) window.initCareAdvisorQuizStepper(document);
  });
</script>
@endsection
