<?php

namespace App\Providers;

use App\Models;
use App\Models\User;
use App\Policies;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Model => policy. Several models share one permission module. */
    protected array $policies = [
        Models\Page::class => Policies\PagePolicy::class,
        Models\PageSection::class => Policies\PagePolicy::class,
        Models\HeroSlide::class => Policies\PagePolicy::class,
        Models\MenuItem::class => Policies\MenuPolicy::class,
        Models\Product::class => Policies\ProductPolicy::class,
        Models\ProductCategory::class => Policies\ProductPolicy::class,
        Models\Industry::class => Policies\ProductPolicy::class,
        Models\Service::class => Policies\ServicePolicy::class,
        Models\Client::class => Policies\ClientPolicy::class,
        Models\Testimonial::class => Policies\ClientPolicy::class,
        Models\GalleryAlbum::class => Policies\GalleryPolicy::class,
        Models\JobPost::class => Policies\CareersPolicy::class,
        Models\JobApplication::class => Policies\CareersPolicy::class,
        Models\TeamMember::class => Policies\TeamPolicy::class,
        Models\NewsPost::class => Policies\NewsPolicy::class,
        Models\NewsCategory::class => Policies\NewsPolicy::class,
        Models\Milestone::class => Policies\HistoryPolicy::class,
        Models\Certification::class => Policies\QualityPolicy::class,
        Models\Office::class => Policies\ContactContentPolicy::class,
        Models\Faq::class => Policies\ContactContentPolicy::class,
        Models\Dealer::class => Policies\ContactContentPolicy::class,
        Models\ContactSubmission::class => Policies\EnquiryPolicy::class,
        Models\QuoteRequest::class => Policies\EnquiryPolicy::class,
        Models\NewsletterSubscriber::class => Policies\SubscriberPolicy::class,
        Models\NewsletterCampaign::class => Policies\SubscriberPolicy::class,
        Models\Media::class => Policies\MediaPolicy::class,
        Models\User::class => Policies\UserPolicy::class,
        Models\Role::class => Policies\RolePolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // Short, stable type names in the translations table (not full class names).
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(collect([
            'Page', 'PageSection', 'SectionItem', 'MenuItem', 'HeroSlide', 'Product', 'ProductCategory', 'ProductSpecification',
            'ProductVariant', 'Service', 'Client', 'NewsPost', 'NewsCategory', 'TeamMember', 'Milestone', 'Certification',
            'Office', 'Faq', 'Testimonial', 'GalleryAlbum', 'GalleryImage', 'Industry', 'JobPost', 'Dealer',
        ])->mapWithKeys(fn ($name) => [$name => 'App\\Models\\'.$name])->all());

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Non-model abilities.
        Gate::define('manage-settings', fn (User $user) => $user->hasPermission('settings.manage'));
        Gate::define('view-activity-log', fn (User $user) => $user->hasPermission('users.manage'));

        // Audit trail: admin changes to content, inbox items and accounts. Public form
        // submissions are not logged because no admin is signed in for those requests.
        foreach (array_keys($this->policies) as $model) {
            $model::observe(\App\Observers\AdminActivityObserver::class);
        }
        Models\Setting::observe(\App\Observers\AdminActivityObserver::class);

        // Password reset links point to the admin SPA, not to a Laravel route.
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(fn (User $user, string $token) => rtrim(config('cms.frontend_url'), '/')
            .'/admin/reset-password?token='.$token.'&email='.urlencode($user->email));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(180)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        // Public form submissions (contact form, newsletter).
        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(50)->by($request->ip()),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(10)->by($request->ip()),
        ]);

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
