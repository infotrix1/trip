<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MapDataService
{
    /** @return array<int, array<string, mixed>> */
    public function search(string $query, string $countryCode = 'ng'): array
    {
        $cacheKey = 'map.search.'.sha1(mb_strtolower($query).'|'.$countryCode);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($query, $countryCode): array {
            $response = $this->nominatim()->get('/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'limit' => 5,
                'countrycodes' => $countryCode,
            ])->throw();

            return collect($response->json())
                ->map(static fn (array $item): array => [
                    'place_id' => $item['place_id'] ?? null,
                    'display_name' => $item['display_name'] ?? 'Unknown location',
                    'latitude' => (float) ($item['lat'] ?? 0),
                    'longitude' => (float) ($item['lon'] ?? 0),
                ])->all();
        });
    }

    /** @return array{display_name:string,latitude:float,longitude:float} */
    public function reverse(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('map.reverse.%0.5f.%0.5f', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($latitude, $longitude): array {
            $data = $this->nominatim()->get('/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'jsonv2',
            ])->throw()->json();

            if (! is_array($data) || ! isset($data['display_name'])) {
                throw new RuntimeException('The map provider returned an invalid reverse-geocoding response.');
            }

            return [
                'display_name' => (string) $data['display_name'],
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        });
    }

    /** @return array<int, array{name:string,latitude:float,longitude:float,type:string}> */
    public function pointsOfInterest(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('map.poi.%0.4f.%0.4f', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($latitude, $longitude): array {
            $query = sprintf(
                '[out:json];(node["tourism"](around:1000,%1$f,%2$f);node["amenity"](around:1000,%1$f,%2$f);node["historic"](around:1000,%1$f,%2$f););out body;',
                $latitude,
                $longitude,
            );

            $response = Http::asForm()
                ->acceptJson()
                ->timeout(15)
                ->retry(2, 300)
                ->post(config('services.overpass.url'), ['data' => $query])
                ->throw();

            return collect(Arr::get($response->json(), 'elements', []))
                ->filter(static fn (array $item): bool => isset($item['lat'], $item['lon']))
                ->take(50)
                ->map(static fn (array $item): array => [
                    'name' => (string) Arr::get($item, 'tags.name', 'Unnamed point of interest'),
                    'latitude' => (float) $item['lat'],
                    'longitude' => (float) $item['lon'],
                    'type' => (string) (Arr::get($item, 'tags.tourism')
                        ?? Arr::get($item, 'tags.amenity')
                        ?? Arr::get($item, 'tags.historic')
                        ?? 'unknown'),
                ])->values()->all();
        });
    }

    private function nominatim(): PendingRequest
    {
        return Http::baseUrl(config('services.nominatim.url'))
            ->acceptJson()
            ->withHeaders(['User-Agent' => config('app.name').'/1.0 ('.config('mail.from.address').')'])
            ->timeout(10)
            ->retry(2, 250);
    }
}
