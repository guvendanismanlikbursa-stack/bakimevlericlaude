<?php

namespace App\Providers;

use App\Models\ChatThread;
use App\Models\ContactMessage;
use App\Models\FacilityClaim;
use App\Models\FacilityInterestLead;
use App\Models\FacilityRegistration;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\JobApplication;
use App\Models\OfferRequest;
use App\Models\VisitServiceRequest;
use App\Models\WalletTopup;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerTurkceArama();

        // Aktif markayi tum public view'larda kullanilabilir hale getir
        View::composer('themes.*', function ($view) {
            if (app()->bound('currentBrand')) {
                $view->with('brand', app('currentBrand'));
            }
        });

        View::composer('layouts.brand', function ($view) {
            if (app()->bound('currentBrand')) {
                $view->with('brand', app('currentBrand'));
            }

            // canliyaal projesinden tasindi: header'da okunmamis bildirim rozeti
            $unread = 0;
            if (session('facility_user_id')) {
                $unread = FacilityUser::find(session('facility_user_id'))?->notifications()->unread()->count() ?? 0;
            } elseif (session('family_user_id')) {
                $unread = FamilyUser::find(session('family_user_id'))?->notifications()->unread()->count() ?? 0;
            }
            $view->with('unreadNotificationsCount', $unread);
        });

        // Admin layout'a bekleyen onay sayilarini ekle (sidebar badge'leri icin)
        View::composer('admin.*', function ($view) {
            $view->with('pendingClaimsCount', FacilityClaim::where('status', 'pending')->count());
            $view->with('pendingTopupsCount', WalletTopup::where('status', 'pending')->count());
            $view->with('pendingRegistrationsCount', FacilityRegistration::where('status', 'pending')->count());
            $view->with('unreadChatThreadsCount', ChatThread::where('unread_by_admin', true)->count());
            // 27 Agustos 2026: kullanicinin bildirdigi gercek eksiklik - "Yakınımı
            // Ziyaret Et" talepleri icin mail/push bildirimi gidiyordu ama panelin
            // KENDISINDE (sidebar linkinde) diger bolumlerdeki (Sahiplenme, Kayit,
            // Sohbet) gibi bir rozet/sayac YOKTU - admin panele girince yeni bir
            // talep oldugunu FARK ETMIYORDU. Ayni desen uygulandi.
            $view->with('newVisitServiceRequestsCount', VisitServiceRequest::where('status', 'yeni')->count());
            // 28 Eylul 2026: kullanicinin talebi - admin'e giden bildirimler
            // (pazarlamacidan kurum bildirimi vb.) artik panelin icinde de
            // gorunuyor, bkz. Admin\NotificationController.
            $adminId = session('admin_id');
            $admin = $adminId ? \App\Models\Admin::find($adminId) : null;
            $view->with('unreadAdminNotificationsCount', $admin?->notifications()->unread()->count() ?? 0);

            // 14 Ekim 2026: kullanicinin talebi - "admin tarafindaki mantik
            // catismalarinin hepsini duzelt - eksik/yarim bildirimler". Daha
            // once SADECE claims/registrations/visit-service/chat/topup
            // kendi ozel sidebar rozetini aliyordu, mail+push zaten giden
            // digerleri (iletisim mesaji, is basvurusu, kurum ilgi talebi,
            // pazarlamaci yonlendirmesi, anlasmali kurum olaylari, Pusula)
            // SADECE genel "Bildirimler" sayacinda gorunuyordu - admin hangi
            // TURDE yeni bir sey oldugunu sidebar'a bakip anlayamiyordu.
            // Kendi durum sutunu olanlar (is basvurusu/kurum ilgi talebi/
            // iletisim mesaji/teklif talebi) O sutundan, dogal bir "durum"
            // sutunu olmayanlar (pazarlamaci/anlasmali kurum/Pusula) ise
            // ayni notify_user() cagrisinin urettigi okunmamis bildirim
            // turune gore sayiliyor - TUM nav ogeleri icin TEK, tutarli kural.
            $view->with('pendingJobApplicationsCount', JobApplication::where('status', 'yeni')->count());
            $view->with('pendingReviewsCount', \App\Models\FacilityReview::where('status', 'pending')->count());
            $view->with('unreadContactMessagesCount', ContactMessage::where('is_read', false)->count());
            // 18 Ekim 2026: kullanıcının bildirdiği gerçek hata - sayaç yeni talep
            // yokken "2" gösteriyordu. Eskiden durum "yeni" kalan TÜM kayıtları
            // sayıyordu: kurumu silinmiş talepler (listede hiç görünmüyor) ve
            // sahipli kurum talepleri (kurumun kendi paneline gider, buradan
            // işlem yapılamaz, "yeni"den hiç çıkamaz) da dahildi. Artık sayfadaki
            // "Bekleyen" sekmesiyle birebir aynı kural: kurumu duran, sahipli
            // olmayan (ön kayıtlı/anlaşmalı) ve henüz işlem yapılmamış talepler.
            $actionableFacility = fn ($q) => $q->where(fn ($w) => $w->where('is_broker_managed', true)->orWhere('is_claimed', false));
            $view->with('newFeeInfoRequestsCount',
                FacilityInterestLead::whereNull('sent_to_facility_at')->whereHas('facility', $actionableFacility)->count()
                + OfferRequest::whereNotNull('facility_id')->whereNull('sent_to_facility_at')->whereHas('facility', $actionableFacility)->count()
            );

            $unreadByType = $admin
                ? $admin->notifications()->unread()->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type')
                : collect();
            $view->with('unreadByType', $unreadByType);
        });
    }

    /**
     * Public formlar icin hiz siniri tanimlari. Degerler koddan degil
     * config/platform.php > throttle icinden okunur; boylece trafiginize
     * gore limitleri kod deploy etmeden, tek dosyadan ayarlayabilirsiniz.
     */
    private function registerRateLimiters(): void
    {
        // 27 Ekim 2026: 3 marka artık tek sunucuda tek kopya olarak çalışıyor.
        // Eskiden her markanın ayrı kopyası (ve ayrı sayacı) vardı; sayaç bu yüzden
        // marka adresi + IP ile tutuluyor, böylece davranış birebir aynı kalıyor.
        foreach (config('platform.throttle', []) as $name => $perMinute) {
            RateLimiter::for($name, fn ($request) => Limit::perMinute($perMinute)->by($request->getHost().'|'.$request->ip()));
        }
    }

    /**
     * 28 Ekim 2026: kullanıcının talebi - aramalar büyük/küçük harften etkilenmemeli.
     * Veritabanı (utf8mb4_unicode_ci) çoğu harfi zaten eşit sayıyor ama Türkçe I/ı/İ/i'yi
     * karıştırıyordu ("Işık" 30 sonuç, "IŞIK" 3 sonuç). Hem sütundaki hem aranan metindeki
     * bu dört harf "i"ye çevrilip öyle karşılaştırılır.
     * Kullanım: ->whereTrLike('name', $aranan) / ->orWhereTrLike('name', $aranan)
     */
    private function registerTurkceArama(): void
    {
        $katla = fn (string $metin) => mb_strtolower(str_replace(['İ', 'I', 'ı'], 'i', $metin), 'UTF-8');
        QueryBuilder::macro('whereTrLike', function (string $sutun, $aranan, string $boolean = 'and') use ($katla) {
            /** @var QueryBuilder $this */
            $s = $this->getGrammar()->wrap($sutun);

            return $this->whereRaw("LOWER(REPLACE(REPLACE(REPLACE($s, 'İ', 'i'), 'I', 'i'), 'ı', 'i')) LIKE ?", ['%'.$katla(trim((string) $aranan)).'%'], $boolean);
        });
        QueryBuilder::macro('orWhereTrLike', function (string $sutun, $aranan) {
            /** @var QueryBuilder $this */
            return $this->whereTrLike($sutun, $aranan, 'or');
        });
    }
}
