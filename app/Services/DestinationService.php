<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\DataTransferObjects\CreateDestinationData;
use App\Events\DestinationCreated;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DestinationService
{
    public function __construct(
        private readonly DestinationRepositoryInterface $destinations,
    ) {
    }

    /** @return Collection<int, Destination> */
    public function listFor(User $user): Collection
    {
        return $this->destinations->forUser($user->getKey());
    }

    public function createFor(User $user, CreateDestinationData $data): Destination
    {
        $destination = DB::transaction(fn (): Destination => $this->destinations->create([
            'name' => $data->name,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'user_id' => $user->getKey(),
            'position' => $this->destinations->nextPositionForUser($user->getKey()),
        ]));

        DestinationCreated::dispatch($destination);

        return $destination;
    }

    public function delete(Destination $destination): void
    {
        $this->destinations->delete($destination);
    }

    /** @param array<int, int> $orderedIds */
    public function reorderFor(User $user, array $orderedIds): void
    {
        $ownedIds = $this->destinations->forUser($user->getKey())
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $requestedIds = collect($orderedIds)->map(static fn (mixed $id): int => (int) $id)->sort()->values()->all();

        if ($ownedIds !== $requestedIds) {
            throw ValidationException::withMessages([
                'destination_ids' => 'The ordering must contain every destination owned by the authenticated user exactly once.',
            ]);
        }

        $this->destinations->reorder($user->getKey(), $orderedIds);
    }
}
