<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\BalanceController as AdminBalanceController;
use App\Http\Controllers\Admin\CityController as AdminCityController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\WhatsappClickController as AdminWhatsappClickController;
use App\Http\Controllers\Admin\ContentPageController as AdminContentPageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DataExtractorController as AdminDataExtractorController;
use App\Http\Controllers\Admin\FacilityCategoryController as AdminFacilityCategoryController;
use App\Http\Controllers\Admin\FacilityClaimController as AdminFacilityClaimController;
use App\Http\Controllers\Admin\FacilityController as AdminFacilityController;
use App\Http\Controllers\Admin\FacilityInvitationController as AdminFacilityInvitationController;
use App\Http\Controllers\Admin\FacilityRegistrationController as AdminFacilityRegistrationController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FacilityQuestionController as AdminFacilityQuestionController;
use App\Http\Controllers\Admin\OfferRequestController as AdminOfferRequestController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SiteStatsController as AdminSiteStatsController;
use App\Http\Controllers\Admin\SubscriptionPackageController as AdminSubscriptionPackageController;
use App\Http\Controllers\Admin\TrashController as AdminTrashController;
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
use App\Http\Controllers\Family\PasswordResetController as FamilyPasswordResetController;
use App\Http\Controllers\Family\DashboardController as FamilyDashboardController;
use App\Http\Controllers\Family\EmailVerificationController as FamilyEmailVerificationController;
use App\Http\Controllers\Family\MessageController as FamilyMessageController;
use App\Http\Controllers\Family\NotificationController as FamilyNotificationController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\EngagementController;
use App\Http\Controllers\Public\FacilityClaimController;
use App\Http\Controllers\Public\FacilityRegistrationController;
use App\Http\Controllers\Public\FacilityController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocationGuideController;
use App\Http\Controllers\Public\OfferRequestController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Admin\FacilityReviewController as AdminFacilityReviewController;
use App\Http\Controllers\Admin\VisitRequestController as AdminVisitRequestController;
use App\Http\Controllers\Public\FacilityReviewController;
use App\Http\Controllers\Public\VisitRequestController;
use App\Http\Controllers\Public\DiscoveryController;
use App\Http\Controllers\Public\CareAdvisorController;
use App\Http\Controllers\Public\FacilityQuestionController;
use App\Http\Controllers\Public\GuideController;
use App\Http\Controllers\Public\NearbyController;
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
    Route::get('/rehber/{sectionSlug}/{citySlug}/kategori/{categorySlug}/{districtSlug?}', [LocationGuideController::class, 'showCategory'])->name('location-guide.category');
    Route::get('/rehber/{sectionSlug}/{citySlug}/{districtSlug?}', [LocationGuideController::class, 'show'])->name('location-guide.show');
    Route::get('/kurumlar', [FacilityController::class, 'index'])->name('facilities.index');
    Route::get('/kurumlar-sayisi', [FacilityController::class, 'count'])->middleware('throttle:public-light')->name('facilities.count');
    Route::get('/kurumlar/{slug}', [FacilityController::class, 'show'])->name('facilities.show');
    Route::post('/kurumlar/{slug}/yorum', [FacilityReviewController::class, 'store'])->middleware('throttle:public-form')->name('reviews.store');
    Route::post('/kurumlar/{slug}/ziyaret-talebi', [VisitRequestController::class, 'store'])->middleware('throttle:public-form')->name('visit-requests.store');
    Route::post('/kurumlar/{slug}/kontenjan-sor', [VisitRequestController::class, 'storeAvailability'])->middleware('throttle:public-form')->name('visit-requests.availability');
    Route::post('/kurumlar/{slug}/favori-say', [EngagementController::class, 'toggleFavoriteCount'])->middleware('throttle:public-light')->name('facilities.favorite-count');
    Route::post('/teklif-talebi', [OfferRequestController::class, 'store'])->middleware('throttle:public-form')->name('offer-requests.store');
    Route::get('/toplu-fiyat-al', [EngagementController::class, 'bulkQuote'])->name('engagement.bulk-quote');
    Route::post('/toplu-teklif-talebi', [OfferRequestController::class, 'storeBulk'])->middleware('throttle:public-form')->name('offer-requests.store-bulk');
    Route::get('/iletisim', [ContactController::class, 'create'])->name('contact.create');
    Route::post('/iletisim', [ContactController::class, 'store'])->middleware('throttle:public-form')->name('contact.store');
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
    Route::get('/fiyat-rehberi/{sectionSlug}/{citySlug}/kategori/{categorySlug}', [PriceGuideController::class, 'showCategory'])->name('price-guide.category');
    Route::get('/fiyat-rehberi/{sectionSlug}/{citySlug}/{districtSlug?}', [PriceGuideController::class, 'show'])->name('price-guide.show');

    // Turkiye istatistikleri
    Route::get('/istatistikler', [StatsController::class, 'index'])->name('stats.index');

    // Bakim rehberi (makaleler)
    Route::get('/bakim-rehberi', [GuideController::class, 'index'])->name('guides.index');

    // Yakinimdaki kurumlar (il bazli yaklasik konum eslesmesi)
    Route::post('/yakinimdaki-kurumlar', [NearbyController::class, 'locate'])->middleware('throttle:public-light')->name('nearby.locate');
    Route::post('/whatsapp-tiklama', [WhatsappController::class, 'track'])->middleware('throttle:public-light')->name('whatsapp.track');

    // Aile sorulari
    Route::post('/kurumlar/{slug}/soru-sor', [FacilityQuestionController::class, 'store'])->middleware('throttle:public-sensitive')->name('questions.store');

    // Kurum sahiplenme basvurusu (herkese acik form, giris gerekmez)
    Route::get('/kurumlar/{slug}/sahiplen', [FacilityClaimController::class, 'create'])->name('facility-claim.create');
    Route::post('/kurumlar/{slug}/sahiplen', [FacilityClaimController::class, 'store'])->middleware('throttle:public-sensitive')->name('facility-claim.store');

    // Kurum kendi kendine kayit basvurusu (herkese acik form, giris gerekmez)
    Route::get('/kurum-kaydi', [FacilityRegistrationController::class, 'create'])->name('facility-registration.create');
    Route::post('/kurum-kaydi', [FacilityRegistrationController::class, 'store'])->middleware('throttle:public-sensitive')->name('facility-registration.store');
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
            Route::get('/bildirimler', [FamilyNotificationController::class, 'index'])->name('notifications.index');
            Route::post('/bildirimler/{notification}/okundu', [FamilyNotificationController::class, 'markRead'])->name('notifications.read');
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

            Route::get('/profil', [FacilityProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profil', [FacilityProfileController::class, 'update'])->name('profile.update');
            Route::post('/profil/gorsel', [FacilityProfileController::class, 'uploadImage'])->name('profile.image.store');
            Route::delete('/profil/gorsel/{image}', [FacilityProfileController::class, 'deleteImage'])->name('profile.image.destroy');

            Route::get('/bakiyem', [FacilityWalletController::class, 'index'])->name('wallet.index');
            Route::post('/bakiyem', [FacilityWalletController::class, 'store'])->name('wallet.store');

            Route::get('/bildirimler', [FacilityNotificationController::class, 'index'])->name('notifications.index');
            Route::post('/bildirimler/{notification}/okundu', [FacilityNotificationController::class, 'markRead'])->name('notifications.read');

            Route::get('/paketler', [FacilitySubscriptionController::class, 'index'])->name('packages.index');
            Route::post('/paketler/{package}', [FacilitySubscriptionController::class, 'store'])->name('packages.store');

            Route::get('/sorular', [FacilityQuestionPanelController::class, 'index'])->name('questions.index');
            Route::post('/sorular/{question}/cevapla', [FacilityQuestionPanelController::class, 'answer'])->name('questions.answer');
        });
    });
};

