<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsCategoryResource;
use App\Http\Resources\NewsPostResource;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NewsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:1|max:30',
        ]);

        $posts = NewsPost::published()
            ->with('category')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $term = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
            })
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($c) => $c->where('slug', $slug)->where('is_active', true)))
            ->latest('published_at')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 9)
            ->withQueryString();

        return NewsPostResource::collection($posts);
    }

    public function show(string $slug): NewsPostResource
    {
        $post = NewsPost::published()->where('slug', $slug)->with('category')->firstOrFail();

        $recent = NewsPost::published()->where('id', '!=', $post->id)->latest('published_at')->limit(3)->get();

        return (new NewsPostResource($post))->additional(['recent' => NewsPostResource::collection($recent)]);
    }

    public function categories(): AnonymousResourceCollection
    {
        return NewsCategoryResource::collection(
            NewsCategory::active()->ordered()->withCount(['posts' => fn ($q) => $q->published()])->get()
        );
    }

    public function recent(): AnonymousResourceCollection
    {
        return NewsPostResource::collection(NewsPost::published()->latest('published_at')->limit(3)->get());
    }
}
