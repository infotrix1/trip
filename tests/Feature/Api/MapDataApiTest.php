<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MapDataApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_location_search_is_proxied_and_normalised(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([[
                'place_id' => 10,
                'display_name' => 'Abuja, Nigeria',
                'lat' => '9.0765',
                'lon' => '7.3986',
            ]]),
        ]);

        $this->getJson('/api/map/search?query=Abuja&country_code=ng')
            ->assertOk()
            ->assertJsonPath('0.display_name', 'Abuja, Nigeria')
            ->assertJsonPath('0.latitude', 9.0765);
    }

    public function test_reverse_geocoding_is_proxied(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Manchester, United Kingdom',
            ]),
        ]);

        $this->getJson('/api/map/reverse?latitude=53.4808&longitude=-2.2426')
            ->assertOk()
            ->assertJsonPath('display_name', 'Manchester, United Kingdom');
    }
}
