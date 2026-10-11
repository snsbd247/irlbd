<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Read-only audit trail (users.manage permission). Entries cannot be edited or deleted via the API. */
class ActivityLogController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('view-activity-log');
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'action' => 'nullable|string|max:40',
            'user' => 'nullable|integer',
            'type' => 'nullable|string|max:60',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $logs = ActivityLog::query()
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($filters['user'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('subject_type', $t))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('subject_label', 'like', $like)->orWhere('user_name', 'like', $like)->orWhere('ip_address', 'like', $like));
            })
            ->latest('id')
            ->paginate($filters['per_page'] ?? 30)
            ->withQueryString();

        return ActivityLogResource::collection($logs);
    }
}
