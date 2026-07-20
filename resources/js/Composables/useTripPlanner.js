import { computed, onBeforeUnmount, ref } from 'vue';
import { destinationApi } from '@/Services/destinationApi';
import { mapApi } from '@/Services/mapApi';

export function useTripPlanner() {
    const destinations = ref([]);
    const suggestions = ref([]);
    const selectedLocation = ref(null);
    const isLoading = ref(false);
    const error = ref('');
    let searchTimer = null;

    const hasDestinations = computed(() => destinations.value.length > 0);

    async function loadDestinations() {
        isLoading.value = true;
        error.value = '';

        try {
            destinations.value = await destinationApi.list();
        } catch (exception) {
            error.value = normaliseError(exception, 'Unable to load destinations.');
        } finally {
            isLoading.value = false;
        }
    }

    function searchLocations(query) {
        clearTimeout(searchTimer);

        if (query.trim().length < 3) {
            suggestions.value = [];
            return;
        }

        searchTimer = setTimeout(async () => {
            try {
                suggestions.value = await mapApi.search(query.trim());
            } catch (exception) {
                suggestions.value = [];
                error.value = normaliseError(exception, 'Unable to search locations.');
            }
        }, 350);
    }

    function selectLocation(location) {
        selectedLocation.value = location;
        suggestions.value = [];
    }

    async function addDestination(name) {
        if (!selectedLocation.value) {
            error.value = 'Select a location from the suggestions or double-click the map.';
            return null;
        }

        isLoading.value = true;
        error.value = '';

        try {
            const destination = await destinationApi.create({
                name: name.trim(),
                latitude: selectedLocation.value.latitude,
                longitude: selectedLocation.value.longitude,
            });
            destinations.value.push(destination);
            selectedLocation.value = null;
            return destination;
        } catch (exception) {
            error.value = normaliseError(exception, 'Unable to add the destination.');
            return null;
        } finally {
            isLoading.value = false;
        }
    }

    async function removeDestination(destination) {
        try {
            await destinationApi.remove(destination.id);
            destinations.value = destinations.value.filter(({ id }) => id !== destination.id);
        } catch (exception) {
            error.value = normaliseError(exception, 'Unable to remove the destination.');
        }
    }

    async function persistOrder() {
        try {
            await destinationApi.reorder(destinations.value.map(({ id }) => id));
        } catch (exception) {
            error.value = normaliseError(exception, 'Unable to save destination order.');
            await loadDestinations();
        }
    }

    function normaliseError(exception, fallback) {
        return exception?.response?.data?.message
            ?? Object.values(exception?.response?.data?.errors ?? {})?.flat()?.[0]
            ?? fallback;
    }

    onBeforeUnmount(() => clearTimeout(searchTimer));

    return {
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
    };
}
