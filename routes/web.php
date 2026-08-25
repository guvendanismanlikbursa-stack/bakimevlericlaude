<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\BalanceController as AdminBalanceController;
use App\Http\Controllers\Admin\CityController as AdminCityController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\WhatsappClickController as AdminWhatsappClickController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\ChatSettingsController as AdminChatSettingsController;
use App\Http\Controllers\Admin\ContentPageController as AdminContentPageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DataExtractorController as AdminDataExtractorController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\FacilityCategoryController as AdminFacilityCategoryController;
use App\Http\Controllers\Admin\FacilityClaimController as AdminFacilityClaimController;
use App\Http\Controllers\Admin\FacilityController as AdminFacilityController;
use App\Http\Controllers\Admin\FacilityInvitationController as AdminFacilityInvitationController;
use App\Http\Controllers\Admin\BrokerController as AdminBrokerController;
use App\Http\Controllers\Admin\OccupancyController as AdminOccupancyController;
use App\Http\Controllers\Admin\FacilityRegistrationController as AdminFacilityRegistrationController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FacilityQuestionController as AdminFacilityQuestionController;
use App\Http\Controllers\Admin\NearbySearchController as AdminNearbySearchController;
use App\Http\Controllers\Admin\OfferRequestController as AdminOfferRequestController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SiteStatsController as AdminSiteStatsController;
use App\Http\Controllers\Admin\SubscriptionPackageController as AdminSubscriptionPackageController;
use App\Http\Controllers\Admin\TrashController as AdminTrashController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WalletTopupController as AdminWalletTopupController;
use App\Http\Controllers\Facility\AuthController as FacilityAuthController;
use App\Http\Controllers\Facility\PasswordResetController as FacilityPasswordResetController;
use App\Http\Controllers\Facility\DashboardController as FacilityDashboardController;
use App\Http\Controllers\Facility\MessageController as FacilityMessageController;
use App\Http\Controllers\Facility\NotificationController as FacilityNotificationController;
use App\Http\Controllers\Facility\ProfileController as FacilityProfileController;
use App\Http\Controllers\Facility\QuoteController as FacilityQuoteController;
use App\Http\Controllers\Facility\SubscriptionController as FacilitySubscriptionController;
use App\Http\Controllers\Facility\WalletController as FacilityWalletController;
use App\Http\Controllers\Family\AuthController as FamilyAuthController;
use App\Http\Controllers\Family\GoogleAuthController as FamilyGoogleAuthController;
use App\Http\Controllers\Family\PasswordResetController as FamilyPasswordResetController;
use App\Http\Controllers\Family\DashboardController as FamilyDashboardController;
use App\Http\Controllers\Family\EmailVerificationController as FamilyEmailVerificationController;
use App\Http\Controllers\Family\MessageController as FamilyMessageController;
use App\Http\Controllers\Family\NotificationController as FamilyNotificationController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\EngagementController;
use App\Http\Controllers\Public\FacilityClaimController;
use App\Http\Controllers\Public\FacilityRegistrationController;
use App\Http\Controllers\Public\FacilityRegistrationGoogleAuthController;
use App\Http\Controllers\Public\CronRunnerController;
use App\Http\Controllers\Public\HealthController;
use App\Http\Controllers\Public\OpsController;
use App\Http\Controllers\Public\FacilityController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocationGuideController;
use App\Http\Controllers\Public\OfferRequestController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\GoogleChatAuthController;
use App\Http\Controllers\Public\SupportChatController;
use App\Http\Controllers\Admin\FacilityReviewController as AdminFacilityReviewController;
use App\Http\Controllers\Admin\VisitRequestController as AdminVisitRequestController;
use App\Http\Controllers\Public\FacilityReviewController;
use App\Http\Controllers\Public\VisitRequestController;
use App\Http\Controllers\Public\DiscoveryController;
use App\Http\Controllers\Public\CareAdvisorController;
use App\Http\Controllers\Public\FacilityQuestionController;
use App\Http\Controllers\Public\GuideController;
use App\Http\Controllers\Public\ManifestController;
use App\Http\Controllers\Public\NearbyController;
use App\Http\Controllers\Public\PushSubscriptionController;
use App\Http\Controllers\Public\WhatsappController;
use App\Http\Controllers\Public\PriceGuideController;
use App\Http\Controllers\Public\StatsController;
use App\Http\Controllers\Facility\QuestionController as FacilityQuestionPanelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ortak route grubu: hem gercek domain'lerde hem de localhost'ta
| /site/{brand} prefix'i ile ayni route isimleri (brand. on ekiyle) calisir.
| ResolveBrand middleware host'a veya route param'ina bakarak markayi cozer.
|--------------------------------------------------------------------------
*/
$siteRoutes = function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/karar-sihirbazi', [EngagementController::class, 'wizard'])->name('engagement.wizard');
    Route::get('/bakim-danismani', [CareAdvisorController::class, 'form'])->name('care-advisor.form');
    Route::get('/bakim-danismani/sonuclar', [CareAdvisorController::class, 'results'])->name('care-advisor.results');
    Route::get('/karsilastir', [EngagementController::class, 'compare'])->name('engagement.compare');
    Route::get('/favoriler', [EngagementController::class, 'favorites'])->middleware('family.auth')->name('engagement.favorites');
    // 24 Agustos 2026: Google Search Console'da bulundu - bolum belirtilmeden
    // /rehber'e giden (muhtemelen eski/dis bir baglanti) istekler 404
    // doruyordu, /rehber/{sectionSlug} zorunlu parametre bekliyor. Varsayilan
    // boluma yonlendirilir.
    Route::get('/rehber', function () {
        $brand = current_brand();
        $defaultSection = $brand['default_section'] ?? array_key_first(service_sections());

        return redirect(brand_route('location-guide.index', ['sectionSlug' => $defaultSection]));
    });
    Route::get('/rehber/{sectionSlug}', [LocationGuideController::class, 'index'])->name('location-guide.index');
    Route::get('/rehber/{sectionSlug}/{citySlug}/kategori/{categorySlug}/{districtSlug?}', [LocationGuideController::class, 'showCategory'])->name('location-guide.category');
    Route::get('/rehber/{sectionSlug}/{citySlug}/{districtSlug?}', [LocationGuideController::class, 'show'])->name('location-guide.show');
    Route::get('/kurumlar', [FacilityController::class, 'index'])->middleware('throttle:public-browse')->name('facilities.index');
    Route::get('/kurumlar-sayisi', [FacilityController::class, 'count'])->middleware('throttle:public-light')->name('facilities.count');
    Route::get('/kurumlar/{slug}', [FacilityController::class, 'show'])->middleware('throttle:public-browse')->name('facilities.show');
    Route::post('/kurumlar/{slug}/yorum', [FacilityReviewController::class, 'store'])->middleware('throttle:public-form')->name('reviews.store');
    Route::post('/kurumlar/{slug}/ziyaret-talebi', [VisitRequestController::class, 'store'])->middleware('throttle:public-form')->name('visit-requests.store');
    Route::post('/kurumlar/{slug}/kontenjan-sor', [VisitRequestController::class, 'storeAvailability'])->middleware('throttle:public-form')->name('visit-requests.availability');
    Route::post('/kurumlar/{slug}/favori-say', [EngagementController::class, 'toggleFavoriteCount'])->middleware('throttle:public-light')->name('facilities.favorite-count');
    Route::post('/kurumlar/{slug}/iletisim-tiklama', [EngagementController::class, 'trackContactClick'])->middleware('throttle:public-light')->name('facilities.contact-click');
    Route::post('/kurumlar/{slug}/gorsel/{image}/goruntulendi', [\App\Http\Controllers\Public\FacilityImageController::class, 'markViewed'])->middleware('throttle:public-light')->name('facilities.image.viewed');
    Route::post('/teklif-talebi', [OfferRequestController::class, 'store'])->middleware('throttle:public-form')->name('offer-requests.store');
    Route::get('/toplu-fiyat-al', [EngagementController::class, 'bulkQuote'])->name('engagement.bulk-quote');
    Route::post('/toplu-teklif-talebi', [OfferRequestController::class, 'storeBulk'])->middleware('throttle:public-form')->name('offer-requests.store-bulk');
    Route::get('/iletisim', [ContactController::class, 'create'])->name('contact.create');
    Route::post('/iletisim', [ContactController::class, 'store'])->middleware('throttle:public-form')->name('contact.store');
    // 12 Agustos 2026: kullanicinin talebi - "Hakkımızda" ozel, zengin
    // tasarimli bir sayfa olmali; genel {slug} joker route'undan ONCE
    // tanimlanmali ki ContentPage'deki eski duz metin/markdown kaydi
    // yerine bu ozel controller devreye girsin.
    Route::get('/sayfa/hakkimizda', [\App\Http\Controllers\Public\AboutController::class, 'show'])->name('pages.about');
    Route::get('/sayfa/{slug}', [PageController::class, 'show'])->name('pages.show');
    Route::get('/sss', [FaqController::class, 'index'])->name('faq.index');

    // Kesif / vitrin sayfalari
    Route::get('/dogrulanmis-kurumlar', [DiscoveryController::class, 'verified'])->name('discovery.verified');
    Route::get('/son-guncellenen-kurumlar', [DiscoveryController::class, 'recentlyUpdated'])->name('discovery.recent-updated');
    Route::get('/yeni-eklenen-kurumlar', [DiscoveryController::class, 'newlyAdded'])->name('discovery.new');
    Route::get('/son-sahiplenilen-kurumlar', [DiscoveryController::class, 'recentlyClaimed'])->name('discovery.recent-claimed');
    Route::get('/en-cok-goruntulenen-kurumlar', [DiscoveryController::class, 'mostViewed'])->name('discovery.most-viewed');
    Route::get('/en-cok-aranan-bolgeler', [DiscoveryController::class, 'mostSearched'])->name('discovery.most-searched');
    Route::get('/son-eklenen-fotograflar', [DiscoveryController::class, 'recentPhotos'])->name('discovery.recent-photos');

    // Ucret rehberi
    Route::get('/fiyat-rehberi', [PriceGuideController::class, 'index'])->name('price-guide.index');
    Route::get('/fiyat-rehberi/{sectionSlug}/{citySlug}/kategori/{categorySlug}/{districtSlug?}', [PriceGuideController::class, 'showCategory'])->name('price-guide.category');
    Route::get('/fiyat-rehberi/{sectionSlug}/{citySlug}/{districtSlug?}', [PriceGuideController::class, 'show'])->name('price-guide.show');

    // Turkiye istatistikleri
    Route::get('/istatistikler', [StatsController::class, 'index'])->name('stats.index');

    // Bakim rehberi (makaleler)
    Route::get('/bakim-rehberi', [GuideController::class, 'index'])->name('guides.index');

    // Yakinimdaki kurumlar (il bazli yaklasik konum eslesmesi)
    Route::post('/yakinimdaki-kurumlar', [NearbyController::class, 'locate'])->middleware('throttle:public-light')->name('nearby.locate');
    Route::post('/whatsapp-tiklama', [WhatsappController::class, 'track'])->middleware('throttle:public-light')->name('whatsapp.track');

    // Canli destek sohbeti (anonim misafir, polling tabanli - bkz. SupportChatController)
    Route::post('/destek/baslat', [SupportChatController::class, 'start'])->middleware('throttle:public-form')->name('support-chat.start');
    Route::post('/destek/{thread}/mesaj', [SupportChatController::class, 'send'])->middleware('throttle:public-form')->name('support-chat.send');
    Route::get('/destek/{thread}/mesajlar', [SupportChatController::class, 'poll'])->middleware('throttle:public-light')->name('support-chat.poll');
    Route::get('/destek/google-giris', [GoogleChatAuthController::class, 'redirect'])->middleware('throttle:public-light')->name('support-chat.google-redirect');
    Route::get('/destek/google-callback', [GoogleChatAuthController::class, 'callback'])->middleware('throttle:public-light')->name('support-chat.google-callback');

    // PWA manifest (marka bazli) + Web Push abonelikleri (session'a gore aile/kurum/admin cozumlenir)
    Route::get('/manifest.json', [ManifestController::class, 'show'])->name('manifest');
    Route::post('/push/abone-ol', [PushSubscriptionController::class, 'store'])->middleware('throttle:public-light')->name('push.subscribe');
    Route::post('/push/abonelikten-cik', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:public-light')->name('push.unsubscribe');

    // Aile sorulari
    Route::post('/kurumlar/{slug}/soru-sor', [FacilityQuestionController::class, 'store'])->middleware('throttle:public-sensitive')->name('questions.store');

    // Kurum sahiplenme basvurusu (herkese acik form, giris gerekmez)
    Route::get('/kurumlar/{slug}/sahiplen', [FacilityClaimController::class, 'create'])->name('facility-claim.create');
    Route::post('/kurumlar/{slug}/sahiplen', [FacilityClaimController::class, 'store'])->middleware('throttle:public-sensitive')->name('facility-claim.store');

    // Kurum kendi kendine kayit basvurusu (herkese acik form, giris gerekmez)
    Route::get('/kurum-kaydi', [FacilityRegistrationController::class, 'create'])->name('facility-registration.create');
    Route::post('/kurum-kaydi', [FacilityRegistrationController::class, 'store'])->middleware('throttle:public-sensitive')->name('facility-registration.store');
    Route::get('/kurum-kaydi/google-giris', [FacilityRegistrationGoogleAuthController::class, 'redirect'])->middleware('throttle:public-light')->name('facility-registration.google-redirect');
    Route::get('/kurum-kaydi/google-callback', [FacilityRegistrationGoogleAuthController::class, 'callback'])->middleware('throttle:public-light')->name('facility-registration.google-callback');
    Route::get('/kurum-kaydi/basvuru-alindi', [FacilityRegistrationController::class, 'received'])->name('facility-registration.received');
    Route::get('/kurum-kaydi/{registration}/duzenle/{hash}', [FacilityRegistrationController::class, 'edit'])
        ->middleware('signed')->name('facility-registration.edit');
    Route::post('/kurum-kaydi/{registration}/duzenle/{hash}', [FacilityRegistrationController::class, 'update'])
        ->middleware(['signed', 'throttle:public-sensitive'])->name('facility-registration.update');

    /*
    |--------------------------------------------------------------
    | Aile paneli
    |--------------------------------------------------------------
    */
    Route::prefix('aile')->name('family.')->group(function () {
        Route::get('/kayit', [FamilyAuthController::class, 'showRegister'])->name('register');
        Route::post('/kayit', [FamilyAuthController::class, 'register'])->middleware('throttle:auth-register')->name('register.attempt');
        Route::get('/giris', [FamilyAuthController::class, 'showLogin'])->name('login');
        Route::post('/giris', [FamilyAuthController::class, 'login'])->middleware('throttle:auth-attempt')->name('login.attempt');
        Route::post('/cikis', [FamilyAuthController::class, 'logout'])->name('logout');
        Route::get('/google-giris', [FamilyGoogleAuthController::class, 'redirect'])->middleware('throttle:public-light')->name('google-redirect');
        Route::get('/google-callback', [FamilyGoogleAuthController::class, 'callback'])->middleware('throttle:public-light')->name('google-callback');
        Route::get('/google-tamamla', [FamilyGoogleAuthController::class, 'completeForm'])->name('google-complete');
        Route::post('/google-tamamla', [FamilyGoogleAuthController::class, 'completeStore'])->middleware('throttle:auth-register')->name('google-complete.store');
        Route::get('/sifremi-unuttum', [FamilyPasswordResetController::class, 'showRequest'])->name('password.request');
        Route::post('/sifremi-unuttum', [FamilyPasswordResetController::class, 'sendResetLink'])->middleware('throttle:public-sensitive')->name('password.email');
        Route::get('/sifre-sifirla/{id}/{hash}', [FamilyPasswordResetController::class, 'showReset'])
            ->middleware('signed')->name('password.reset');
        Route::post('/sifre-sifirla/{id}/{hash}', [FamilyPasswordResetController::class, 'reset'])
            ->middleware(['signed', 'throttle:public-sensitive'])->name('password.reset.update');
        Route::get('/email-dogrula/{id}/{hash}', [FamilyEmailVerificationController::class, 'verify'])
            ->middleware('signed')->name('verify-email');
        Route::get('/email-dogrulama', [FamilyEmailVerificationController::class, 'notice'])
            ->name('verify-email.notice');
        Route::post('/email-dogrulama/tekrar-gonder', [FamilyEmailVerificationController::class, 'resend'])
            ->middleware('throttle:public-sensitive')->name('verify-email.resend');

        Route::middleware('family.auth')->group(function () {
            Route::get('/panel', [FamilyDashboardController::class, 'index'])->name('dashboard');
            Route::post('/teklif/{quote}/kabul-et', [FamilyDashboardController::class, 'acceptQuote'])->name('quotes.accept');
            Route::get('/talep/{offerRequest}/mesajlar', [FamilyMessageController::class, 'index'])->name('thread');
            Route::post('/talep/{offerRequest}/mesajlar', [FamilyMessageController::class, 'store'])->name('thread.store');
            Route::get('/talep/{offerRequest}/mesajlar/yeni', [FamilyMessageController::class, 'poll'])->middleware('throttle:public-light')->name('thread.poll');
            Route::get('/bildirimler', [FamilyNotificationController::class, 'index'])->name('notifications.index');
            Route::post('/bildirimler/{notification}/okundu', [FamilyNotificationController::class, 'markRead'])->name('notifications.read');
            Route::get('/profil', [\App\Http\Controllers\Family\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profil', [\App\Http\Controllers\Family\ProfileController::class, 'update'])->name('profile.update');
            Route::put('/profil/bildirim-tercihleri', [\App\Http\Controllers\Family\ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
            Route::delete('/profil', [\App\Http\Controllers\Family\ProfileController::class, 'destroy'])->name('profile.destroy');
            Route::get('/bildirimler/sayi', [FamilyNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
            Route::post('/kayitli-aramalar', [\App\Http\Controllers\Family\SavedSearchController::class, 'store'])->name('saved-searches.store');
            Route::delete('/kayitli-aramalar/{savedSearch}', [\App\Http\Controllers\Family\SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
        });
    });

    /*
    |--------------------------------------------------------------
    | Kurum (yetkili) paneli — hesap sadece admin onayiyla acilir
    |--------------------------------------------------------------
    */
    Route::prefix('kurum-panel')->name('facility.')->group(function () {
        Route::get('/giris', [FacilityAuthController::class, 'showLogin'])->name('login');
        Route::post('/giris', [FacilityAuthController::class, 'login'])->middleware('throttle:auth-attempt')->name('login.attempt');
        Route::post('/cikis', [FacilityAuthController::class, 'logout'])->name('logout');
        Route::get('/google-giris', [\App\Http\Controllers\Facility\GoogleAuthController::class, 'redirect'])->middleware('throttle:public-light')->name('google-redirect');
        Route::get('/google-callback', [\App\Http\Controllers\Facility\GoogleAuthController::class, 'callback'])->middleware('throttle:public-light')->name('google-callback');
        Route::get('/sifremi-unuttum', [FacilityPasswordResetController::class, 'showRequest'])->name('password.request');
        Route::post('/sifremi-unuttum', [FacilityPasswordResetController::class, 'sendResetLink'])->middleware('throttle:public-sensitive')->name('password.email');
        Route::get('/sifre-sifirla/{id}/{hash}', [FacilityPasswordResetController::class, 'showReset'])
            ->middleware('signed')->name('password.reset');
        Route::post('/sifre-sifirla/{id}/{hash}', [FacilityPasswordResetController::class, 'reset'])
            ->middleware(['signed', 'throttle:public-sensitive'])->name('password.reset.update');
        Route::get('/email-dogrula/{id}/{hash}', [\App\Http\Controllers\Facility\EmailVerificationController::class, 'verify'])
            ->middleware('signed')->name('verify-email');
        Route::get('/email-dogrulama', [\App\Http\Controllers\Facility\EmailVerificationController::class, 'notice'])
            ->name('verify-email.notice');
        Route::post('/email-dogrulama/tekrar-gonder', [\App\Http\Controllers\Facility\EmailVerificationController::class, 'resend'])
            ->middleware('throttle:public-sensitive')->name('verify-email.resend');
        Route::middleware('facility.auth')->group(function () {
            Route::get('/sifre-degistir', [FacilityAuthController::class, 'showChangePassword'])->name('password.change');
            Route::post('/sifre-degistir', [FacilityAuthController::class, 'changePassword'])->name('password.update');

            Route::get('/panel', [FacilityDashboardController::class, 'index'])->name('dashboard');
            Route::post('/talep/{offerRequest}/teklif-ver', [FacilityQuoteController::class, 'store'])->name('quotes.store');
            Route::get('/talep/{offerRequest}/mesajlar', [FacilityMessageController::class, 'index'])->name('thread');
            Route::post('/talep/{offerRequest}/mesajlar', [FacilityMessageController::class, 'store'])->name('thread.store');
            Route::get('/talep/{offerRequest}/mesajlar/yeni', [FacilityMessageController::class, 'poll'])->middleware('throttle:public-light')->name('thread.poll');

            Route::get('/profil', [FacilityProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profil', [FacilityProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profil', [FacilityProfileController::class, 'destroy'])->name('profile.destroy');
            // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi
            // uzerine yapilan spam/kotuye kullanim denetiminde bulundu -
            // anonim ucler (sahiplen, kurum-kaydi) zaten throttle:public-
            // sensitive tasiyordu, ama oturum-ici dosya yukleme ucleri
            // (profil gorseli, dekont) unutulmustu - ele gecirilmis/kotu
            // niyetli bir hesap disk alani + admin bildirim kuyrugunu
            // sinirsiz doldurabilirdi.
            Route::post('/profil/gorsel', [FacilityProfileController::class, 'uploadImage'])->middleware('throttle:public-sensitive')->name('profile.image.store');
            Route::delete('/profil/gorsel/{image}', [FacilityProfileController::class, 'deleteImage'])->name('profile.image.destroy');
            Route::post('/profil/gorsel/{image}/ana-gorsel-yap', [FacilityProfileController::class, 'setPrimaryImage'])->name('profile.image.set-primary');
            Route::post('/profil/yemek-listesi', [FacilityProfileController::class, 'uploadMenuImage'])->middleware('throttle:public-sensitive')->name('profile.menu-image.store');
            Route::delete('/profil/yemek-listesi', [FacilityProfileController::class, 'deleteMenuImage'])->name('profile.menu-image.destroy');
            Route::put('/profil/bildirim-tercihleri', [FacilityProfileController::class, 'updateNotifications'])->name('profile.notifications.update');

            Route::get('/bakiyem', [FacilityWalletController::class, 'index'])->name('wallet.index');
            Route::post('/bakiyem', [FacilityWalletController::class, 'store'])->middleware('throttle:public-sensitive')->name('wallet.store');

            Route::get('/bildirimler', [FacilityNotificationController::class, 'index'])->name('notifications.index');
            Route::post('/bildirimler/{notification}/okundu', [FacilityNotificationController::class, 'markRead'])->name('notifications.read');
            Route::get('/bildirimler/sayi', [FacilityNotificationController::class, 'unreadCount'])->name('notifications.unread-count');

            Route::get('/paketler', [FacilitySubscriptionController::class, 'index'])->name('packages.index');
            Route::post('/paketler/{package}', [FacilitySubscriptionController::class, 'store'])->middleware('throttle:public-sensitive')->name('packages.store');

            Route::get('/sorular', [FacilityQuestionPanelController::class, 'index'])->name('questions.index');
            Route::post('/sorular/{question}/cevapla', [FacilityQuestionPanelController::class, 'answer'])->name('questions.answer');

            Route::get('/yorumlar', [\App\Http\Controllers\Facility\ReviewController::class, 'index'])->name('reviews.index');
            Route::post('/yorumlar/{review}/cevapla', [\App\Http\Controllers\Facility\ReviewController::class, 'reply'])->name('reviews.reply');

            Route::get('/ekip', [\App\Http\Controllers\Facility\TeamController::class, 'index'])->name('team.index');
            Route::post('/ekip', [\App\Http\Controllers\Facility\TeamController::class, 'store'])->middleware('throttle:public-sensitive')->name('team.store');
            Route::delete('/ekip/{member}', [\App\Http\Controllers\Facility\TeamController::class, 'destroy'])->name('team.destroy');
        });
    });
};

// 1) Gercek domain modu (host eslesirse ResolveBrand brand'i ayarlar)
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
// cPanel cron'u dakikada bir bu URL'i curl ile tetikler (bkz. CronRunnerController).
Route::get('/_internal/cron-runner', [CronRunnerController::class, 'run'])->name('internal.cron-runner');
// Dis izleme (UptimeRobot vb.) ve deploy script'inin canliyi dogrulamasi icin - bkz. HealthController.
Route::get('/_saglik', [HealthController::class, 'check'])->name('health-check');
// Deploy script'inin migrate/cache-refresh tetiklemesi icin token korumali uc - bkz. OpsController.
Route::post('/_ops/{action}', [OpsController::class, 'run'])->middleware('throttle:public-sensitive')->name('ops.run');
// 19 Agustos 2026: kullanicinin talebi - 3 domain ayni veritabanini
// paylasiyor ama dosya deposu paylasilmiyordu (bir domain'de yuklenen
// gorsel digerlerinde kirik cikiyordu). Bu 2 uc, CrossDomainImageSync
// tarafindan DIGER domainlere ayni gorseli SENKRON olarak kopyalamak/
// silmek icin cagrilir - token korumali, /_ops/{action} ile ayni desen.
Route::post('/_internal/kurum-gorseli-sync', [\App\Http\Controllers\Internal\FacilityImageSyncController::class, 'store'])->name('internal.facility-image-sync.store');
Route::post('/_internal/kurum-gorseli-sil', [\App\Http\Controllers\Internal\FacilityImageSyncController::class, 'destroy'])->name('internal.facility-image-sync.destroy');
// Admin'in kurum/aile panelini "onlarin gozuyle" goruntulemesinden (impersonation)
// cikip kendi paneline donmesi icin - bkz. Admin\UserController::impersonateFacilityUser/
// -FamilyUser ve ImpersonationController. Bilerek facility.auth/family.auth
// disinda, tek/brand-siz bir uc - askiya alinmis/dogrulanmamis bir hesabi
// goruntulerken bile admin'in cikabilmesi gerekiyor.
Route::post('/impersonation/dur', [\App\Http\Controllers\Public\ImpersonationController::class, 'stop'])->name('impersonation.stop');

Route::middleware('track.visit')->group($siteRoutes);

// 2) Localhost test modu: /site/{brand}/... ayni route'lari "brand." on ekiyle uretir
// 24 Agustos 2026: kullanicinin bildirdigi gercek hata - bu route grubu
// UZUN SUREDIR production'da da (kosulsuz) acikti, footer'daki "Diger
// Sitelerimiz" linki (bkz. layouts/brand.blade.php) yanlislikla route('brand.home')
// kullanip buraya link veriyordu - Google bu prefix altinda TUM siteyi
// (3 markanin hepsini, birbirinin ustune binen sekilde) ikinci kez taramis,
// Search Console'da yuzlerce "kopya sayfa" ve capraz-marka sizintisi olarak
// gorunmustu. Footer linki gercek domaine duzeltildi (kok neden), burada da
// savunma amacli sadece local/testing'de acik birakildi - production'da
// bu prefix artik 404 doner, Google zamanla bu URL'leri dizin disi birakir.
if (app()->environment(['local', 'testing'])) {
    Route::prefix('site/{brand}')->name('brand.')->middleware('track.visit')->group($siteRoutes);
}

/*
|--------------------------------------------------------------------------
| Ortak admin panel (tek panel, 3 markayi da tam yetkiyle yonetir)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/manifest.json', [\App\Http\Controllers\Public\AdminManifestController::class, 'show'])->name('manifest');
    Route::get('/giris', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [AdminAuthController::class, 'login'])->middleware('throttle:auth-attempt')->name('login.attempt');
    Route::get('/giris/dogrula', [AdminAuthController::class, 'showVerify'])->name('login.verify');
    Route::post('/giris/dogrula', [AdminAuthController::class, 'verify'])->middleware('throttle:auth-attempt')->name('login.verify.attempt');
    Route::post('/giris/kod-yenile', [AdminAuthController::class, 'resendCode'])->middleware('throttle:public-sensitive')->name('login.verify.resend');
    Route::post('/cikis', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('kurumlar', AdminFacilityController::class)
            ->parameters(['kurumlar' => 'facility'])
            ->names('facilities')
            ->except(['show']);
        Route::delete('/kurumlar/gorsel/{image}', [AdminFacilityController::class, 'deleteImage'])->name('facilities.image.destroy');
        Route::post('/kurumlar/gorsel/{image}/ana-gorsel-yap', [AdminFacilityController::class, 'setPrimaryImage'])->name('facilities.image.set-primary');
        Route::post('/kurumlar/{facility}/bakiye-duzenle', [AdminBalanceController::class, 'adjust'])->name('facilities.balance.adjust');
        Route::post('/kurumlar/{facility}/onaya-kaldir', [AdminFacilityController::class, 'revertToPreRegistered'])->name('facilities.revert');
        Route::post('/kurumlar/{facility}/yerinde-sahiplendir', [AdminFacilityController::class, 'instantClaim'])->name('facilities.instant-claim');

        Route::get('/kurum-davetleri', [AdminFacilityInvitationController::class, 'index'])->name('invitations.index');
        Route::get('/kurum-davetleri/hizli-gonderim', [AdminFacilityInvitationController::class, 'quickSend'])->name('invitations.quick-send');
        Route::get('/kurum-davetleri/{facility}/whatsapp-ac', [AdminFacilityInvitationController::class, 'openWhatsapp'])->name('invitations.whatsapp');
        Route::post('/kurum-davetleri/{facility}/durum', [AdminFacilityInvitationController::class, 'updateStatus'])->name('invitations.update-status');

        Route::get('/aracilik/kurumlar', [AdminBrokerController::class, 'facilities'])->name('broker.facilities');
        Route::post('/aracilik/kurumlar/{facility}/degistir', [AdminBrokerController::class, 'toggleFacility'])->name('broker.facilities.toggle');
        Route::get('/aracilik/yonlendirmeler', [AdminBrokerController::class, 'referrals'])->name('broker.referrals');
        Route::post('/aracilik/yonlendirmeler', [AdminBrokerController::class, 'storeReferral'])->name('broker.referrals.store');
        Route::post('/aracilik/yonlendirmeler/{referral}', [AdminBrokerController::class, 'updateReferral'])->name('broker.referrals.update');

        Route::get('/doluluk-durumu', [AdminOccupancyController::class, 'index'])->name('occupancy.index');
        Route::post('/doluluk-durumu/{facility}', [AdminOccupancyController::class, 'update'])->name('occupancy.update');

        Route::get('/sahiplenme-basvurulari', [AdminFacilityClaimController::class, 'index'])->name('claims.index');
        Route::get('/sahiplenme-basvurulari/{claim}', [AdminFacilityClaimController::class, 'show'])->name('claims.show');
        Route::post('/sahiplenme-basvurulari/{claim}/onayla', [AdminFacilityClaimController::class, 'approve'])->name('claims.approve');
        Route::post('/sahiplenme-basvurulari/{claim}/reddet', [AdminFacilityClaimController::class, 'reject'])->name('claims.reject');
        Route::post('/sahiplenme-basvurulari/{claim}/belge-yukle', [AdminFacilityClaimController::class, 'uploadDocument'])->name('claims.upload-document');

        Route::get('/kurum-kayit-basvurulari', [AdminFacilityRegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/kurum-kayit-basvurulari/{registration}', [AdminFacilityRegistrationController::class, 'show'])->name('registrations.show');
        Route::post('/kurum-kayit-basvurulari/{registration}/onayla', [AdminFacilityRegistrationController::class, 'approve'])->name('registrations.approve');
        Route::post('/kurum-kayit-basvurulari/{registration}/revize-iste', [AdminFacilityRegistrationController::class, 'requestRevision'])->name('registrations.request-revision');
        Route::post('/kurum-kayit-basvurulari/{registration}/reddet', [AdminFacilityRegistrationController::class, 'reject'])->name('registrations.reject');
        Route::delete('/kurum-kayit-basvurulari/{registration}', [AdminFacilityRegistrationController::class, 'destroy'])->name('registrations.destroy');

        Route::get('/ayarlar', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/ayarlar', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::get('/bakiye-yuklemeleri', [AdminWalletTopupController::class, 'index'])->name('topups.index');
        Route::post('/bakiye-yuklemeleri/{topup}/onayla', [AdminWalletTopupController::class, 'approve'])->name('topups.approve');
        Route::post('/bakiye-yuklemeleri/{topup}/reddet', [AdminWalletTopupController::class, 'reject'])->name('topups.reject');

        Route::get('/veri-cekici', [AdminDataExtractorController::class, 'index'])->name('data-extractor.index');
        Route::post('/veri-cekici/import', [AdminDataExtractorController::class, 'import'])->name('data-extractor.import');
        Route::post('/veri-cekici/calistir', [AdminDataExtractorController::class, 'run'])->name('data-extractor.run');
        Route::get('/veri-cekici/satir/{row}', [AdminDataExtractorController::class, 'showRow'])->name('data-extractor.rows.show');
        Route::put('/veri-cekici/satir/{row}', [AdminDataExtractorController::class, 'updateRow'])->name('data-extractor.rows.update');
        Route::post('/veri-cekici/satir/{row}/otomatik-doldur', [AdminDataExtractorController::class, 'autofill'])->name('data-extractor.rows.autofill');
        Route::post('/veri-cekici/satir/{row}/onayla', [AdminDataExtractorController::class, 'approve'])->name('data-extractor.rows.approve');
        Route::delete('/veri-cekici/satir/{row}', [AdminDataExtractorController::class, 'destroyRow'])->name('data-extractor.rows.destroy');

        Route::get('/teklif-talepleri', [AdminOfferRequestController::class, 'index'])->name('offer-requests.index');
        Route::put('/teklif-talepleri/{offerRequest}', [AdminOfferRequestController::class, 'update'])->name('offer-requests.update');
        Route::get('/teklif-talepleri/{offerRequest}/mesajlar', [AdminOfferRequestController::class, 'showMessages'])->name('offer-requests.messages');
        Route::post('/teklif-talepleri/{offerRequest}/aile-durumu', [AdminOfferRequestController::class, 'suspendFamily'])->name('offer-requests.suspend-family');
        Route::post('/teklif-talepleri/{offerRequest}/kurum-durumu', [AdminOfferRequestController::class, 'suspendFacility'])->name('offer-requests.suspend-facility');

        Route::get('/yorumlar', [AdminFacilityReviewController::class, 'index'])->name('reviews.index');
        Route::put('/yorumlar/{review}', [AdminFacilityReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/yorumlar/{review}', [AdminFacilityReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::get('/ziyaret-talepleri', [AdminVisitRequestController::class, 'index'])->name('visit-requests.index');
        Route::put('/ziyaret-talepleri/{visitRequest}', [AdminVisitRequestController::class, 'update'])->name('visit-requests.update');
        Route::delete('/ziyaret-talepleri/{visitRequest}', [AdminVisitRequestController::class, 'destroy'])->name('visit-requests.destroy');

        Route::get('/mesajlar', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::patch('/mesajlar/{contactMessage}/okundu', [AdminContactMessageController::class, 'markRead'])->name('contact-messages.read');
        Route::post('/mesajlar/{contactMessage}/cevapla', [AdminContactMessageController::class, 'reply'])->name('contact-messages.reply');
        Route::delete('/mesajlar/{contactMessage}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

        Route::get('/whatsapp-tiklamalari', [AdminWhatsappClickController::class, 'index'])->name('whatsapp-clicks.index');
        Route::delete('/whatsapp-tiklamalari/{whatsappClick}', [AdminWhatsappClickController::class, 'destroy'])->name('whatsapp-clicks.destroy');

        Route::get('/canli-sohbet', [AdminChatController::class, 'index'])->name('chat.index');
        Route::get('/canli-sohbet-istatistik', [AdminChatController::class, 'stats'])->name('chat.stats');
        Route::get('/canli-sohbet/{thread}', [AdminChatController::class, 'show'])->name('chat.show');
        Route::post('/canli-sohbet/{thread}/yanitla', [AdminChatController::class, 'reply'])->name('chat.reply');
        Route::get('/canli-sohbet/{thread}/mesajlar', [AdminChatController::class, 'poll'])->name('chat.poll');
        Route::post('/canli-sohbet/{thread}/kapat', [AdminChatController::class, 'close'])->name('chat.close');
        Route::get('/canli-sohbet-ayarlari', [AdminChatSettingsController::class, 'edit'])->name('chat-settings.edit');
        Route::put('/canli-sohbet-ayarlari', [AdminChatSettingsController::class, 'update'])->name('chat-settings.update');

        Route::get('/sehirler', [AdminCityController::class, 'index'])->name('cities.index');
        Route::post('/sehirler', [AdminCityController::class, 'store'])->name('cities.store');
        Route::delete('/sehirler/{city}', [AdminCityController::class, 'destroy'])->name('cities.destroy');

        Route::get('/belge/{type}/{id}', [AdminDocumentController::class, 'show'])->where(['type' => 'claim|topup', 'id' => '[0-9]+'])->name('documents.show');

        Route::get('/kullanicilar/aileler', [AdminUserController::class, 'families'])->name('users.families');
        Route::post('/kullanicilar/aileler/{familyUser}/durum', [AdminUserController::class, 'toggleFamilyStatus'])->name('users.families.toggle-status');
        Route::post('/kullanicilar/aileler/{familyUser}/giris-yap', [AdminUserController::class, 'impersonateFamilyUser'])->name('users.families.impersonate');
        Route::get('/kullanicilar/kurum-yetkilileri', [AdminUserController::class, 'facilityUsers'])->name('users.facility-users');
        Route::post('/kullanicilar/kurum-yetkilileri/{facilityUser}/durum', [AdminUserController::class, 'toggleFacilityUserStatus'])->name('users.facility-users.toggle-status');
        Route::post('/kullanicilar/kurum-yetkilileri/{facilityUser}/sifre-sifirla', [AdminUserController::class, 'resetFacilityUserPassword'])->name('users.facility-users.reset-password');
        Route::post('/kullanicilar/kurum-yetkilileri/{facilityUser}/giris-yap', [AdminUserController::class, 'impersonateFacilityUser'])->name('users.facility-users.impersonate');
        Route::delete('/kullanicilar/kurum-yetkilileri/{facilityUser}', [AdminUserController::class, 'destroyFacilityUser'])->name('users.facility-users.destroy');

        Route::get('/kategoriler', [AdminFacilityCategoryController::class, 'index'])->name('categories.index');
        Route::post('/kategoriler', [AdminFacilityCategoryController::class, 'store'])->name('categories.store');
        Route::put('/kategoriler/{category}/segment-esikleri', [AdminFacilityCategoryController::class, 'updatePriceTiers'])->name('categories.price-tiers.update');
        Route::delete('/kategoriler/{category}', [AdminFacilityCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/sayfalar', [AdminContentPageController::class, 'index'])->name('content-pages.index');
        Route::post('/sayfalar', [AdminContentPageController::class, 'store'])->name('content-pages.store');
        Route::put('/sayfalar/{contentPage}', [AdminContentPageController::class, 'update'])->name('content-pages.update');
        Route::delete('/sayfalar/{contentPage}', [AdminContentPageController::class, 'destroy'])->name('content-pages.destroy');

        Route::get('/sss', [AdminFaqController::class, 'index'])->name('faqs.index');
        Route::post('/sss', [AdminFaqController::class, 'store'])->name('faqs.store');
        Route::put('/sss/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
        Route::delete('/sss/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');

        Route::get('/paketler', [AdminSubscriptionPackageController::class, 'index'])->name('packages.index');
        Route::post('/paketler', [AdminSubscriptionPackageController::class, 'store'])->name('packages.store');
        Route::put('/paketler/{package}', [AdminSubscriptionPackageController::class, 'update'])->name('packages.update');
        Route::delete('/paketler/{package}', [AdminSubscriptionPackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/cop-kutusu', [AdminTrashController::class, 'index'])->name('trash.index');
        Route::post('/cop-kutusu/{type}/{id}/geri-yukle', [AdminTrashController::class, 'restore'])->name('trash.restore');
        Route::delete('/cop-kutusu/{type}/{id}/kalici-sil', [AdminTrashController::class, 'forceDestroy'])->name('trash.force-destroy');

        Route::get('/islem-gunlugu', [AdminAuditLogController::class, 'index'])->name('audit-log.index');

        Route::get('/hesap-silme-talepleri', [\App\Http\Controllers\Admin\AccountDeletionController::class, 'index'])->name('account-deletions.index');
        Route::post('/hesap-silme-talepleri/{accountDeletionRequest}/onayla', [\App\Http\Controllers\Admin\AccountDeletionController::class, 'approve'])->name('account-deletions.approve');
        Route::post('/hesap-silme-talepleri/{accountDeletionRequest}/reddet', [\App\Http\Controllers\Admin\AccountDeletionController::class, 'reject'])->name('account-deletions.reject');

        Route::get('/hatalar', [\App\Http\Controllers\Admin\PlatformErrorController::class, 'index'])->name('platform-errors.index');
        Route::post('/hatalar/{platformError}/coz', [\App\Http\Controllers\Admin\PlatformErrorController::class, 'resolve'])->name('platform-errors.resolve');
        Route::delete('/hatalar/{platformError}', [\App\Http\Controllers\Admin\PlatformErrorController::class, 'destroy'])->name('platform-errors.destroy');

        Route::get('/veri-denetimi', [\App\Http\Controllers\Admin\DataQualityController::class, 'index'])->name('data-quality.index');
        Route::post('/veri-denetimi/kategori-duzelt', [\App\Http\Controllers\Admin\DataQualityController::class, 'fixMiscategory'])->name('data-quality.fix-miscategory');
        Route::post('/veri-denetimi/telefon-duzelt', [\App\Http\Controllers\Admin\DataQualityController::class, 'fixPhoneType'])->name('data-quality.fix-phone-type');
        Route::post('/veri-denetimi/ilce-duzelt', [\App\Http\Controllers\Admin\DataQualityController::class, 'fixDistrict'])->name('data-quality.fix-district');
        Route::post('/veri-denetimi/isim-duzelt', [\App\Http\Controllers\Admin\DataQualityController::class, 'fixNameCleanup'])->name('data-quality.fix-name-cleanup');
        Route::post('/veri-denetimi/sahiplik-duzelt', [\App\Http\Controllers\Admin\DataQualityController::class, 'fixOwnership'])->name('data-quality.fix-ownership');

        Route::get('/aile-sorulari', [AdminFacilityQuestionController::class, 'index'])->name('questions.index');
        Route::delete('/aile-sorulari/{question}', [AdminFacilityQuestionController::class, 'destroy'])->name('questions.destroy');

        Route::get('/site-istatistikleri', [AdminSiteStatsController::class, 'index'])->name('site-stats.index');
        Route::get('/aileler/{familyUser}', [AdminSiteStatsController::class, 'showFamily'])->name('family-users.show');
        Route::get('/yakin-arama-kayitlari', [AdminNearbySearchController::class, 'index'])->name('nearby-searches.index');

        // 17 Agustos 2026: kullanicinin talebi - bkz. ScheduledJobMonitor yorumu.
        Route::get('/zamanlanan-gorevler', [\App\Http\Controllers\Admin\ScheduledJobController::class, 'index'])->name('scheduled-jobs.index');
    });
});