<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SectionRequest;
use App\Http\Resources\PageSectionResource;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Page/homepage builder: sections of an approved type, with their repeatable items.
 */
class SectionController extends Controller
{
    use AuthorizesRequests;

    public function index(Page $page): AnonymousResourceCollection
    {
        $this->authorize('view', $page);

        return PageSectionResource::collection($page->sections()->with('items')->get());
    }

    public function store(SectionRequest $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $section = DB::transaction(function () use ($request, $page) {
            $section = $page->sections()->create([
                ...$this->fields($request),
                'type' => $request->validated('type'),
                'sort_order' => (int) $page->sections()->max('sort_order') + 1,
            ]);
            $this->syncItems($section, $request);

            return $section;
        });

        return (new PageSectionResource($section->load('items')))->response()->setStatusCode(201);
    }

    public function show(PageSection $section): PageSectionResource
    {
        $this->authorize('view', $section->page);

        return new PageSectionResource($section->load('items'));
    }

    public function update(SectionRequest $request, PageSection $section): PageSectionResource
    {
        $this->authorize('update', $section->page);

        DB::transaction(function () use ($request, $section) {
            $section->update($this->fields($request));
            $this->syncItems($section, $request);
        });

        return new PageSectionResource($section->fresh('items'));
    }

    public function destroy(PageSection $section): JsonResponse
    {
        $this->authorize('update', $section->page);
        $section->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);
        $data = $request->validate(['ids' => 'required|array|max:200', 'ids.*' => 'integer']);

        DB::transaction(function () use ($data, $page) {
            foreach (array_values($data['ids']) as $i => $id) {
                $page->sections()->whereKey($id)->update(['sort_order' => $i + 1]);
            }
        });

        return response()->json(['message' => 'Order saved.']);
    }

    protected function fields(SectionRequest $request): array
    {
        $data = collect($request->validated())->except(['type', 'items', 'settings'])->all();
        $data['settings'] = $request->cleanSettings();

        return $data;
    }

    /** Replace items with the submitted ordered list; existing ids are updated in place. */
    protected function syncItems(PageSection $section, SectionRequest $request): void
    {
        if (! $request->has('items')) {
            return;
        }

        $keep = [];
        foreach (array_values($request->validated('items') ?? []) as $i => $item) {
            $attributes = [
                'title' => $item['title'] ?? null,
                'subtitle' => $item['subtitle'] ?? null,
                'description' => $item['description'] ?? null,
                'value' => $item['value'] ?? null,
                'icon' => $item['icon'] ?? null,
                'image' => $item['image'] ?? null,
                'link_url' => $item['link_url'] ?? null,
                'is_active' => $item['is_active'] ?? true,
                'sort_order' => $i + 1,
            ];

            $existing = ! empty($item['id']) ? $section->items()->whereKey($item['id'])->first() : null;
            if ($existing) {
                $existing->update($attributes);
                $keep[] = $existing->id;
            } else {
                $keep[] = $section->items()->create($attributes)->id;
            }
        }

        $section->items()->whereNotIn('id', $keep)->delete();
    }
}
