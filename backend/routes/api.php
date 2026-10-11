<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\PublicApi;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Public endpoints expose only published/active content.
| Admin endpoints require a Sanctum session (cookie) + an active user + policies.
*/

// One-time installer / migrator for hosts without SSH (disabled unless INSTALL_TOKEN is set).
Route::post('install', InstallController::class)->middleware('throttle:5,1')->name('install');

Route::prefix('v1')->name('v1.')->group(function () {

    // ---------------------------------------------------------------- Public
    Route::middleware(['throttle:api', \App\Http\Middleware\SetLocale::class])->group(function () {
        Route::get('settings', [PublicApi\SiteController::class, 'settings'])->name('settings');
        Route::get('navigation', [PublicApi\SiteController::class, 'navigation'])->name('navigation');
        Route::get('search', [PublicApi\SiteController::class, 'search'])->name('search');
        Route::get('sitemap.xml', [PublicApi\SiteController::class, 'sitemap'])->name('sitemap');
        Route::get('meta', PublicApi\MetaController::class)->name('meta'); // honours ?lang via SetLocale

        Route::get('home', [PublicApi\PageController::class, 'home'])->name('home');
        Route::get('pages/{slug}', [PublicApi\PageController::class, 'show'])->name('pages.show');

        Route::get('product-categories', [PublicApi\ProductController::class, 'categories'])->name('product-categories.index');
        Route::get('products', [PublicApi\ProductController::class, 'index'])->name('products.index');
        Route::get('products/compare', [PublicApi\ProductController::class, 'compare'])->name('products.compare');
        Route::get('industries', [PublicApi\ProductController::class, 'industries'])->name('industries.index');
        Route::get('products/{slug}', [PublicApi\ProductController::class, 'show'])->name('products.show');

        Route::get('services', [PublicApi\ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{slug}', [PublicApi\ServiceController::class, 'show'])->name('services.show');

        Route::get('news-categories', [PublicApi\NewsController::class, 'categories'])->name('news-categories.index');
        Route::get('news/recent', [PublicApi\NewsController::class, 'recent'])->name('news.recent');
        Route::get('news', [PublicApi\NewsController::class, 'index'])->name('news.index');
        Route::get('news/{slug}', [PublicApi\NewsController::class, 'show'])->name('news.show');

        Route::get('contact-info', [PublicApi\ContactController::class, 'info'])->name('contact.info');

        Route::get('dealers', [PublicApi\DealerController::class, 'index'])->name('dealers.index');
        Route::get('jobs', [PublicApi\CareerController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{slug}', [PublicApi\CareerController::class, 'show'])->name('jobs.show');
        Route::get('gallery', [PublicApi\GalleryController::class, 'index'])->name('gallery.index');
        Route::get('gallery/{slug}', [PublicApi\GalleryController::class, 'show'])->name('gallery.show');
    });

    Route::middleware('throttle:forms')->group(function () {
        Route::post('contact', [PublicApi\ContactController::class, 'store'])->name('contact.store');
        Route::post('quote', [PublicApi\QuoteController::class, 'store'])->name('quote.store');
        Route::post('quote/track', [PublicApi\QuoteController::class, 'track'])->name('quote.track');
        Route::post('jobs/{slug}/apply', [PublicApi\CareerController::class, 'apply'])->name('jobs.apply');
        Route::post('newsletter/subscribe', [PublicApi\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
        Route::post('newsletter/unsubscribe', [PublicApi\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    });

    // ----------------------------------------------------------------- Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:login')->name('login');
        Route::post('two-factor-challenge', [Admin\AuthController::class, 'twoFactorChallenge'])->middleware('throttle:login')->name('two-factor.challenge');
        Route::post('forgot-password', [Admin\AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset')->name('password.email');
        // Separate, looser limit than the email step so a mistyped new password does not lock the user out.
        Route::post('reset-password', [Admin\AuthController::class, 'resetPassword'])->middleware('throttle:login')->name('password.update');

        Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
            Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
            Route::get('me', [Admin\AuthController::class, 'me'])->name('me');
            Route::put('profile', [Admin\AuthController::class, 'updateProfile'])->name('profile');
            Route::post('two-factor', [Admin\TwoFactorController::class, 'enable'])->name('two-factor.enable');
            Route::post('two-factor/confirm', [Admin\TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
            Route::post('two-factor/recovery-codes', [Admin\TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery');
            Route::delete('two-factor', [Admin\TwoFactorController::class, 'disable'])->name('two-factor.disable');
            Route::delete('users/{id}/two-factor', [Admin\TwoFactorController::class, 'reset'])->whereNumber('id')->name('two-factor.reset');
            Route::get('activity-log', Admin\ActivityLogController::class)->name('activity-log');

            Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');
            Route::get('meta', Admin\MetaController::class)->name('meta');
            Route::post('sitemap', [Admin\MetaController::class, 'regenerateSitemap'])->name('sitemap');

            Route::get('settings', [Admin\SettingsController::class, 'show'])->name('settings.show');
            Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

            Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
            Route::post('media', [Admin\MediaController::class, 'store'])->middleware('throttle:uploads')->name('media.store');
            Route::put('media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
            Route::delete('media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');

            // Page builder (sections are nested under pages; shallow for update/delete).
            Route::get('pages/{page}/sections', [Admin\SectionController::class, 'index'])->name('sections.index');
            Route::post('pages/{page}/sections', [Admin\SectionController::class, 'store'])->name('sections.store');
            Route::post('pages/{page}/sections/reorder', [Admin\SectionController::class, 'reorder'])->name('sections.reorder');
            Route::get('sections/{section}', [Admin\SectionController::class, 'show'])->name('sections.show');
            Route::put('sections/{section}', [Admin\SectionController::class, 'update'])->name('sections.update');
            Route::delete('sections/{section}', [Admin\SectionController::class, 'destroy'])->name('sections.destroy');

            Route::get('enquiries/export', [Admin\EnquiryController::class, 'export'])->name('enquiries.export');
            Route::get('quote-requests/export', [Admin\QuoteRequestController::class, 'export'])->name('quote-requests.export');
            Route::get('job-applications/{id}/cv', [Admin\JobApplicationController::class, 'cv'])->whereNumber('id')->name('job-applications.cv');
            Route::get('subscribers/export', [Admin\SubscriberController::class, 'export'])->name('subscribers.export');
            Route::get('newsletter-campaigns/recipients', [Admin\NewsletterCampaignController::class, 'recipients'])->name('newsletter.recipients');
            Route::get('newsletter-campaigns/{id}/preview', [Admin\NewsletterCampaignController::class, 'preview'])->whereNumber('id')->name('newsletter.preview');
            Route::post('newsletter-campaigns/{id}/test', [Admin\NewsletterCampaignController::class, 'test'])->whereNumber('id')->middleware('throttle:forms')->name('newsletter.test');
            Route::post('newsletter-campaigns/{id}/send-batch', [Admin\NewsletterCampaignController::class, 'sendBatch'])->whereNumber('id')->name('newsletter.send-batch');

            $crud = [
                'pages' => Admin\PageController::class,
                'hero-slides' => Admin\HeroSlideController::class,
                'menu-items' => Admin\MenuItemController::class,
                'product-categories' => Admin\ProductCategoryController::class,
                'products' => Admin\ProductController::class,
                'industries' => Admin\IndustryController::class,
                'services' => Admin\ServiceController::class,
                'clients' => Admin\ClientController::class,
                'testimonials' => Admin\TestimonialController::class,
                'gallery-albums' => Admin\GalleryAlbumController::class,
                'team-members' => Admin\TeamMemberController::class,
                'news-categories' => Admin\NewsCategoryController::class,
                'news-posts' => Admin\NewsPostController::class,
                'milestones' => Admin\MilestoneController::class,
                'certifications' => Admin\CertificationController::class,
                'offices' => Admin\OfficeController::class,
                'faqs' => Admin\FaqController::class,
                'dealers' => Admin\DealerController::class,
                'enquiries' => Admin\EnquiryController::class,
                'quote-requests' => Admin\QuoteRequestController::class,
                'job-posts' => Admin\JobPostController::class,
                'job-applications' => Admin\JobApplicationController::class,
                'subscribers' => Admin\SubscriberController::class,
                'newsletter-campaigns' => Admin\NewsletterCampaignController::class,
                'users' => Admin\UserController::class,
                'roles' => Admin\RoleController::class,
            ];

            foreach ($crud as $uri => $controller) {
                Route::post($uri.'/reorder', [$controller, 'reorder'])->name($uri.'.reorder');
                Route::apiResource($uri, $controller)->parameters([$uri => 'id'])->whereNumber('id');
            }
        });
    });
});
