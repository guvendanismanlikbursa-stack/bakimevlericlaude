@extends('admin.layout')
@section('title', 'Ayarlar')

@section('content')
<h1 class="text-2xl font-bold mb-6">Platform Ayarları</h1>
<p class="text-sm text-gray-500 mb-4">Bu ayarlar 3 sitenin de ortak kullandığı teklif ücreti ve fiyat segmentleridir; banka hesapları site bazında ayrı ayrı tanımlanır.</p>

<form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 max-w-2xl">
  @csrf @method('PUT')

  <div class="md:col-span-2">
    <h2 class="text-lg font-bold mb-1">Banka Hesap Bilgileri (site bazında)</h2>
    <p class="text-sm text-gray-500 mb-4">Kurum panelindeki "Bakiyem" sayfasında, kurum sahibi hangi siteden erişiyorsa o sitenin banka hesabı gösterilir.</p>
  </div>
  @foreach($bankAccounts as $slug => $account)
    <div class="md:col-span-2 grid md:grid-cols-2 gap-4 border border-gray-100 rounded-lg p-4">
      <div class="md:col-span-2 font-semibold text-sm">{{ $account['label'] }}</div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Banka Adı</label>
        <input type="text" name="bank_name_{{ $slug }}" value="{{ old('bank_name_'.$slug, $account['bank_name']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Hesap Sahibi</label>
        <input type="text" name="bank_account_holder_{{ $slug }}" value="{{ old('bank_account_holder_'.$slug, $account['bank_account_holder']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">IBAN</label>
        <input type="text" name="bank_iban_{{ $slug }}" value="{{ old('bank_iban_'.$slug, $account['bank_iban']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
      </div>
    </div>
  @endforeach

  <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
    <label class="text-sm font-medium">Ücretsiz Hak Bitince Teklif Başına Ücret (₺)</label>
    <input type="number" step="0.01" name="quote_price" value="{{ old('quote_price', $settings['quote_price']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
    <p class="text-xs text-gray-400 mt-1">Bu, genel/varsayılan ücrettir. Belirli bir kurum için farklı bir teklif ücreti tanımlamak isterseniz Kurumlar &gt; ilgili kurumun düzenleme sayfasındaki "Bakiye / Hak" kartından yapabilirsiniz.</p>
  </div>

  <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
    <h2 class="text-lg font-bold mb-1">Ücretlendirme Segmentleri</h2>
    <p class="text-sm text-gray-500 mb-4">Kurumun aylık başlangıç ücreti (price_min) bu eşiklere göre 3 sitede de otomatik segment rozetiyle gösterilir. Eşiğin altı bir önceki segmenttir.</p>
  </div>
  <div>
    <label class="text-sm font-medium">🟢 Ekonomik → 🔵 Standart eşiği (₺)</label>
    <input type="number" step="1" name="price_tier_standart_min" value="{{ old('price_tier_standart_min', $settings['price_tier_standart_min']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
    <p class="text-xs text-gray-400 mt-1">Bu tutarın altı Ekonomik, üstü Standart.</p>
  </div>
  <div>
    <label class="text-sm font-medium">🔵 Standart → 🟣 Premium eşiği (₺)</label>
    <input type="number" step="1" name="price_tier_premium_min" value="{{ old('price_tier_premium_min', $settings['price_tier_premium_min']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>
  <div>
    <label class="text-sm font-medium">🟣 Premium → 🟡 Ultra Premium eşiği (₺)</label>
    <input type="number" step="1" name="price_tier_ultra_min" value="{{ old('price_tier_ultra_min', $settings['price_tier_ultra_min']) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
    <h2 class="text-lg font-bold mb-1">WhatsApp Butonu</h2>
    <p class="text-sm text-gray-500 mb-4">3 sitede de sağ altta görünen WhatsApp butonunun numarası ve tıklayınca açılan hazır mesaj. {marka} yazan yer otomatik olarak sitenin adıyla değişir.</p>
  </div>
  <div class="md:col-span-2">
    <label class="text-sm font-medium">WhatsApp Numarası (ülke kodu + numara, boşluksuz, başında + olmadan)</label>
    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number']) }}" placeholder="908503087991" required class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>
  <div class="md:col-span-2">
    <label class="text-sm font-medium">Hazır Mesaj</label>
    <textarea name="whatsapp_message" rows="2" required class="border rounded-lg px-3 py-2 w-full mt-1">{{ old('whatsapp_message', $settings['whatsapp_message']) }}</textarea>
  </div>

  <div class="md:col-span-2">
    <button class="bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold">Kaydet</button>
  </div>
</form>
@endsection
