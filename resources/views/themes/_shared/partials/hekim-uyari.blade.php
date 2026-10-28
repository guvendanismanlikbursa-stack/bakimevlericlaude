{{-- 28 Ekim 2026: kullanıcının talebi - TTB Hekimlerin Tanıtım ve Bilgilendirme Faaliyetleri,
     md. 5: her hekim sayfasının en başında, en az diğer yazılarla aynı puntoda ve kolayca fark
     edilir biçimde. Metin mevzuattaki ifadedir; değiştirilmemeli.
     Rehber sayfalarındaki kısa metinlerde ($metin) yazı ekrana göre küçülür, ortalanır. --}}
@isset($metin)
  {{-- Telefonda tek satır: kısa metin ($kisa) gösterilir, yazı ekran genişliğine göre küçülür. --}}
  <style>
    .hu-kisa{display:none}
    @media (max-width:639px){
      .hekim-uyari.hu-sig .hu-uzun{display:none}
      .hekim-uyari.hu-sig .hu-kisa{display:inline}
      .hekim-uyari.hu-sig{white-space:nowrap;padding:9px 8px !important;font-size:clamp(10px, calc((100vw - 64px) / 30), 15px) !important;}
    }
  </style>
@endisset
<div role="note" class="hekim-uyari{{ isset($metin) ? ' hu-sig' : '' }}" style="margin:0 0 14px;border:2px solid #f59e0b;background:#fffbeb;border-radius:12px;padding:10px 14px;color:#78350f;font-size:{{ $boyut ?? (isset($metin) ? 'clamp(12px, calc((100vw - 72px) / 50), 16px)' : '16px') }};line-height:1.5;font-weight:700;text-align:center;text-wrap:balance;">⚠️ @isset($metin)<span class="hu-uzun">{{ $metin }}</span><span class="hu-kisa">{{ $kisa ?? $metin }}</span>@else Sayfa içeriği sadece bilgilendirme amaçlıdır, tanı ve tedavi için mutlaka doktorunuza başvurunuz.@endisset</div>
