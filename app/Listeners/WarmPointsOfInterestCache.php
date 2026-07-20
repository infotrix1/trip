<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DestinationCreated;
use App\Services\MapDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class WarmPointsOfInterestCache implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private readonly MapDataService $maps) {}

    public function handle(DestinationCreated $event): void
    {
        $this->maps->pointsOfInterest(
            (float) $event->destination->latitude,
            (float) $event->destination->longitude,
        );
    }
}
