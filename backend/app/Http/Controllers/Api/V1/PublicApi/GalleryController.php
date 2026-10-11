<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryAlbumResource;
use App\Models\GalleryAlbum;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GalleryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return GalleryAlbumResource::collection(
            GalleryAlbum::active()->ordered()->withCount('images')->with(['images' => fn ($q) => $q->limit(1)])->get()
                ->filter(fn ($album) => $album->images_count > 0)->values()
        );
    }

    public function show(string $slug): GalleryAlbumResource
    {
        return new GalleryAlbumResource(GalleryAlbum::active()->where('slug', $slug)->with('images')->firstOrFail());
    }
}
