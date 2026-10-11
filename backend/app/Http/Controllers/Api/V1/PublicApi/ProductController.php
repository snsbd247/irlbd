<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Industry;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:200',
            'industry' => 'nullable|string|max:200',
            'featured' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:48',
        ]);

        $products = Product::visible()
            ->with('category')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $term = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('sku', 'like', $term));
            })
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['industry'] ?? null, fn ($query, $slug) => $query->whereHas('industries', fn ($i) => $i->where('slug', $slug)->where('is_active', true)))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->ordered()
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::visible()
            ->where('slug', $slug)
            ->with(['category', 'images', 'specifications', 'variants', 'industries'])
            ->firstOrFail();

        $related = Product::visible()
            ->where('id', '!=', $product->id)
            ->where('product_category_id', $product->product_category_id)
            ->ordered()->limit(4)->get();

        return (new ProductResource($product))->additional(['related' => ProductResource::collection($related)]);
    }

    /** Side-by-side comparison of up to 4 published products: ?slugs=a,b,c */
    public function compare(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['slugs' => 'required|string|max:900']);
        $slugs = array_slice(array_values(array_unique(array_filter(array_map('trim', explode(',', $data['slugs']))))), 0, 4);

        $products = Product::visible()->whereIn('slug', $slugs)
            ->with(['category', 'specifications', 'variants', 'industries'])->get()
            ->sortBy(fn ($p) => array_search($p->slug, $slugs, true))->values();

        return ProductResource::collection($products);
    }

    public function industries(): AnonymousResourceCollection
    {
        return IndustryResource::collection(
            Industry::active()->ordered()->withCount(['products' => fn ($q) => $q->published()])->get()
                ->filter(fn ($i) => $i->products_count > 0)->values()
        );
    }

    public function categories(): AnonymousResourceCollection
    {
        return ProductCategoryResource::collection(
            ProductCategory::active()->ordered()->withCount(['products' => fn ($q) => $q->published()])->get()
        );
    }
}
