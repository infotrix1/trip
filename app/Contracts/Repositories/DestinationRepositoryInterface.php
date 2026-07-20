<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Collection;

interface DestinationRepositoryInterface
{
    /** @return Collection<int, Destination> */
    public function forUser(int $userId): Collection;

    /** @param array{name:string,latitude:float,longitude:float,user_id:int,position:int} $data */
    public function create(array $data): Destination;

    public function delete(Destination $destination): bool;

    /** @param array<int, int> $orderedIds */
    public function reorder(int $userId, array $orderedIds): void;

    public function nextPositionForUser(int $userId): int;
}
