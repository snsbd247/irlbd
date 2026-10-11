<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/**
 * Shared admin CRUD: paginated + searchable + filterable listing, create, update,
 * delete and drag-and-drop reordering. Every action is authorized via the model policy
 * and every write is validated by the resource's Form Request.
 */
abstract class CrudController extends Controller
{
    use AuthorizesRequests;

    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<JsonResource> */
    protected string $resource;

    /** @var class-string<FormRequest> */
    protected string $request;

    protected array $searchable = [];

    /** query param => column, for exact-match filters */
    protected array $filters = [];

    protected array $with = [];

    protected array $withCount = [];

    protected array $showWith = [];

    /** Default ordering: [column, direction] */
    protected array $order = ['sort_order', 'asc'];

    protected array $sortableColumns = ['id', 'created_at', 'updated_at', 'sort_order'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', $this->model);

        $query = $this->model::query()->with($this->with)->withCount($this->withCount);
        $this->scopeIndex($query, $request);

        if (($term = trim((string) $request->input('search'))) !== '' && $this->searchable) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function (Builder $q) use ($like) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        foreach ($this->filters as $param => $column) {
            if ($request->filled($param)) {
                $value = $request->input($param);
                $query->where($column, in_array($value, ['true', 'false'], true) ? $value === 'true' : $value);
            }
        }

        [$column, $direction] = $this->order;
        if (in_array($request->input('sort'), $this->sortableColumns, true)) {
            $column = $request->input('sort');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        }
        $query->orderBy($column, $direction)->orderBy('id', $direction);

        // Small option lists (e.g. category selects) may request everything at once.
        if ($request->boolean('all')) {
            return $this->resource::collection($query->limit(500)->get());
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return $this->resource::collection($query->paginate($perPage)->withQueryString());
    }

    public function show($id)
    {
        $model = $this->find($id);
        $this->authorize('view', $model);

        return new $this->resource($model->load($this->showWith ?: $this->with));
    }

    public function store()
    {
        $this->authorize('create', $this->model);
        $request = app($this->request);

        $model = DB::transaction(function () use ($request) {
            $model = $this->model::create($this->prepare($request->validated(), $request, null));
            $this->afterSave($model, $request);

            return $model;
        });

        return (new $this->resource($model->fresh($this->showWith ?: $this->with)))->response()->setStatusCode(201);
    }

    public function update($id)
    {
        $model = $this->find($id);
        $this->authorize('update', $model);
        $request = app($this->request);

        DB::transaction(function () use ($model, $request) {
            $model->update($this->prepare($request->validated(), $request, $model));
            $this->afterSave($model, $request);
        });

        return new $this->resource($model->fresh($this->showWith ?: $this->with));
    }

    public function destroy($id): JsonResponse
    {
        $model = $this->find($id);
        $this->authorize('delete', $model);
        $this->beforeDelete($model);
        $model->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('reorder', $this->model);
        $data = $request->validate(['ids' => 'required|array|max:500', 'ids.*' => 'integer']);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['ids']) as $index => $id) {
                $this->model::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Order saved.']);
    }

    protected function find($id): Model
    {
        return $this->model::query()->findOrFail($id);
    }

    /** Hook: restrict index query (e.g. by parent). */
    protected function scopeIndex(Builder $query, Request $request): void {}

    /** Hook: transform validated data before persisting. Nested arrays are removed by default. */
    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        return collect($data)->reject(fn ($v) => is_array($v))->all();
    }

    /** Hook: persist relations after the main model is saved. */
    protected function afterSave(Model $model, FormRequest $request): void {}

    /** Hook: guard or clean up before delete. */
    protected function beforeDelete(Model $model): void {}
}
