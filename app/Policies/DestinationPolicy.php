<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Destination;
use App\Models\User;

final class DestinationPolicy
{
    public function delete(User $user, Destination $destination): bool
    {
        return $destination->user_id === $user->getKey();
    }
}