// 1) Gercek domain modu (host eslesirse ResolveBrand brand'i ayarlar)
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::middleware('track.visit')->group($siteRoutes);

// 2) Localhost test modu: /site/{brand}/... ayni route'lari "brand." on ekiyle uretir
Route::prefix('site/{brand}')->name('brand.')->middleware('track.visit')->group($siteRoutes);

/*
|--------------------------------------------------------------------------
| Ortak admin panel (tek panel, 3 markayi da tam yetkiyle yonetir)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/giris', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [AdminAuthController::class, 'login'])->middleware('throttle:auth-attempt')->name('login.attempt');
    Route::post('/cikis', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('kurumlar', AdminFacilityController::class)
            ->parameters(['kurumlar' => 'facility'])
            ->names('facilities')
            ->except(['show']);
        Route::delete('/kurumlar/gorsel/{image}', [AdminFacilityController::class, 'deleteImage'])->name('facilities.image.destroy');
        Route::post('/kurumlar/{facility}/bakiye-duzenle', [AdminBalanceController::class, 'adjust'])->name('facilities.balance.adjust');
        Route::post('/kurumlar/{facility}/onaya-kaldir', [AdminFacilityController::class, 'revertToPreRegistered'])->name('facilities.revert');

        Route::get('/kurum-davetleri', [AdminFacilityInvitationController::class, 'index'])->name('invitations.index');
        Route::get('/kurum-davetleri/{facility}/whatsapp-ac', [AdminFacilityInvitationController::class, 'openWhatsapp'])->name('invitations.whatsapp');
        Route::post('/kurum-davetleri/{facility}/durum', [AdminFacilityInvitationController::class, 'updateStatus'])->name('invitations.update-status');

        Route::get('/sahiplenme-basvurulari', [AdminFacilityClaimController::class, 'index'])->name('claims.index');
        Route::get('/sahiplenme-basvurulari/{claim}', [AdminFacilityClaimController::class, 'show'])->name('claims.show');
        Route::post('/sahiplenme-basvurulari/{claim}/onayla', [AdminFacilityClaimController::class, 'approve'])->name('claims.approve');
        Route::post('/sahiplenme-basvurulari/{claim}/reddet', [AdminFacilityClaimController::class, 'reject'])->name('claims.reject');

        Route::get('/kurum-kayit-basvurulari', [AdminFacilityRegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/kurum-kayit-basvurulari/{registration}', [AdminFacilityRegistrationController::class, 'show'])->name('registrations.show');
        Route::post('/kurum-kayit-basvurulari/{registration}/onayla', [AdminFacilityRegistrationController::class, 'approve'])->name('registrations.approve');
        Route::post('/kurum-kayit-basvurulari/{registration}/revize-iste', [AdminFacilityRegistrationController::class, 'requestRevision'])->name('registrations.request-revision');
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
        Route::delete('/mesajlar/{contactMessage}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

        Route::get('/whatsapp-tiklamalari', [AdminWhatsappClickController::class, 'index'])->name('whatsapp-clicks.index');
        Route::delete('/whatsapp-tiklamalari/{whatsappClick}', [AdminWhatsappClickController::class, 'destroy'])->name('whatsapp-clicks.destroy');

        Route::get('/sehirler', [AdminCityController::class, 'index'])->name('cities.index');
        Route::post('/sehirler', [AdminCityController::class, 'store'])->name('cities.store');
        Route::delete('/sehirler/{city}', [AdminCityController::class, 'destroy'])->name('cities.destroy');

        Route::get('/kategoriler', [AdminFacilityCategoryController::class, 'index'])->name('categories.index');
        Route::post('/kategoriler', [AdminFacilityCategoryController::class, 'store'])->name('categories.store');
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

        Route::get('/aile-sorulari', [AdminFacilityQuestionController::class, 'index'])->name('questions.index');
        Route::delete('/aile-sorulari/{question}', [AdminFacilityQuestionController::class, 'destroy'])->name('questions.destroy');

        Route::get('/site-istatistikleri', [AdminSiteStatsController::class, 'index'])->name('site-stats.index');
    });
});