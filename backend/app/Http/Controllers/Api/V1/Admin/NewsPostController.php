<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\NewsPostRequest;
use App\Http\Resources\NewsPostResource;
use App\Models\NewsPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class NewsPostController extends CrudController
{
    protected string $model = NewsPost::class;

    protected string $resource = NewsPostResource::class;

    protected string $request = NewsPostRequest::class;

    protected array $searchable = ['title', 'excerpt'];

    protected array $filters = ['status' => 'status', 'category' => 'news_category_id', 'is_featured' => 'is_featured'];

    protected array $with = ['category', 'author'];

    protected array $order = ['created_at', 'desc'];

    protected array $sortableColumns = ['id', 'created_at', 'published_at', 'title'];

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        if (! $model) {
            $data['author_id'] = $request->user()->id;
        }

        return parent::prepare($data, $request, $model);
    }
}
