<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\MenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class MenuItemController extends CrudController
{
    protected string $model = MenuItem::class;

    protected string $resource = MenuItemResource::class;

    protected string $request = MenuItemRequest::class;

    protected array $searchable = ['label'];

    protected array $filters = ['location' => 'location', 'is_active' => 'is_active'];

    protected array $with = ['page'];

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        // Keep only the field relevant to the chosen link type.
        if ($data['link_type'] === 'page') {
            $data['url'] = null;
        } else {
            $data['page_id'] = null;
        }

        return parent::prepare($data, $request, $model);
    }

    /** A parent with children cannot itself become a child (menus are two levels deep). */
    protected function afterSave(Model $model, FormRequest $request): void
    {
        if ($model->parent_id && $model->children()->exists()) {
            abort(422, 'A menu item that has sub-items cannot be nested under another item.');
        }
        $model->children()->update(['location' => $model->location]);
    }
}
