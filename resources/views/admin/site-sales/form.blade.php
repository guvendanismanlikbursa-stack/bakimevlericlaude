@extends('admin.layout')
@section('title', $satis->exists ? 'Satışı Düzenle' : 'Satış Ekle')

@section('content')
{{-- 25 Ekim 2026: kurum / doktor sitesi satış kaydı formu. --}}
@php
  $alan = 'display:block;width:100%;border:1.5px solid #d6dde0;border-radius:10px;padding:10px 12px;font:inherit;background:#fff;color:#111827;';
  $etiket = 'display:block;font-weight:800;font-size:14px;color:#111827;margin-bottom:4px;';
  $hata = 'color:#b91c1c;font-size:13px;margin-top:4px;';
@endphp
<div style="margin-bottom:16px;">
  <h1 class="text-2xl font-bold">{{ $satis->exists ? 'Satışı Düzenle' : 'Satış Ekle' }}</h1>
  <a href="{{ route('admin.site-sales.index') }}" style="font-size:14px;text-decoration:underline;color:#6b7280;">Site Satışları'na dön</a>
</div>
@if($sahip)
  <div style="background:#f8fafc;border-radius:12px;padding:12px 16px;margin-bottom:14px;font-size:14px;">
    <b>{{ $satis->owner_type === 'doktor' ? '🩺 '.trim(($sahip->title ? $sahip->title.' ' : '').$sahip->name) : '🏠 '.$sahip->name }}</b>
    <span style="color:#6b7280;">· {{ $sahip->city?->name }}</span>
  </div>
@endif
<form method="POST" action="{{ $satis->exists ? route('admin.site-sales.update', $satis) : route('admin.site-sales.store') }}" style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);display:grid;gap:16px;max-width:860px;">
  @csrf
  @if($satis->exists) @method('PUT') @endif
  <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <div>
      <label style="{{ $etiket }}">Kimin sitesi</label>
      <select name="owner_type" style="{{ $alan }}">
        @foreach(\App\Models\SiteSale::TURLER as $k => $v)<option value="{{ $k }}" @selected(old('owner_type', $satis->owner_type) === $k)>{{ $v }}</option>@endforeach
      </select>
    </div>
    <div>
      <label style="{{ $etiket }}">Kurum / doktor numarası</label>
      <input name="owner_id" type="number" value="{{ old('owner_id', $satis->owner_id) }}" required style="{{ $alan }}" placeholder="ör. 15536">
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">Kurum Siteleri / Doktor Siteleri listesindeki "💰 Satış" düğmesi bu alanı kendiliğinden doldurur.</div>
      @error('owner_id')<div style="{{ $hata }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label style="{{ $etiket }}">Paket</label>
      <select name="plan" style="{{ $alan }}">
        @foreach(\App\Models\SiteSale::PLANLAR as $k => $v)<option value="{{ $k }}" @selected(old('plan', $satis->plan) === $k)>{{ $v }}</option>@endforeach
      </select>
    </div>
    <div>
      <label style="{{ $etiket }}">Başlangıç</label>
      <input type="date" name="baslangic" value="{{ old('baslangic', optional($satis->baslangic)->format('Y-m-d')) }}" required style="{{ $alan }}">
    </div>
    <div>
      <label style="{{ $etiket }}">Bitiş</label>
      <input type="date" name="bitis" value="{{ old('bitis', optional($satis->bitis)->format('Y-m-d')) }}" style="{{ $alan }}">
      @error('bitis')<div style="{{ $hata }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label style="{{ $etiket }}">Alınan ücret (₺)</label>
      <input type="number" step="0.01" min="0" name="ucret" value="{{ old('ucret', $satis->ucret ?? 0) }}" required style="{{ $alan }}">
      @error('ucret')<div style="{{ $hata }}">{{ $message }}</div>@enderror
    </div>
    <div>
      <label style="{{ $etiket }}">Ödeme durumu</label>
      <select name="odeme" style="{{ $alan }}">
        @foreach(\App\Models\SiteSale::ODEME as $k => $v)<option value="{{ $k }}" @selected(old('odeme', $satis->odeme) === $k)>{{ $v }}</option>@endforeach
      </select>
    </div>
    <div>
      <label style="{{ $etiket }}">Satışı yapan pazarlamacı</label>
      <select name="marketer_id" style="{{ $alan }}">
        <option value="">— Yok / admin —</option>
        @foreach($marketers as $m)<option value="{{ $m->id }}" @selected((string) old('marketer_id', $satis->marketer_id) === (string) $m->id)>{{ $m->name }}</option>@endforeach
      </select>
    </div>
  </div>
  <div>
    <label style="{{ $etiket }}">Notlar</label>
    <textarea name="notlar" rows="4" maxlength="3000" style="{{ $alan }}" placeholder="ör. Havale ile ödedi, alan adı Kasım'da alınacak.">{{ old('notlar', $satis->notlar) }}</textarea>
  </div>
  <label style="display:flex;gap:8px;align-items:flex-start;font-size:14px;background:#f0fdfa;border-radius:10px;padding:10px 12px;">
    <input type="checkbox" name="siteye_uygula" value="1" @checked(old('siteye_uygula', ! $satis->exists)) style="margin-top:3px;">
    <span><b>Siteye uygula:</b> sitenin paketi ve bitiş tarihi bu satışa göre ayarlansın ve site yayına alınsın.</span>
  </label>
  <div><button type="submit" style="background:#0d6e6a;color:#fff;border:0;border-radius:12px;padding:12px 22px;font-weight:800;font-size:15px;cursor:pointer;">Kaydet</button></div>
</form>
@endsection
