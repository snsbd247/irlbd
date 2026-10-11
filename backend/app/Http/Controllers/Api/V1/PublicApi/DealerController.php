<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\DealerResource;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    /** Active dealers, optionally for one district, plus the districts that have any. */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['district' => 'nullable|string|max:40', 'type' => 'nullable|string|max:20']);

        $dealers = Dealer::where('is_active', true)
            ->when($data['district'] ?? null, fn ($q, $d) => $q->where('district', $d))
            ->when($data['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('district')->orderBy('sort_order')->orderBy('name')
            ->get();

        return response()->json([
            'data' => DealerResource::collection($dealers)->resolve($request),
            'districts' => Dealer::where('is_active', true)->distinct()->orderBy('district')->pluck('district'),
        ]);
    }
}
