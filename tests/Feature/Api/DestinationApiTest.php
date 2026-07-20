<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\DestinationCreated;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DestinationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_destinations(): void
    {
        $this->getJson('/api/destinations')->assertUnauthorized();
    }

    public function test_user_only_receives_their_destinations_in_position_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Destination::factory()->for($user)->create(['name' => 'Second', 'position' => 2]);
        Destination::factory()->for($user)->create(['name' => 'First', 'position' => 1]);
        Destination::factory()->for($otherUser)->create(['name' => 'Private destination']);

        Sanctum::actingAs($user);

        $this->getJson('/api/destinations')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'First')
            ->assertJsonPath('1.name', 'Second')
            ->assertJsonMissing(['name' => 'Private destination']);
    }

    public function test_user_can_create_a_destination(): void
    {
        Event::fake([DestinationCreated::class]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/destinations', [
            'name' => 'Manchester, United Kingdom',
            'latitude' => 53.4808,
            'longitude' => -2.2426,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Manchester, United Kingdom')
            ->assertJsonPath('position', 1);

        $this->assertDatabaseHas('destinations', [
            'user_id' => $user->id,
            'name' => 'Manchester, United Kingdom',
        ]);

        Event::assertDispatched(DestinationCreated::class);
    }

    public function test_destination_coordinates_are_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/destinations', [
            'name' => 'Invalid',
            'latitude' => 100,
            'longitude' => -200,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_user_can_delete_their_destination(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/destinations/{$destination->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Destination deleted successfully.');

        $this->assertModelMissing($destination);
    }

    public function test_user_cannot_delete_another_users_destination(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->for(User::factory()->create())->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/destinations/{$destination->id}")->assertForbidden();
        $this->assertModelExists($destination);
    }

    public function test_user_can_reorder_all_owned_destinations(): void
    {
        $user = User::factory()->create();
        $first = Destination::factory()->for($user)->create(['position' => 1]);
        $second = Destination::factory()->for($user)->create(['position' => 2]);
        $third = Destination::factory()->for($user)->create(['position' => 3]);
        Sanctum::actingAs($user);

        $this->putJson('/api/destinations/reorder', [
            'destination_ids' => [$third->id, $first->id, $second->id],
        ])->assertOk();

        $this->assertDatabaseHas('destinations', ['id' => $third->id, 'position' => 1]);
        $this->assertDatabaseHas('destinations', ['id' => $first->id, 'position' => 2]);
        $this->assertDatabaseHas('destinations', ['id' => $second->id, 'position' => 3]);
    }

    public function test_reorder_rejects_an_incomplete_destination_set(): void
    {
        $user = User::factory()->create();
        $first = Destination::factory()->for($user)->create(['position' => 1]);
        Destination::factory()->for($user)->create(['position' => 2]);
        Sanctum::actingAs($user);

        $this->putJson('/api/destinations/reorder', [
            'destination_ids' => [$first->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('destination_ids');
    }
}
