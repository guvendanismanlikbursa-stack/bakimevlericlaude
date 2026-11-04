{{-- 4 Kasım 2026: kullanıcının talebi - konum sayfa açılınca ya da form doldurulurken değil, form
     GÖNDERİLİRKEN sorulur (isteğe bağlı). İzin verilmese de form birkaç saniye içinde gönderilir.
     Beklenen: $lat, $lng (gizli alan id'leri), isteğe bağlı $not (açıklama), $bilgiId (mevcut not öğesi). --}}
<script>
(function () {
  var lat = document.getElementById(@json($lat)), lng = document.getElementById(@json($lng));
  if (!lat || !lat.form || !navigator.geolocation) return;
  var form = lat.form, soruldu = false, not = @json($not ?? 'Konumunuz soruluyor (isteğe bağlı)…');
  var bilgi = document.getElementById(@json($bilgiId ?? ''));
  if (!bilgi) {
    bilgi = document.createElement('p');
    bilgi.className = 'text-xs text-gray-500 hidden mt-2';
    var buton = form.querySelector('button[type=submit], button:not([type])');
    if (buton && buton.parentNode) { buton.parentNode.insertBefore(bilgi, buton.nextSibling); } else { form.appendChild(bilgi); }
  }
  form.addEventListener('submit', function (e) {
    if (soruldu || lat.value) return;
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;
    e.preventDefault();
    soruldu = true;
    bilgi.textContent = not;
    bilgi.classList.remove('hidden');
    var bitti = false;
    function gonder() { if (bitti) return; bitti = true; HTMLFormElement.prototype.submit.call(form); }
    navigator.geolocation.getCurrentPosition(function (p) { lat.value = p.coords.latitude; lng.value = p.coords.longitude; gonder(); }, gonder, { timeout: 6000, maximumAge: 600000 });
    setTimeout(gonder, 12000);
  });
})();
</script>
