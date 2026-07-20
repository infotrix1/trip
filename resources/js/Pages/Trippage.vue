<template>
    <section class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Route builder</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Plan your next journey</h1>
                <p class="mt-2 text-sm text-slate-500">Search for destinations or double-click the map to select a location.</p>
            </div>

            <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ error }}
            </div>

            <form class="space-y-3" @submit.prevent="handleAddDestination">
                <div class="relative">
                    <label for="destination" class="mb-1 block text-sm font-medium text-slate-700">Destination</label>
                    <input
                        id="destination"
                        v-model="destinationName"
                        type="text"
                        autocomplete="off"
                        placeholder="Start typing a location"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                        @input="searchLocations(destinationName)"
                    >

                    <ul
                        v-if="suggestions.length"
                        class="absolute z-[1000] mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl"
                    >
                        <li v-for="suggestion in suggestions" :key="suggestion.place_id">
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                                @click="chooseSuggestion(suggestion)"
                            >
                                {{ suggestion.display_name }}
                            </button>
                        </li>
                    </ul>
                </div>

                <button
                    type="submit"
                    :disabled="isLoading || !destinationName.trim()"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ isLoading ? 'Saving…' : 'Add destination' }}
                </button>
            </form>

            <div class="mt-6 border-t border-slate-200 pt-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Itinerary</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                        {{ destinations.length }} stops
                    </span>
                </div>

                <p v-if="!hasDestinations && !isLoading" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    Your saved destinations will appear here.
                </p>

                <ol v-else class="space-y-2">
                    <li
                        v-for="(destination, index) in destinations"
                        :key="destination.id"
                        class="flex items-start gap-3 rounded-lg border border-slate-200 p-3"
                    >
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700">
                            {{ index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800" :title="destination.name">
                                {{ destination.name }}
                            </p>
                            <div class="mt-2 flex gap-1">
                                <button type="button" class="action-button" :disabled="index === 0" @click="move(index, -1)">↑</button>
                                <button type="button" class="action-button" :disabled="index === destinations.length - 1" @click="move(index, 1)">↓</button>
                                <button type="button" class="ml-auto text-xs font-medium text-red-600 hover:text-red-700" @click="handleRemove(destination)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </aside>

        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <article class="summary-card">
                    <span class="summary-label">Distance</span>
                    <strong class="summary-value">{{ journeySummary.distance.toFixed(1) }} km</strong>
                </article>
                <article class="summary-card">
                    <span class="summary-label">Estimated time</span>
                    <strong class="summary-value">{{ formatDuration(journeySummary.seconds) }}</strong>
                </article>
                <article class="summary-card">
                    <span class="summary-label">Estimated fuel</span>
                    <strong class="summary-value">{{ journeySummary.fuel.toFixed(1) }} L</strong>
                </article>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div ref="mapElement" class="h-[620px] w-full"></div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-routing-machine';
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css';
import { useTripPlanner } from '@/Composables/useTripPlanner';
import { mapApi } from '@/Services/mapApi';

const mapElement = ref(null);
const destinationName = ref('');
const mapInstance = ref(null);
const userMarker = ref(null);
const destinationMarkers = ref([]);
const poiMarkers = ref([]);
const routingControl = ref(null);
const userLocation = ref(null);
const locationWatchId = ref(null);
const journeySummary = ref({ distance: 0, seconds: 0, fuel: 0 });

const {
    destinations,
    suggestions,
    selectedLocation,
    isLoading,
    error,
    hasDestinations,
    loadDestinations,
    searchLocations,
    selectLocation,
    addDestination,
    removeDestination,
    persistOrder,
} = useTripPlanner();

onMounted(async () => {
    initialiseMap();
    await loadDestinations();
    await nextTick();
    renderTrip();
});

onBeforeUnmount(() => {
    if (locationWatchId.value !== null) navigator.geolocation.clearWatch(locationWatchId.value);
    mapInstance.value?.remove();
});

watch(destinations, renderTrip, { deep: true });

function initialiseMap() {
    mapInstance.value = L.map(mapElement.value, { doubleClickZoom: false }).setView([9.082, 8.6753], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapInstance.value);

    mapInstance.value.on('dblclick', handleMapDoubleClick);

    if (navigator.geolocation) {
        locationWatchId.value = navigator.geolocation.watchPosition(
            ({ coords }) => updateUserLocation(coords.latitude, coords.longitude),
            () => { error.value = 'Location access is unavailable. You can still search and add destinations.'; },
            { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 },
        );
    }
}

function updateUserLocation(latitude, longitude) {
    userLocation.value = L.latLng(latitude, longitude);

    if (!userMarker.value) {
        userMarker.value = L.marker(userLocation.value).addTo(mapInstance.value).bindPopup('Your current location');
        mapInstance.value.setView(userLocation.value, 12);
    } else {
        userMarker.value.setLatLng(userLocation.value);
    }

    renderRoute();
}

async function handleMapDoubleClick({ latlng }) {
    try {
        const location = await mapApi.reverse(latlng.lat, latlng.lng);
        destinationName.value = location.display_name;
        selectLocation(location);
    } catch (exception) {
        error.value = exception?.response?.data?.message ?? 'Unable to identify that map location.';
    }
}

function chooseSuggestion(suggestion) {
    destinationName.value = suggestion.display_name;
    selectLocation(suggestion);
    mapInstance.value.setView([suggestion.latitude, suggestion.longitude], 14);
}

async function handleAddDestination() {
    const destination = await addDestination(destinationName.value);
    if (!destination) return;

    destinationName.value = '';
    await loadPointsOfInterest(destination);
}

async function handleRemove(destination) {
    if (!window.confirm(`Remove “${destination.name}” from this trip?`)) return;
    await removeDestination(destination);
}

async function move(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= destinations.value.length) return;

    [destinations.value[index], destinations.value[target]] = [destinations.value[target], destinations.value[index]];
    await persistOrder();
}

function renderTrip() {
    if (!mapInstance.value) return;

    destinationMarkers.value.forEach((item) => item.remove());
    destinationMarkers.value = destinations.value.map((destination, index) => L.marker([
        destination.latitude,
        destination.longitude,
    ]).addTo(mapInstance.value).bindPopup(`<strong>${index + 1}. ${escapeHtml(destination.name)}</strong>`));

    renderRoute();
}

function renderRoute() {
    if (!mapInstance.value) return;

    if (routingControl.value) {
        mapInstance.value.removeControl(routingControl.value);
        routingControl.value = null;
    }

    const waypoints = [
        ...(userLocation.value ? [userLocation.value] : []),
        ...destinations.value.map(({ latitude, longitude }) => L.latLng(latitude, longitude)),
    ];

    if (waypoints.length < 2) {
        journeySummary.value = { distance: 0, seconds: 0, fuel: 0 };
        return;
    }

    routingControl.value = L.Routing.control({
        waypoints,
        addWaypoints: false,
        draggableWaypoints: false,
        routeWhileDragging: false,
        showAlternatives: false,
        fitSelectedRoutes: true,
        lineOptions: { styles: [{ opacity: 0.75, weight: 5 }] },
        createMarker: () => null,
    }).on('routesfound', ({ routes }) => {
        const summary = routes[0]?.summary;
        if (!summary) return;

        const distance = summary.totalDistance / 1000;
        journeySummary.value = {
            distance,
            seconds: summary.totalTime,
            fuel: distance * 0.07,
        };
    }).addTo(mapInstance.value);
}

async function loadPointsOfInterest(destination) {
    poiMarkers.value.forEach((item) => item.remove());
    poiMarkers.value = [];

    try {
        const points = await mapApi.pointsOfInterest(destination.latitude, destination.longitude);
        poiMarkers.value = points.map((point) => L.circleMarker([point.latitude, point.longitude], {
            radius: 5,
            weight: 1,
            fillOpacity: 0.7,
        }).addTo(mapInstance.value).bindPopup(`<strong>${escapeHtml(point.name)}</strong><br>${escapeHtml(point.type)}`));
    } catch {
        // POIs are supplementary; route planning remains available if the provider is unavailable.
    }
}

function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.round((seconds % 3600) / 60);
    return hours ? `${hours}h ${minutes}m` : `${minutes}m`;
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
}
</script>

<style scoped>
.action-button {
    @apply rounded border border-slate-200 px-2 py-0.5 text-xs text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30;
}
.summary-card {
    @apply rounded-xl border border-slate-200 bg-white p-4 shadow-sm;
}
.summary-label {
    @apply block text-xs font-semibold uppercase tracking-wide text-slate-500;
}
.summary-value {
    @apply mt-1 block text-xl text-slate-900;
}
:deep(.leaflet-routing-container) {
    display: none;
}
</style>
