<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Destination;
use App\Policies\DestinationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Destination::class => DestinationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
