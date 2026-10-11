<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\GalleryAlbumRequest;
use App\Http\Resources\GalleryAlbumResource;
use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class GalleryAlbumController extends CrudController
{
    protected string $model = GalleryAlbum::class;

    protected string $resource = GalleryAlbumResource::class;

    protected string $request = GalleryAlbumRequest::class;

    protected array $searchable = ['title', 'description'];

    protected array $filters = ['is_active' => 'is_active'];

    protected array $withCount = ['images'];

    protected array $showWith = ['images'];

    /** Album photos are replaced with the submitted, ordered list ("alt" is the caption). */
    protected function afterSave(Model $model, FormRequest $request): void
    {
        if (! $request->has('images')) {
            return;
        }

        $model->images()->delete();
        foreach (array_values($request->validated('images') ?? []) as $i => $image) {
            $model->images()->create(['path' => $image['path'], 'caption' => $image['alt'] ?? null, 'sort_order' => $i + 1]);
        }
    }
}
