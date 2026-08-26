{{--
  26 Agustos 2026: oda tipi (yasli bakim/huzurevi), yas grubu ve program
  suresi (cocuk bakim/kres-anaokulu) fiyat bloklari AYNI yapiyi kullaniyor -
  bkz. Admin\FacilityController::syncPriceOptions() ayni tarihli yorum.
  Parametreler: $optionsTitle, $optionsDescription, $optionsTypes (sabit
  key=>label dizisi), $optionsInputKey (form alani adi), $optionsColumn
  (mevcut kayitlarda arama yapilacak sutun), $optionsExisting (facility'nin
  ilgili iliskisi, key'e gore).
--}}
<div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
  <label class="text-sm font-semibold block mb-1">{{ $optionsTitle }}</label>
  <p class="text-xs text-gray-500 mb-3">{{ $optionsDescription }}</p>
  <div class="grid sm:grid-cols-2 gap-3">
    @foreach($optionsTypes as $key => $label)
      @php $opt = $optionsExisting->get($key); @endphp
      <div class="bg-white border rounded-lg p-3">
        <div class="font-semibold text-sm mb-2">{{ $label }}</div>
        <div class="grid grid-cols-2 gap-2">
          <input type="number" step="0.01" min="0" name="{{ $optionsInputKey }}[{{ $key }}][price_min]" value="{{ old($optionsInputKey.'.'.$key.'.price_min', $opt->price_min ?? '') }}" placeholder="Min TL" class="border rounded-lg px-2 py-1.5 text-sm w-full">
          <input type="number" step="0.01" min="0" name="{{ $optionsInputKey }}[{{ $key }}][price_max]" value="{{ old($optionsInputKey.'.'.$key.'.price_max', $opt->price_max ?? '') }}" placeholder="Maks TL" class="border rounded-lg px-2 py-1.5 text-sm w-full">
        </div>
      </div>
    @endforeach
  </div>
</div>
