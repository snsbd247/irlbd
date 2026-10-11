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
    Route::middleware('throttle:api')->group(function () {
        Route::get('settings', [PublicApi\SiteController::class, 'settings'])->name('settings');
        Route::get('navigation', [PublicApi\SiteController::class, 'navigation'])->name('navigation');
        Route::get('search', [PublicApi\SiteController::class, 'search'])->name('search');
        Route::get('sitemap.xml', [PublicApi\SiteController::class, 'sitemap'])->name('sitemap');

        Route::get('home', [PublicApi\PageController::class, 'home'])->name('home');
        Route::get('pages/{slug}', [PublicApi\PageController::class, 'show'])->name('pages.show');

        Route::get('product-categories', [PublicApi\ProductController::class, 'categories'])->name('product-categories.index');
        Route::get('products', [PublicApi\ProductController::class, 'index'])->name('products.index');
        Route::get('products/{slug}', [PublicApi\ProductController::class, 'show'])->name('products.show');

        Route::get('services', [PublicApi\ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{slug}', [PublicApi\ServiceController::class, 'show'])->name('services.show');

        Route::get('news-categories', [PublicApi\NewsController::class, 'categories'])->name('news-categories.index');
        Route::get('news/recent', [PublicApi\NewsController::class, 'recent'])->name('news.recent');
        Route::get('news', [PublicApi\NewsController::class, 'index'])->name('news.index');
        Route::get('news/{slug}', [PublicApi\NewsController::class, 'show'])->name('news.show');

        Route::get('contact-info', [PublicApi\ContactController::class, 'info'])->name('contact.info');
    });

    Route::middleware('throttle:forms')->group(function () {
        Route::post('contact', [PublicApi\ContactController::class, 'store'])->name('contact.store');
        Route::post('quote', [PublicApi\QuoteController::class, 'store'])->name('quote.store');
        Route::post('newsletter/subscribe', [PublicApi\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
        Route::post('newsletter/unsubscribe', [PublicApi\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    });

    // ----------------------------------------------------------------- Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:login')->name('login');

        Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
            Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
            Route::get('me', [Admin\AuthController::class, 'me'])->name('me');
            Route::put('profile', [Admin\AuthController::class, 'updateProfile'])->name('profile');

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
            Route::get('subscribers/export', [Admin\SubscriberController::class, 'export'])->name('subscribers.export');

            $crud = [
                'pages' => Admin\PageController::class,
                'hero-slides' => Admin\HeroSlideController::class,
                'menu-items' => Admin\MenuItemController::class,
                'product-categories' => Admin\ProductCategoryController::class,
                'products' => Admin\ProductController::class,
                'services' => Admin\ServiceController::class,
                'clients' => Admin\ClientController::class,
                'team-members' => Admin\TeamMemberController::class,
                'news-categories' => Admin\NewsCategoryController::class,
                'news-posts' => Admin\NewsPostController::class,
                'milestones' => Admin\MilestoneController::class,
                'certifications' => Admin\CertificationController::class,
                'offices' => Admin\OfficeController::class,
                'faqs' => Admin\FaqController::class,
                'enquiries' => Admin\EnquiryController::class,
                'quote-requests' => Admin\QuoteRequestController::class,
                'subscribers' => Admin\SubscriberController::class,
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
