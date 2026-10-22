{{-- 22 Ekim 2026: kullanıcının talebi - "her ölçüdeki cihazda en güzel
     şekilde görünmeli, özellikle mobilde dengeli ve şık". İyilik Hareketi
     sayfalarının kendi stilleri; canlıda Tailwind yeniden derlenmediği için
     (FTP ile yükleme) yeni sınıflar burada düz CSS olarak tanımlanır.
     @once: sayfada birden fazla parça çağırsa da bir kez basılır. --}}
@once
<style>
  .ih-wrap{max-width:1080px;margin:0 auto;padding:0 16px}
  .ih-sec{padding:52px 0}
  @media (min-width:768px){.ih-sec{padding:80px 0}}
  .ih-eyebrow{display:inline-block;font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#a21caf}
  .ih-h2{font-size:clamp(26px,4.4vw,38px);font-weight:900;line-height:1.15;color:#111827;margin:8px 0 14px;letter-spacing:-.01em}
  .ih-lead{font-size:16px;line-height:1.75;color:#4b5563;max-width:720px}
  .ih-center{text-align:center}
  .ih-center .ih-lead{margin-left:auto;margin-right:auto}
  .ih-card{background:#fff;border:1px solid #eceef3;border-radius:20px;padding:22px;box-shadow:0 1px 2px rgba(16,24,40,.04),0 10px 30px rgba(16,24,40,.05)}
  @media (min-width:768px){.ih-card{padding:28px}}
  .ih-grid3{display:grid;gap:16px;grid-template-columns:1fr;max-width:560px;margin:0 auto}
  @media (min-width:900px){.ih-grid3{grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;max-width:none}}
  .ih-grid2{display:grid;gap:16px;grid-template-columns:1fr}
  @media (min-width:760px){.ih-grid2{grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}}
  .ih-split{display:grid;gap:28px;align-items:center}
  @media (min-width:880px){.ih-split{grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:56px}}
  .ih-photo{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:24px;box-shadow:0 20px 50px rgba(74,4,78,.18)}
  .ih-note{border-radius:16px;padding:16px 18px;background:#f8fafc;border:1px solid #e5e7eb;font-size:14px;line-height:1.65;color:#4b5563}

  /* Üyelik kartları */
  .ih-tier{display:flex;flex-direction:column;height:100%;position:relative}
  .ih-tier.is-premium{border:2px solid #fcd34d;box-shadow:0 1px 2px rgba(16,24,40,.04),0 18px 40px rgba(180,83,9,.12)}
  .ih-tier-emoji{font-size:36px;line-height:1}
  .ih-tier-title{font-size:21px;font-weight:900;color:#111827;margin-top:14px;line-height:1.25}
  .ih-tier-sub{font-size:12px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;color:#a21caf;margin-top:4px}
  .ih-tier.is-premium .ih-tier-sub{color:#b45309}
  .ih-tier-tag{font-weight:800;color:#1f2937;margin-top:14px;line-height:1.45}
  .ih-tier-aud{font-size:14px;line-height:1.6;color:#4b5563;margin-top:8px}
  .ih-tier-price{font-size:30px;font-weight:900;color:#701a75;margin-top:10px}
  .ih-tier-link{margin-top:auto;padding-top:18px;font-size:14px;font-weight:900;color:#701a75;text-decoration:underline;text-underline-offset:3px}
  .ih-ribbon{position:absolute;top:-12px;right:18px;background:#f59e0b;color:#fff;font-size:11px;font-weight:900;letter-spacing:.06em;text-transform:uppercase;border-radius:999px;padding:5px 10px}

  /* Rozet listesi + şeffaflık */
  .ih-badges{display:grid;gap:10px;grid-template-columns:1fr;margin-top:20px}
  .ih-badge{display:flex;align-items:center;gap:12px;background:#fff;border-radius:14px;padding:12px 14px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
  .ih-badge b{color:#701a75}
  .ih-badge span{font-size:13px;color:#6b7280}
  .ih-stat{text-align:center;background:#fff;border-radius:24px;padding:28px 20px;box-shadow:0 10px 30px rgba(112,26,117,.10)}
  .ih-stat-num{font-size:clamp(48px,9vw,64px);font-weight:900;color:#701a75;line-height:1}

  /* Karşılaştırma: masaüstünde tablo, mobilde alt alta kartlar */
  .ih-cmp-table{display:none}
  .ih-cmp-list{display:block}
  @media (min-width:760px){.ih-cmp-table{display:block}.ih-cmp-list{display:none}}
  .ih-cmp-table table{width:100%;border-collapse:collapse;font-size:14px}
  .ih-cmp-table th,.ih-cmp-table td{padding:12px 14px;border-top:1px solid #f1f5f9}
  .ih-cmp-table td.v{text-align:center;font-weight:700;color:#1f2937;width:17%}
  .ih-cmp-group td{background:#faf5ff;font-weight:900;color:#111827;padding-top:16px}
  .ih-cmp-row{padding:14px 0;border-top:1px solid #f1f5f9}
  .ih-cmp-row:first-child{border-top:0}
  .ih-cmp-label{font-size:14px;color:#374151;font-weight:700;margin-bottom:8px}
  .ih-cmp-vals{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
  .ih-cmp-val{background:#f8fafc;border-radius:12px;padding:8px 6px;text-align:center;font-size:12px;font-weight:700;color:#1f2937;line-height:1.3}
  .ih-cmp-val i{display:block;font-style:normal;font-size:16px;margin-bottom:2px}
  .ih-ok{color:#15803d;font-weight:900}
  .ih-no{color:#cbd5e1}

  /* Paket ayrıntıları (açılır-kapanır) */
  .ih-acc{padding:0;overflow:hidden}
  .ih-acc>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:14px;padding:20px 22px}
  .ih-acc>summary::-webkit-details-marker{display:none}
  .ih-acc>summary .ih-chev{margin-left:auto;flex:none;width:34px;height:34px;border-radius:999px;background:#faf5ff;color:#701a75;display:flex;align-items:center;justify-content:center;font-weight:900;transition:transform .2s}
  .ih-acc[open]>summary .ih-chev{transform:rotate(180deg)}
  .ih-acc-body{padding:0 22px 24px;border-top:1px solid #f1f5f9}
  @media (min-width:768px){.ih-acc>summary{padding:24px 28px}.ih-acc-body{padding:0 28px 28px}}
  .ih-items{display:grid;gap:10px 28px;grid-template-columns:1fr;margin:0;padding:0;list-style:none}
  @media (min-width:900px){.ih-items{grid-template-columns:repeat(2,minmax(0,1fr))}}
  .ih-items li{display:flex;gap:10px;font-size:14px;line-height:1.6;color:#4b5563}
  .ih-items li:before{content:"✓";color:#a21caf;font-weight:900;flex:none}
  .ih-items li b{color:#111827}
  .ih-group-title{font-weight:900;color:#111827;margin:22px 0 10px;font-size:16px}

  /* Adımlar / SSS */
  .ih-step-head{display:flex;align-items:center;gap:10px;font-weight:900;color:#111827}
  .ih-faq{padding:0}
  .ih-faq>summary{list-style:none;cursor:pointer;padding:18px 20px;font-weight:800;color:#111827;display:flex;gap:12px;align-items:center}
  .ih-faq>summary::-webkit-details-marker{display:none}
  .ih-faq>summary:after{content:"+";margin-left:auto;font-size:22px;color:#a21caf;font-weight:900}
  .ih-faq[open]>summary:after{content:"–"}
  .ih-faq p{padding:0 20px 18px;margin:0;font-size:14px;line-height:1.7;color:#4b5563}
  .ih-btn{display:inline-flex;align-items:center;justify-content:center;font-weight:900;border-radius:14px;padding:13px 20px;text-align:center}
  @media (max-width:520px){.ih-btns .ih-btn{width:100%}}
</style>
<script>
  // "Paketin tüm ayrıntıları" bağlantısı ilgili paketin bölümünü açar.
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href^="#paket-"]');
    if (!a) return;
    var d = document.querySelector(a.getAttribute('href'));
    if (d && d.tagName === 'DETAILS') d.open = true;
  });
  document.addEventListener('DOMContentLoaded', function () {
    if (location.hash.indexOf('#paket-') === 0) {
      var d = document.querySelector(location.hash);
      if (d && d.tagName === 'DETAILS') d.open = true;
    }
  });
</script>
@endonce
