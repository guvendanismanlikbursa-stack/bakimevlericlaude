{{-- 25 Ekim 2026: kullanıcının talebi - anasayfadaki bölüm kartlarına 4. kart olarak
     "Özel Doktorlar". Yalnızca admin "Doktorlar bölümünü yayına al" dediyse görünür
     (bkz. doctors_live()); kapatınca kartlar eski 3'lü haline döner.
     Tailwind FTP yüklemesinde yeniden derlenmediği için düzen gömülü stille:
     telefonda 2x2 ve kompakt (filtreler ilk ekranda görünsün), masaüstünde 4'lü tek satır.
     $kose: temanın kart köşe yuvarlaklığı (px). $secili: Doktorlar sayfasındayken true. --}}
<style>
  .bolum-kartlari-4{display:grid !important;grid-template-columns:repeat(2,minmax(0,1fr)) !important;gap:10px !important;}
  @media (min-width:640px){.bolum-kartlari-4{grid-template-columns:repeat(4,minmax(0,1fr)) !important;gap:12px !important;}}
  @media (max-width:639px){
    .bolum-kartlari-4 > a{padding:10px 12px !important;min-height:0 !important;}
    .bolum-kartlari-4 .bk-alt{display:none !important;}
    .bolum-kartlari-4 .bk-baslik{margin-top:6px !important;font-size:14px !important;line-height:1.25 !important;}
    .bolum-kartlari-4 .bk-ikon{padding:6px !important;}
    .bolum-kartlari-4 .bk-ikon svg{width:20px !important;height:20px !important;}
    .bolum-kartlari-4 .bk-secili{display:none !important;}
  }
  .doktor-bolum-karti:hover{transform:translateY(-2px);box-shadow:0 10px 24px -12px rgba(13,110,106,.45);}
</style>
<a href="{{ brand_route('experts.index') }}" class="doktor-bolum-karti section-card-beam" style="--beam-color:#0d9488;position:relative;display:block;overflow:hidden;border-radius:{{ $kose ?? 12 }}px;padding:16px;text-decoration:none;color:#0f172a;{{ ! empty($secili) ? 'border:2px solid #0d9488;box-shadow:0 8px 22px -12px rgba(13,110,106,.55);' : 'border:1.5px solid #cfe7e4;' }}background:linear-gradient(135deg,#ffffff 0%,#effaf8 60%,#e3f4f1 100%);transition:transform .15s,box-shadow .15s;"{{ ! empty($secili) ? ' aria-current=page' : '' }}>
  @if(! empty($secili))<span class="bk-secili" style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:800;border-radius:6px;padding:3px 8px;background:#e0f2f1;color:#0d6e6a;">Seçili</span>@endif
  <span aria-hidden="true" style="position:absolute;right:-14px;bottom:-22px;font-size:84px;line-height:1;opacity:.08;">🩺</span>
  <span class="bk-ikon" style="position:relative;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;padding:8px;background:#e0f2f1;font-size:22px;line-height:1;">🩺</span>
  <div class="bk-baslik" style="position:relative;font-weight:900;margin-top:12px;">Özel Doktorlar</div>
  <div class="bk-alt" style="position:relative;font-size:12px;margin-top:4px;color:#475569;">Pediatri · Geriatri · Fizik Tedavi</div>
</a>
