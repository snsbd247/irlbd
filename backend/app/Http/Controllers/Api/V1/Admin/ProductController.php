<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class ProductController extends CrudController
{
    protected string $model = Product::class;

    protected string $resource = ProductResource::class;

    protected string $request = ProductRequest::class;

    protected array $searchable = ['name', 'sku', 'short_description'];

    protected array $filters = ['status' => 'status', 'category' => 'product_category_id', 'is_featured' => 'is_featured'];

    protected array $with = ['category'];

    protected array $showWith = ['category', 'images', 'specifications'];

    protected array $sortableColumns = ['id', 'created_at', 'updated_at', 'sort_order', 'name'];

    /** Gallery images and specifications are replaced wholesale with the submitted, ordered lists. */
    protected function afterSave(Model $model, FormRequest $request): void
    {
        if ($request->has('images')) {
            $model->images()->delete();
            foreach (array_values($request->validated('images') ?? []) as $i => $image) {
                $model->images()->create(['path' => $image['path'], 'alt' => $image['alt'] ?? null, 'sort_order' => $i + 1]);
            }
        }

        if ($request->has('specifications')) {
            $model->specifications()->delete();
            foreach (array_values($request->validated('specifications') ?? []) as $i => $spec) {
                $model->specifications()->create(['label' => $spec['label'], 'value' => $spec['value'], 'sort_order' => $i + 1]);
            }
        }
    }
}
