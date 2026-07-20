<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final class CreateDestinationData
{
    public function __construct(
        public string $name,
        public float $latitude,
        public float $longitude,
    ) {
    }
}
