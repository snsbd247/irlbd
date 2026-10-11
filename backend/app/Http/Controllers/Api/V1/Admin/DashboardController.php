<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Product;
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
            'recent_enquiries' => $recentEnquiries,
            'recent_news' => $recentNews,
        ]);
    }
}
