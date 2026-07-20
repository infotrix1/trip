<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\DestinationCreated;
use App\Listeners\WarmPointsOfInterestCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DestinationCreated::class => [
            WarmPointsOfInterestCache::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
