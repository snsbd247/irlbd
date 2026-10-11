<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class PageController extends CrudController
{
    protected string $model = Page::class;

    protected string $resource = PageResource::class;

    protected string $request = PageRequest::class;

    protected array $searchable = ['title', 'slug'];

    protected array $filters = ['status' => 'status', 'template' => 'template'];

    protected array $withCount = ['sections'];

    protected array $order = ['id', 'asc'];

    protected array $sortableColumns = ['id', 'title', 'updated_at'];

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        // System pages keep their template so built-in routes (products, news, contact) keep working.
        if ($model?->is_system) {
            $data['template'] = $model->template;
            if ($model->is_home) {
                $data['status'] = 'published';
                $data['is_home'] = true;
            }
        }

        return parent::prepare($data, $request, $model);
    }

    protected function afterSave(Model $model, FormRequest $request): void
    {
        if ($model->is_home) {
            Page::whereKeyNot($model->id)->where('is_home', true)->update(['is_home' => false]);
        }
    }

    protected function beforeDelete(Model $model): void
    {
        if ($model->is_system || $model->is_home) {
            abort(422, 'Built-in pages cannot be deleted. Set them to Draft to hide them instead.');
        }
    }
}
