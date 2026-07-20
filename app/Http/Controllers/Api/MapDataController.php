<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Map\CoordinatesRequest;
use App\Http\Requests\Map\SearchLocationRequest;
use App\Services\MapDataService;
use Illuminate\Http\JsonResponse;

final class MapDataController extends Controller
{
    public function __construct(private readonly MapDataService $maps) {}

    public function search(SearchLocationRequest $request): JsonResponse
    {
        return response()->json($this->maps->search(
            (string) $request->validated('query'),
            strtolower((string) ($request->validated('country_code') ?? 'ng')),
        ));
    }

    public function reverse(CoordinatesRequest $request): JsonResponse
    {
        return response()->json($this->maps->reverse(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        ));
    }

    public function pointsOfInterest(CoordinatesRequest $request): JsonResponse
    {
        return response()->json($this->maps->pointsOfInterest(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        ));
    }
}
