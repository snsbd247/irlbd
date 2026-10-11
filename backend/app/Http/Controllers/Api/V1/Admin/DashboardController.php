<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\JobApplication;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'products' => $user->hasPermission('products.manage') ? [
                'total' => Product::count(),
                'published' => Product::where('status', 'published')->count(),
            ] : null,
            'news' => $user->hasPermission('news.manage') ? [
                'total' => NewsPost::count(),
                'published' => NewsPost::where('status', 'published')->count(),
            ] : null,
            'pages' => $user->hasPermission('pages.manage') ? [
                'total' => Page::count(),
                'published' => Page::where('status', 'published')->count(),
            ] : null,
            'enquiries' => $user->hasPermission('enquiries.manage') ? [
                'total' => ContactSubmission::count(),
                'new' => ContactSubmission::where('status', 'new')->count(),
            ] : null,
            'quotes' => $user->hasPermission('enquiries.manage') ? [
                'total' => QuoteRequest::count(),
                'new' => QuoteRequest::where('status', 'new')->count(),
            ] : null,
            'applications' => $user->hasPermission('careers.manage') ? [
                'total' => JobApplication::count(),
                'new' => JobApplication::where('status', 'new')->count(),
            ] : null,
            'subscribers' => $user->hasPermission('subscribers.manage') ? [
                'total' => NewsletterSubscriber::where('status', 'subscribed')->count(),
            ] : null,
            'media' => $user->hasPermission('media.manage') ? ['total' => Media::count()] : null,
        ];

        $recentEnquiries = $user->hasPermission('enquiries.manage')
            ? ContactSubmission::latest()->limit(5)->get(['id', 'name', 'email', 'subject', 'status', 'created_at'])
            : [];

        $recentNews = $user->hasPermission('news.manage')
            ? NewsPost::latest('updated_at')->limit(5)->get(['id', 'title', 'status', 'updated_at'])
            : [];

        return response()->json([
            'stats' => array_filter($stats),
            'report' => $this->report($user),
            'recent_enquiries' => $recentEnquiries,
            'recent_news' => $recentNews,
        ]);
    }

    /**
     * Last 12 months of incoming leads (only the kinds this user may see) and the
     * products most often asked for in quotations. Grouped in PHP so it works the
     * same on MySQL and SQLite; volumes for a company site are small.
     */
    protected function report($user): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonths($i)->format('Y-m'));
        $series = [];
        $byMonth = function (string $model) use ($start, $months) {
            $counts = $model::where('created_at', '>=', $start)->pluck('created_at')
                ->countBy(fn ($d) => $d->format('Y-m'));

            return $months->map(fn ($m) => (int) ($counts[$m] ?? 0))->values()->all();
        };

        if ($user->hasPermission('enquiries.manage')) {
            $series['enquiries'] = $byMonth(ContactSubmission::class);
            $series['quotes'] = $byMonth(QuoteRequest::class);
        }
        if ($user->hasPermission('careers.manage')) {
            $series['applications'] = $byMonth(JobApplication::class);
        }

        $topProducts = $user->hasPermission('enquiries.manage')
            ? QuoteRequest::where('created_at', '>=', $start)->get(['product_name'])
                ->countBy(fn ($q) => $q->product_name ?: 'Custom / not listed')
                ->sortDesc()->take(5)
                ->map(fn ($n, $name) => ['name' => $name, 'count' => $n])->values()->all()
            : [];

        return ['months' => $months->all(), 'series' => $series, 'top_products' => $topProducts];
    }
}
