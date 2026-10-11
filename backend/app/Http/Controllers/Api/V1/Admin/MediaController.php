<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected MediaService $media) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeMediaAccess($request);

        $items = Media::query()
            ->when($request->filled('folder'), fn ($q) => $q->where('folder', $request->input('folder')))
            ->when($request->input('type') === 'document', fn ($q) => $q->where('mime_type', 'application/pdf'))
            ->when($request->input('type') === 'image', fn ($q) => $q->where('mime_type', 'like', 'image/%'))
            ->when($request->filled('search'), fn ($q) => $q->where('original_name', 'like', '%'.addcslashes($request->input('search'), '%_\\').'%'))
            ->latest('id')
            ->paginate(min((int) $request->input('per_page', 30), 100));

        return MediaResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeMediaAccess($request);

        $mimes = implode(',', array_unique(array_values(config('cms.uploads.mimes'))));
        $data = $request->validate([
            // Images are limited to max_kb; PDFs get a separate (larger) limit checked in MediaService.
            'file' => ['required', 'file', 'max:'.max(config('cms.uploads.max_kb'), config('cms.uploads.document_max_kb')), 'mimes:'.$mimes.',jpeg'],
            'folder' => 'nullable|string|in:'.implode(',', config('cms.uploads.folders')),
            'alt' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        if ($file->getMimeType() !== 'application/pdf' && $file->getSize() > config('cms.uploads.max_kb') * 1024) {
            return response()->json(['message' => 'The image is too large.', 'errors' => ['file' => ['The image may not be larger than '.config('cms.uploads.max_kb').' KB.']]], 422);
        }

        $media = $this->media->store($request->file('file'), $data['folder'] ?? 'general', $request->user()->id, $data['alt'] ?? null);

        return (new MediaResource($media))->response()->setStatusCode(201);
    }

    public function update(Request $request, Media $media): MediaResource
    {
        $this->authorize('update', $media);
        $media->update($request->validate(['alt' => 'nullable|string|max:255']));

        return new MediaResource($media);
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->authorize('delete', $media);
        $this->media->delete($media);

        return response()->json(null, 204);
    }

    /**
     * Editors of any content module need to upload/pick images, so access is granted to
     * anyone holding "media.manage" or any content-management permission.
     */
    protected function authorizeMediaAccess(Request $request): void
    {
        $user = $request->user();
        $modules = ['media', 'pages', 'products', 'services', 'clients', 'gallery', 'team', 'news', 'history', 'quality', 'contact', 'settings'];

        foreach ($modules as $module) {
            if ($user->hasPermission($module.'.manage')) {
                return;
            }
        }

        abort(403, 'You do not have permission to manage media.');
    }
}
