<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Repositories\EloquentDestinationRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DestinationRepositoryInterface::class, EloquentDestinationRepository::class);
    }

    public function boot(): void
    {
        // Application-wide bootstrapping belongs here.
    }
}
