<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\CreateDestinationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Destination\ReorderDestinationsRequest;
use App\Http\Requests\Destination\StoreDestinationRequest;
use App\Http\Resources\DestinationResource;
use App\Models\Destination;
use App\Services\DestinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class DestinationController extends Controller
{
    public function __construct(private readonly DestinationService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return DestinationResource::collection($this->service->listFor(request()->user()));
    }

    public function store(StoreDestinationRequest $request): JsonResponse
    {
        $destination = $this->service->createFor($request->user(), new CreateDestinationData(
            name: (string) $request->validated('name'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
        ));

        return (new DestinationResource($destination))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Destination $destination): JsonResponse
    {
        $this->authorize('delete', $destination);
        $this->service->delete($destination);

        return response()->json(['message' => 'Destination deleted successfully.']);
    }

    public function reorder(ReorderDestinationsRequest $request): JsonResponse
    {
        $this->service->reorderFor($request->user(), $request->validated('destination_ids'));

        return response()->json(['message' => 'Destination order updated successfully.']);
    }
}
