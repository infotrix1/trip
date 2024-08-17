<template>
    <div class="p-4 space-y-4">
      <!-- Input for Destination -->
      <div class="relative">
        <input
          v-model="newDestination"
          placeholder="Enter a destination"
          @input="fetchSuggestions"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <ul
          v-if="suggestions.length"
          class="absolute top-full left-0 mt-2 w-full bg-white border border-gray-300 rounded-lg shadow-lg z-10"
        >
          <li
            v-for="suggestion in suggestions"
            :key="suggestion.place_id"
            @click="selectSuggestion(suggestion)"
            class="px-4 py-2 cursor-pointer hover:bg-gray-100"
          >
            {{ suggestion.display_name }}
          </li>
        </ul>
      </div>

      <!-- Add Destination Button -->
      <button
        @click="addDestination"
        class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        Add Destination
      </button>

      <!-- Route Information -->
      <div v-if="routeInfo" class="bg-blue-100 p-4 border border-blue-200 rounded-lg shadow-sm">
        <p>{{ routeInfo }}</p>
      </div>

      <!-- Map Display -->
      <div id="map" class="w-full h-80 rounded-lg shadow-md"></div>

      <!-- Journey Summary -->
      <div class="bg-gray-100 p-4 border border-gray-300 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-2">Journey Summary</h3>
        <p>Total Distance: {{ journeySummary.totalDistance.toFixed(2) }} km</p>
        <p>Total Time: {{ journeySummary.totalTime.toFixed(2) }} hours</p>
        <p>Total Fuel: {{ journeySummary.totalFuel.toFixed(2) }} liters</p>
      </div>

      <!-- Destinations List -->
      <ul class="space-y-2">
        <li
          v-for="destination in destinations"
          :key="destination.id"
          class="flex items-center justify-between p-4 bg-white border border-gray-300 rounded-lg shadow-sm"
        >
          <span>{{ destination.name }}</span>
          <button
            @click="removeDestination(destination.id)"
            class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
          >
            Remove
          </button>
        </li>
      </ul>
    </div>
  </template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { map, tileLayer, marker } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-routing-machine';
import { usePage } from '@inertiajs/vue3';

const newDestination = ref('');
const destinations = ref([]);
const mapInstance = ref(null);
const markers = ref([]);
const suggestions = ref([]);
const selectedCoords = ref(null);
const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

const pois = ref([]); // Array to store Points of Interest (POIs)

// Initialize journey summary data
const journeySummary = ref({
  totalDistance: 0,
  totalTime: 0,
  totalFuel: 0,
});

// Fetch existing destinations when component is mounted
onMounted(() => {
  initializeMap();
});

// Initialize map and fetch user location
const initializeMap = () => {
  mapInstance.value = map('map').setView([51.505, -0.09], 13); // Set initial view with zoom level 13
  tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapInstance.value);

  // Watch user's location and update map
  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
      (position) => {
        const { latitude, longitude } = position.coords;
        updateUserLocation(latitude, longitude);
      },
      (error) => {
        console.error('Error getting location', error);
      },
      {
        enableHighAccuracy: true,
        maximumAge: 10000,
        timeout: 5000
      }
    );
  } else {
    console.error('Geolocation is not supported by this browser.');
  }

  // Listen for double-click events on the map to add a destination
  mapInstance.value.on('dblclick', onMapDoubleClick);

  fetchDestinations();
};

// Update map and marker with the user's location
const updateUserLocation = (latitude, longitude) => {
  // Only update the view if the user’s location has changed significantly
  if (!mapInstance.value._lastLatLng || mapInstance.value._lastLatLng.lat !== latitude || mapInstance.value._lastLatLng.lng !== longitude) {
    mapInstance.value.setView([latitude, longitude], 13); // Set the zoom level to 13
    mapInstance.value._lastLatLng = { lat: latitude, lng: longitude }; // Store the last location
  }

  // Remove old user marker if it exists
  if (markers.value.user) {
    mapInstance.value.removeLayer(markers.value.user);
  }

  // Add a new marker for the user's location
  markers.value.user = marker([latitude, longitude])
    .addTo(mapInstance.value)
    .bindPopup('You are here!')
    .openPopup();
};

// Handle double-click on the map to add a destination
const onMapDoubleClick = async (event) => {
  const { lat, lng } = event.latlng;

  // Reverse geocode to get location name
  try {
    const response = await axios.get('https://nominatim.openstreetmap.org/reverse', {
      params: {
        lat,
        lon: lng,
        format: 'json',
      },
    });

    const placeName = response.data.display_name;
    newDestination.value = placeName;
    selectedCoords.value = { latitude: lat, longitude: lng };

    // Automatically add the destination after double-click
    addDestination();
  } catch (error) {
    console.error('Error during reverse geocoding:', error);
  }
};

// Fetch destinations from the server
const fetchDestinations = async () => {
  try {
    const response = await axios.get('/api/destinations');
    destinations.value = response.data;
    updateMap();
  } catch (error) {
    console.error('Error fetching destinations:', error);
  }
};

