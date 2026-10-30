@extends('admin.layout')
@section('title', $tur === 'kurum' ? 'CRM - Kurumlar' : 'CRM - Doktorlar')

@section('content')
{{-- 30 Ekim 2026: kullanıcının talebi - pazarlamacı CRM'inin ayrıntılı admin hali.
     Pazarlamacı ekranlarına dokunulmaz; aynı not tabloları kullanılır.
     Inline stil: yeni Tailwind sınıfları FTP deploy'da derlenmiyor. --}}
<style>
  .crm-sekme{display:inline-flex;align-items:center;gap:6px;border:1.5px solid #e5e7eb;border-radius:12px;padding:7px 14px;font-size:14px;font-weight:700;text-decoration:none;background:#fff;color:#374151}
  .crm-sekme.aktif{background:#111827;border-color:#111827;color:#fff}
  .crm-sekme small{font-size:12px;opacity:.6}
  .crm-sec{border:1.5px solid #d6dde0;border-radius:10px;padding:7px 10px;font-size:14px;background:#fff;color:#111827}
  .crm-kart{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.07);padding:16px;scroll-margin-top:80px}
  .crm-rozet{display:inline-block;font-size:12px;font-weight:700;border-radius:999px;padding:2px 9px;background:#f3f4f6;color:#374151}
  .crm-dg{display:inline-flex;align-items:center;gap:4px;border:1.5px solid #e5e7eb;border-radius:9px;padding:4px 10px;font-size:12.5px;font-weight:800;text-decoration:none;color:#374151;background:#fff}
  .crm-etk{display:block;font-size:12px;font-weight:700;color:#6b7280;margin-bottom:3px}
  .crm-girdi{border:1.5px solid #d6dde0;border-radius:9px;padding:6px 9px;font-size:14px;background:#fff;color:#111827}
  .crm-ozet{background:#fff;border-radius:14px;padding:12px 16px;box-shadow:0 1px 3px rgba(0,0,0,.06);min-width:150px}
  .crm-ozet b{display:block;font-size:22px;color:#111827}
  .crm-ozet span{font-size:12.5px;color:#6b7280;font-weight:700}
  .crm-gecmis div{font-size:12.5px;color:#4b5563;padding:3px 0;border-bottom:1px dashed #eef0f2}
</style>
@php
  $rota = $tur === 'kurum' ? 'admin.crm.index' : 'admin.crm.doctors';
  $filtreAlanlari = ['musteri', 'yayin', 'telefon', 'city', 'district', 'category', 'branch', 'site', 'ara', 'kim', 'son_gun', 'aranmayan_gun', 'sira'];
  $filtre = array_filter(request()->only($filtreAlanlari), fn ($v) => $v !== null && $v !== '');
  $yazar = fn ($n) => $n->marketer?->name ? '🧑‍💼 '.$n->marketer->name : ($n->admin_id ? '🛡️ '.($n->admin?->name ?? 'Admin') : '—');
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
  <div>
    <h1 class="text-2xl font-bold">CRM — Arama Listesi</h1>
    <div style="color:#6b7280;font-size:14px;">Pazarlamacılarla aynı kayıtlar: sizin notlarınızı onlar, onlarınkini siz görürsünüz.</div>
  </div>
  <div style="display:flex;gap:6px;">
    <a href="{{ route('admin.crm.index') }}" class="crm-sekme {{ $tur === 'kurum' ? 'aktif' : '' }}">🏠 Kurumlar</a>
    <a href="{{ route('admin.crm.doctors') }}" class="crm-sekme {{ $tur === 'doktor' ? 'aktif' : '' }}">🩺 Doktorlar</a>
  </div>
</div>

@if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:14px;">{{ session('success') }}</div>@endif
@if($errors->any())<div style="background:#fef2f2;color:#991b1b;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:14px;">{{ $errors->first() }}</div>@endif

@if($tur === 'doktor')@include('admin.crm._doktor_rehber')@endif

<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
  <div class="crm-ozet"><b>{{ $ozet['bugun_randevu'] }}</b><span>📅 Bugün randevulu</span></div>
  <div class="crm-ozet"><b style="color:{{ $ozet['geciken_randevu'] ? '#b45309' : '#111827' }}">{{ $ozet['geciken_randevu'] }}</b><span>⏰ Saati geçmiş randevu</span></div>
  <div class="crm-ozet"><b>{{ $ozet['bugun_gorusme'] }}</b><span>☎️ Bugün tüm görüşmeler</span></div>
  <div class="crm-ozet"><b>{{ $ozet['bugun_benim'] }}</b><span>🛡️ Bugün sizin görüşmeleriniz</span></div>
  <div class="crm-ozet"><b>{{ $ozet['hafta_gorusme'] }}</b><span>Son 7 gün görüşme</span></div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
  @foreach($groups as $key => $def)
    <a href="{{ route($rota, array_merge($filtre, ['group' => $key])) }}" class="crm-sekme {{ $group === $key ? 'aktif' : '' }}">{{ $def['title'] }} <small>{{ number_format($groupCounts[$key] ?? 0, 0, ',', '.') }}</small></a>
  @endforeach
</div>

<form method="GET" data-district-map='@json($districtMap)' class="js-location-filter" style="background:#fff;border-radius:14px;padding:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:14px;">
  <input type="hidden" name="group" value="{{ $group }}">
  <input type="search" name="ara" value="{{ request('ara') }}" placeholder="İsim veya telefon ara" class="crm-sec" style="min-width:200px;">
  @if($tur === 'kurum')
    <select name="musteri" class="crm-sec" onchange="this.form.submit()">
      @foreach(\App\Http\Controllers\Admin\CrmController::MUSTERI as $mk => $ml)<option value="{{ $mk }}" @selected(request('musteri', 'on_kayitli') === $mk)>{{ $ml }}</option>@endforeach
    </select>
  @else
    <select name="yayin" class="crm-sec" onchange="this.form.submit()">
      <option value="">Yayındaki doktorlar</option>
      <option value="hepsi" @selected(request('yayin') === 'hepsi')>Yayında olmayanlar dahil</option>
    </select>
  @endif
  <select name="city" class="js-city crm-sec" onchange="this.form.submit()">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)<option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>@endforeach
  </select>
  <select name="district" data-selected="{{ request('district') }}" class="js-district crm-sec" onchange="this.form.submit()" @if(! request('city')) disabled @endif>
    <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
    @if(request('city'))
      @foreach(districts_for_city(optional($cities->firstWhere('slug', request('city')))->name ?? '') as $ilce)<option value="{{ $ilce }}" @selected(request('district') === $ilce)>{{ $ilce }}</option>@endforeach
    @endif
  </select>
  @if($tur === 'kurum')
    <select name="category" class="crm-sec" onchange="this.form.submit()">
      <option value="">Tüm Kategoriler</option>
      @foreach($categories as $c)<option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>@endforeach
    </select>
  @else
    <select name="branch" class="crm-sec" onchange="this.form.submit()">
      <option value="">Tüm Branşlar</option>
      @foreach($branches as $b)<option value="{{ $b }}" @selected(request('branch') === $b)>{{ $b }}</option>@endforeach
    </select>
  @endif
  <select name="site" class="crm-sec" onchange="this.form.submit()">
    <option value="">İnternet sitesi: Tümü</option>
    <option value="eksik" @selected(request('site') === 'eksik')>Kendi sitesi olmayanlar</option>
    @foreach(\App\Models\Facility::WEBSITE_STATUSES as $wk => $wl)<option value="{{ $wk }}" @selected(request('site') === $wk)>{{ $wl }}</option>@endforeach
    <option value="bilinmiyor" @selected(request('site') === 'bilinmiyor')>Henüz kontrol edilmedi</option>
  </select>
  <select name="kim" class="crm-sec" onchange="this.form.submit()">
    <option value="">Kim aradı: Herkes</option>
    <option value="hic" @selected(request('kim') === 'hic')>Hiç aranmamış</option>
    <option value="ben" @selected(request('kim') === 'ben')>Benim aradıklarım</option>
    <option value="admin" @selected(request('kim') === 'admin')>Adminlerin aradıkları</option>
    @foreach($marketers as $m)<option value="{{ $m->id }}" @selected(request('kim') === (string) $m->id)>Pazarlamacı: {{ $m->name }}</option>@endforeach
  </select>
  <select name="son_gun" class="crm-sec" onchange="this.form.submit()">
    <option value="">Son görüşme: Tümü</option>
    @foreach([1 => 'Son 24 saat', 7 => 'Son 7 gün', 30 => 'Son 30 gün'] as $gk => $gl)<option value="{{ $gk }}" @selected(request('son_gun') === (string) $gk)>{{ $gl }}</option>@endforeach
  </select>
  <select name="aranmayan_gun" class="crm-sec" onchange="this.form.submit()">
    <option value="">Aranmayan süre: Tümü</option>
    @foreach([7 => '7 günden uzun süredir aranmayan', 30 => '30 günden uzun', 90 => '90 günden uzun'] as $gk => $gl)<option value="{{ $gk }}" @selected(request('aranmayan_gun') === (string) $gk)>{{ $gl }}</option>@endforeach
  </select>
  <select name="telefon" class="crm-sec" onchange="this.form.submit()">
    <option value="">Telefonu olanlar</option>
    <option value="hepsi" @selected(request('telefon') === 'hepsi')>Telefonsuzlar dahil</option>
  </select>
  <select name="sira" class="crm-sec" onchange="this.form.submit()">
    @foreach(\App\Http\Controllers\Admin\CrmController::SIRALAMA as $sk => $sl)<option value="{{ $sk }}" @selected(request('sira', ($groups[$group]['callback'] ?? false) ? 'randevu' : 'son_gorusme') === $sk)>Sırala: {{ $sl }}</option>@endforeach
  </select>
  <button type="submit" style="background:#111827;color:#fff;font-weight:800;border:0;border-radius:10px;padding:8px 16px;cursor:pointer;">Ara</button>
  @if($filtre)<a href="{{ route($rota, ['group' => $group]) }}" style="font-size:13px;font-weight:700;color:#6b7280;text-decoration:underline;">Filtreleri temizle</a>@endif
  <span style="margin-left:auto;font-size:13px;color:#6b7280;font-weight:700;">{{ number_format($kayitlar->total(), 0, ',', '.') }} kayıt</span>
</form>
@include('themes._shared.partials.location-filter-script')

<div style="display:grid;gap:12px;">
@forelse($kayitlar as $k)
  @php
    $doktor = $tur === 'doktor';
    $ad = $doktor ? trim(($k->title ? $k->title.' ' : '').$k->name) : $k->name;
    $wa = classify_phone_type($k->phone) === 'mobile' ? normalize_whatsapp_number($k->phone) : '';
    $site = $doktor ? ($doktorSiteleri[$k->id] ?? null) : $k->website;
    $notlar = $k->marketingNotes;
    $sonNot = $notlar->first();
  @endphp
  <div class="crm-kart" id="k{{ $k->id }}">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;">
      <div style="min-width:0;flex:1;">
        <div style="font-weight:900;font-size:16px;color:#111827;">{{ $ad }}</div>
        <div style="font-size:13.5px;color:#6b7280;">
          {{ $doktor ? ($k->branch_label ?: $k->branch) : $k->category?->name }} · {{ $k->district }} / {{ $k->city?->name }}
          @if($doktor && $k->clinic_name) · 🏥 {{ $k->clinic_name }}@endif
        </div>
        @if($k->address)<div style="font-size:12.5px;color:#9ca3af;margin-top:2px;">📍 {{ $k->address }}</div>@endif
        <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:8px;">
          @if($k->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $k->phone) }}" class="crm-dg" style="border-color:#93c5fd;color:#1d4ed8;">📞 {{ $k->phone }}</a>
            @if($wa)<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="crm-dg" style="border-color:#86efac;color:#166534;">💬 WhatsApp</a>@endif
          @else
            <span class="crm-rozet">Telefon yok</span>
          @endif
          @if($k->website_url)<a href="{{ $k->website_url }}" target="_blank" rel="noopener nofollow" class="crm-dg">🌐 Kendi sitesi</a>@endif
          @if($k->website_status)@include('partials.website-status-badge', ['facility' => $k])@endif
        </div>
      </div>
      <div style="text-align:right;display:grid;gap:4px;align-content:start;justify-items:end;">
        @if($k->marketing_status)<span class="crm-rozet" style="background:#111827;color:#fff;">{{ $statusLabels[$k->marketing_status] ?? $k->marketing_status }}</span>@else<span class="crm-rozet">Hiç aranmadı</span>@endif
        @if($k->marketing_status_at)<span style="font-size:12px;color:#6b7280;">Son görüşme: {{ $k->marketing_status_at->diffForHumans() }}</span>@endif
        @if(! $doktor)
          @if($k->is_broker_managed)<span class="crm-rozet" style="background:#dbeafe;color:#1e40af;">Anlaşmalı</span>@elseif($k->is_claimed)<span class="crm-rozet" style="background:#dcfce7;color:#166534;">Sahiplenilmiş</span>@else<span class="crm-rozet">Ön kayıtlı</span>@endif
          @if($k->is_subscribed)<span class="crm-rozet" style="background:#fef3c7;color:#92400e;">Abone / üye</span>@endif
          @if(! $k->is_published)<span class="crm-rozet" style="background:#fee2e2;color:#991b1b;">Yayında değil</span>@endif
        @else
          @if(! $k->is_published)<span class="crm-rozet" style="background:#fee2e2;color:#991b1b;">Yayında değil</span>@endif
          @if($k->supporter_until && $k->supporter_until->isFuture())<span class="crm-rozet" style="background:#fdf2f8;color:#9d174d;">💗 Destekçi ({{ $k->supporter_until->format('d.m.Y') }})</span>@endif
        @endif
        @if($site)<span class="crm-rozet" style="{{ $site->isLive() ? 'background:#dcfce7;color:#166534;' : '' }}">Bizim site: {{ $site->isLive() ? 'Yayında'.($site->isPro() ? ' · Profesyonel' : '') : 'Taslak' }}</span>@endif
        @if($k->marketing_brochure_requested !== null)<span style="font-size:12px;color:#6b7280;">Broşür: {{ $k->marketing_brochure_requested ? 'İstiyor' : 'İstemiyor' }}</span>@endif
      </div>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
      @if($doktor)
        <a href="{{ route('admin.experts.edit', $k) }}" target="_blank" class="crm-dg">📝 Kaydı aç</a>
        <a href="{{ url('/doktor/'.$k->slug) }}" target="_blank" rel="noopener" class="crm-dg">👁 Sayfası</a>
        <a href="{{ route('admin.experts.website.edit', $k) }}" target="_blank" class="crm-dg">🖥 Site ayarları</a>
        <a href="{{ route('admin.expert-websites.demo', $k) }}" target="_blank" class="crm-dg" style="border-color:#86efac;color:#166534;">💬 Demo site gönder</a>
      @else
        <a href="{{ route('admin.facilities.edit', $k) }}" target="_blank" class="crm-dg">📝 Kaydı aç</a>
        <a href="{{ url('/kurumlar/'.$k->slug) }}" target="_blank" rel="noopener" class="crm-dg">👁 Sayfası</a>
        <a href="{{ route('admin.facility-websites.edit', $k) }}" target="_blank" class="crm-dg">🖥 Site ayarları</a>
        <a href="{{ route('admin.facility-websites.demo', $k) }}" target="_blank" class="crm-dg" style="border-color:#86efac;color:#166534;">💬 Demo site gönder</a>
        <a href="{{ route('admin.crm.offer-page', $k) }}" target="_blank" rel="noopener" class="crm-dg" style="border-color:#e9d5ff;color:#a21caf;">💛 Teklif sayfası</a>
      @endif
    </div>

    @if($k->marketing_callback_at)
      <div style="margin-top:10px;font-size:13px;font-weight:800;color:{{ $k->marketing_callback_at->isFuture() ? '#b45309' : '#9ca3af' }};">📅 Aranacak zaman: {{ $k->marketing_callback_at->format('d.m.Y H:i') }}{{ $k->marketing_callback_at->isPast() ? ' (geçti)' : '' }}</div>
    @endif

    @if($notlar->isNotEmpty())
      <details style="margin-top:10px;" @if($notlar->count() <= 3) open @endif>
        <summary style="cursor:pointer;font-size:13px;font-weight:800;color:#374151;">Görüşme geçmişi ({{ $notlar->count() }})</summary>
        <div class="crm-gecmis" style="margin-top:6px;">
          @foreach($notlar as $n)
            <div>
              <b style="color:#111827;">{{ $statusLabels[$n->status] ?? $n->status }}</b>
              · {{ $n->created_at->format('d.m.Y H:i') }} · {{ $yazar($n) }}
              @if($n->contactLabel()) · 👤 {{ $n->contactLabel() }}@endif
              @if($doktor && $n->package_interest) · 📦 {{ \App\Models\ExpertMarketingNote::PACKAGES[$n->package_interest] ?? $n->package_interest }}@endif
              @if($n->website_status) · 🌐 {{ \App\Models\FacilityMarketingNote::WEBSITE_STATUSES[$n->website_status] ?? $n->website_status }}@endif
              @if($n->site_offer_interest) · {{ \App\Models\FacilityMarketingNote::SITE_OFFER_INTEREST[$n->site_offer_interest] ?? '' }}@endif
              @if($n->brochure_requested !== null) · Broşür: {{ $n->brochure_requested ? 'istiyor' : 'istemiyor' }}@endif
              @if($n->callback_at) · 📅 {{ $n->callback_at->format('d.m.Y H:i') }}@endif
              @if($n->note)<div style="border:0;padding:2px 0 0;color:#111827;">“{{ $n->note }}”</div>@endif
            </div>
          @endforeach
        </div>
      </details>
    @endif

    <details style="margin-top:10px;">
      <summary style="cursor:pointer;font-size:13.5px;font-weight:900;color:#0d6e6a;">✍️ Görüşme notu ekle</summary>
      <form method="POST" action="{{ $doktor ? route('admin.crm.doctors.store-note', $k) : route('admin.crm.store-note', $k) }}" style="margin-top:10px;display:grid;gap:10px;">
        @csrf
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
          <div><span class="crm-etk">Durum</span>
            <select name="status" required class="crm-girdi">@foreach($statusLabels as $sv => $sl)<option value="{{ $sv }}" @selected(($sonNot?->status ?? 'called') === $sv)>{{ $sl }}</option>@endforeach</select>
          </div>
          <div><span class="crm-etk">Broşür dağıtımı</span>
            <select name="brochure_requested" class="crm-girdi"><option value="">Sorulmadı / geçiliyor</option><option value="1">İstiyor</option><option value="0">İstemiyor</option></select>
          </div>
          <div><span class="crm-etk">Ne zaman tekrar aranacak</span><input type="datetime-local" name="callback_at" class="crm-girdi"></div>
          @if($k->marketing_callback_at)<label style="font-size:13px;font-weight:700;display:inline-flex;gap:5px;align-items:center;"><input type="checkbox" name="randevu_kapat" value="1"> Eski randevuyu kapat</label>@endif
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
          <div style="flex:1;min-width:180px;"><span class="crm-etk">Görüşülen kişinin adı</span><input type="text" name="contact_name" maxlength="120" class="crm-girdi" style="width:100%;" value="{{ $sonNot?->contact_name }}" placeholder="ör. Ayşe Yılmaz"></div>
          <div><span class="crm-etk">Konumu</span>
            <div style="display:flex;gap:10px;font-size:14px;padding:6px 0;">@foreach(\App\Models\FacilityMarketingNote::CONTACT_ROLES as $rv => $rl)<label style="display:inline-flex;gap:4px;align-items:center;"><input type="radio" name="contact_role" value="{{ $rv }}" @checked($sonNot?->contact_role === $rv)> {{ $rl }}</label>@endforeach</div>
          </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px 22px;">
          <div><span class="crm-etk">Web sitesi var mı?</span>
            <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:14px;">@foreach(\App\Models\FacilityMarketingNote::WEBSITE_STATUSES as $wv => $wl)<label style="display:inline-flex;gap:4px;align-items:center;"><input type="radio" name="website_status" value="{{ $wv }}"> {{ $wl }}</label>@endforeach</div>
          </div>
          <div><span class="crm-etk">"Size internet sitesi de yapıyoruz" teklifine</span>
            <div style="display:flex;gap:10px;font-size:14px;"><label style="display:inline-flex;gap:4px;align-items:center;"><input type="radio" name="site_offer_interest" value="ilgilendi"> İlgilendi</label><label style="display:inline-flex;gap:4px;align-items:center;"><input type="radio" name="site_offer_interest" value="ilgilenmedi"> İlgilenmedi</label></div>
          </div>
          @if($doktor)
            <div><span class="crm-etk">İlgilendiği paket</span>
              <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:14px;">@foreach(\App\Models\ExpertMarketingNote::PACKAGES as $pv => $pl)<label style="display:inline-flex;gap:4px;align-items:center;"><input type="radio" name="package_interest" value="{{ $pv }}"> {{ $pl }}</label>@endforeach</div>
            </div>
          @endif
        </div>
        <div><span class="crm-etk">Not</span><textarea name="note" maxlength="2000" rows="2" class="crm-girdi" style="width:100%;" placeholder="Görüşme notu — ör. Müdür Salı 14:00'te arayın dedi, fiyat listesi istedi."></textarea></div>
        <div><button type="submit" style="background:#111827;color:#fff;font-weight:800;border:0;border-radius:10px;padding:9px 20px;cursor:pointer;">Kaydet</button></div>
      </form>
    </details>
  </div>
@empty
  <div class="crm-kart" style="text-align:center;color:#9ca3af;">Bu filtrede kayıt yok.</div>
@endforelse
</div>
<div style="margin-top:16px;">{{ $kayitlar->links() }}</div>
@endsection
