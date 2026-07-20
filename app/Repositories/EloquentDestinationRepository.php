<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentDestinationRepository implements DestinationRepositoryInterface
{
    public function forUser(int $userId): Collection
    {
        return Destination::query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): Destination
    {
        return Destination::query()->create($data);
    }

    public function delete(Destination $destination): bool
    {
        return (bool) $destination->delete();
    }

    public function reorder(int $userId, array $orderedIds): void
    {
        DB::transaction(function () use ($userId, $orderedIds): void {
            foreach ($orderedIds as $position => $destinationId) {
                Destination::query()
                    ->whereKey($destinationId)
                    ->where('user_id', $userId)
                    ->update(['position' => $position + 1]);
            }
        });
    }

    public function nextPositionForUser(int $userId): int
    {
        return ((int) Destination::query()->where('user_id', $userId)->max('position')) + 1;
    }
}