// Fetch autocomplete suggestions based on user input
const fetchSuggestions = async () => {
  if (newDestination.value.length < 3) {
    suggestions.value = [];
    return;
  }

  try {
    const response = await axios.get('https://nominatim.openstreetmap.org/search', {
      params: {
        q: `${newDestination.value}, Nigeria`,
        format: 'json',
        addressdetails: 1,
        limit: 5,
        countrycodes: 'NG',
      },
    });

    suggestions.value = response.data;
  } catch (error) {
    console.error('Error fetching suggestions:', error);
    suggestions.value = [];
  }
};

// Handle suggestion selection
const selectSuggestion = (suggestion) => {
  newDestination.value = suggestion.display_name;
  selectedCoords.value = {
    latitude: parseFloat(suggestion.lat),
    longitude: parseFloat(suggestion.lon),
  };
  suggestions.value = [];
};

// Add destination
const addDestination = async () => {
  try {
    // Get the user's current position from the map
    const userPosition = mapInstance.value.getCenter();

    // Check if the user has selected a destination from the suggestions or map
    if (!selectedCoords.value) {
      throw new Error('Please select a destination from the suggestions or map.');
    }

    // Make the POST request to add the destination
    const response = await axios.post('/api/destinations', {
      name: newDestination.value,
      latitude: selectedCoords.value.latitude,
      longitude: selectedCoords.value.longitude,
    }, {
      headers: {
        'X-CSRF-TOKEN': csrfToken, // Include CSRF token in the headers
        'Content-Type': 'application/json',
      },
    });

    // Update the list of destinations with the newly added destination
    destinations.value.push(response.data);

    // Reset the input fields
    newDestination.value = '';
    selectedCoords.value = null;

    // Display the route on the map from the user's current location to the selected destination
    L.Routing.control({
      waypoints: [
        L.latLng(userPosition.lat, userPosition.lng), // User's current location
        L.latLng(response.data.latitude, response.data.longitude), // Destination coordinates
      ],
      routeWhileDragging: true,
      lineOptions: {
        styles: [{ color: 'blue', opacity: 0.6, weight: 4 }],
      },
    })
      .on('routesfound', function (e) {
        const routes = e.routes;
        const summary = routes[0].summary;
        updateJourneySummary(summary);
      })
      .addTo(mapInstance.value);

    // Fetch and display POIs along the route
    fetchAndDisplayPOIs(userPosition, response.data);

    // Update the map with the new markers
    updateMap();
  } catch (error) {
    console.error('Error adding destination and showing route:', error);
  }
};

// Update the journey summary with distance and time
const updateJourneySummary = (summary) => {
  journeySummary.value.totalDistance += summary.totalDistance / 1000; // Convert meters to kilometers
  journeySummary.value.totalTime += summary.totalTime / 3600; // Convert seconds to hours
  journeySummary.value.totalFuel += calculateFuelUsage(summary.totalDistance); // Example function to calculate fuel usage
};

// Example function to calculate fuel usage (assuming 7 liters per 100 km)
const calculateFuelUsage = (distance) => {
  const fuelConsumptionRate = 7; // liters per 100 km
  return (distance / 1000) * (fuelConsumptionRate / 100);
};

// Fetch and display POIs along the route
const fetchAndDisplayPOIs = async (startCoords, destinationCoords) => {
  try {
    const query = `
      [out:json];
      (
        node["tourism"](around:1000,${destinationCoords.latitude},${destinationCoords.longitude});
        node["amenity"](around:1000,${destinationCoords.latitude},${destinationCoords.longitude});
        node["historic"](around:1000,${destinationCoords.latitude},${destinationCoords.longitude});
      );
      out body;
    `;

    const response = await axios.post('https://overpass-api.de/api/interpreter', query, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    });

    pois.value = response.data.elements.map((poi) => ({
      name: poi.tags.name || 'Unnamed POI',
      latitude: poi.lat,
      longitude: poi.lon,
      type: poi.tags.tourism || poi.tags.amenity || poi.tags.historic || 'Unknown',
    }));

    // Add POIs to the map
    pois.value.forEach(poi => {
      marker([poi.latitude, poi.longitude])
        .addTo(mapInstance.value)
        .bindPopup(`<b>${poi.name}</b><br>Type: ${poi.type}`);
    });
  } catch (error) {
    console.error('Error fetching POIs:', error);
  }
};

// Remove a destination
const removeDestination = async (id) => {
  try {
    const response = await axios.delete(`/api/destinations/${id}`);
    if (response.data.success) {
      destinations.value = destinations.value.filter(destination => destination.id !== id);
      updateMap();
    }
  } catch (error) {
    console.error('Error removing destination:', error);
  }
};

// Update map with markers
const updateMap = () => {
  markers.value.forEach(m => mapInstance.value.removeLayer(m));
  markers.value = [];
  destinations.value.forEach(destination => {
    const coords = [destination.latitude, destination.longitude];
    const markerObj = marker(coords).addTo(mapInstance.value);
    markers.value.push(markerObj);
  });
};
</script>
