import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

document.addEventListener('DOMContentLoaded', function() {
    const tokenInput = document.getElementById('mapbox_setting_mapboxToken');
    const latitudeInput = document.getElementById('mapbox_setting_latitude');
    const longitudeInput = document.getElementById('mapbox_setting_longitude');
    const addressInput = document.getElementById('mapbox_setting_restaurantAddress');
    let map;
    let marker;
    let searchTimeout;
    let suggestionsContainer;

    // Create suggestions container
    createSuggestionsContainer();

    // Initialize map if token exists
    if (tokenInput.value) {
        initializeMap(tokenInput.value);
    }

    // Listen for Mapbox token changes
    tokenInput.addEventListener('change', function() {
        if (tokenInput.value) {
            document.getElementById('map-container').classList.remove('hidden');
            initializeMap(tokenInput.value);
        } else {
            document.getElementById('map-container').classList.add('hidden');
        }
    });

    // Create suggestions container
    function createSuggestionsContainer() {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = 'address-suggestions';
        suggestionsContainer.className = 'bg-white rounded border border-gray-300 shadow-md absolute z-10 w-full max-h-60 overflow-y-auto hidden';

        if (addressInput) {
            const addressParent = addressInput.parentElement;
            addressParent.style.position = 'relative';
            addressParent.appendChild(suggestionsContainer);
        }
    }

    // Handle address input events with debounce
    if (addressInput) {
        addressInput.addEventListener('input', function() {
            console.log("Input detected:", addressInput.value);

            if (!tokenInput.value || !addressInput.value) {
                hideSuggestions();
                return;
            }

            // Clear any existing timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Set a new timeout to search after typing
            console.log("Setting timeout for search...");
            searchTimeout = setTimeout(() => {
                console.log("Timeout completed, searching for:", addressInput.value);
                searchAddress(addressInput.value);
            }, 500);
        });
    }

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target !== addressInput && event.target !== suggestionsContainer && !suggestionsContainer.contains(event.target)) {
            hideSuggestions();
        }
    });

    // Search for address
    function searchAddress(query) {
        console.log("Searching for address:", query);

        if (!tokenInput.value || !query) {
            return;
        }

        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${tokenInput.value}&limit=5`;
        console.log("Making API request to:", url);

        fetch(url)
            .then(response => {
                console.log("Response received:", response.status);
                return response.json();
            })
            .then(data => {
                console.log("Data received:", data);
                displaySuggestions(data.features);
            })
            .catch(error => console.error('Error searching for address:', error));
    }

    /**
     * @typedef {Object} MapboxFeature
     * @property {string} place_name - The full place name provided by Mapbox.
     * @property {string} text - A short alternative or fallback name.
     * @property {number[]} center - An array containing longitude and latitude [lng, lat].
     */

    /**
     * Display suggestions returned by Mapbox API.
     * @param {MapboxFeature[]} features
     */
    function displaySuggestions(features) {
        suggestionsContainer.innerHTML = '';

        if (!features || features.length === 0) {
            hideSuggestions();
            return;
        }

        suggestionsContainer.classList.remove('hidden');

        features.forEach(feature => {
            const suggestion = document.createElement('div');
            suggestion.classList.add('p-2', 'hover:bg-gray-100', 'cursor-pointer');
            suggestion.textContent = feature.place_name;

            suggestion.addEventListener('click', function() {
                const [lng, lat] = feature.center;

                // Update form fields
                addressInput.value = feature.place_name;
                latitudeInput.value = lat;
                longitudeInput.value = lng;

                // Update map
                updateMapMarker(lng, lat);

                hideSuggestions();
            });

            suggestionsContainer.appendChild(suggestion);
        });
    }

    function hideSuggestions() {
        if (suggestionsContainer) {
            suggestionsContainer.classList.add('hidden');
        }
    }

    // Initialize map with token
    function initializeMap(token) {
        mapboxgl.accessToken = token;


        const defaultLat = latitudeInput.value || 53.1235;  // Bydgoszcz
        const defaultLng = longitudeInput.value || 18.0084;

        // Create map
        map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [defaultLng, defaultLat],
            zoom: 12
        });

        // Setup map controls
        map.addControl(new mapboxgl.NavigationControl());

        // Handle map clicks to set marker
        map.on('click', function(e) {
            const lng = e.lngLat.lng;
            const lat = e.lngLat.lat;

            // Update form inputs with new coordinates
            latitudeInput.value = lat;
            longitudeInput.value = lng;

            // Set marker and reverse geocode to get address
            updateMapMarker(lng, lat);
            reverseGeocode(lng, lat);
        });

        // Check if we have existing coordinates
        if (latitudeInput.value && longitudeInput.value) {
            const lng = parseFloat(longitudeInput.value);
            const lat = parseFloat(latitudeInput.value);

            // Update map with existing coordinates
            updateMapMarker(lng, lat);
        } else if (addressInput.value) {
            // If we have address but no coordinates, try to geocode it
            searchAddress(addressInput.value);
        }
    }

    // Update or create marker on the map
    function updateMapMarker(lng, lat) {
        // Center map on coordinates
        map.flyTo({
            center: [lng, lat],
            zoom: 14
        });

        // Update or create marker
        if (marker) {
            marker.setLngLat([lng, lat]);
        } else {
            marker = new mapboxgl.Marker({
                draggable: true
            })
                .setLngLat([lng, lat])
                .addTo(map);

            // Handle marker drag events
            marker.on('dragend', function() {
                const lngLat = marker.getLngLat();
                latitudeInput.value = lngLat.lat;
                longitudeInput.value = lngLat.lng;

                // Update address when marker is dragged
                reverseGeocode(lngLat.lng, lngLat.lat);
            });
        }
    }

    // Reverse geocode to get address from coordinates
    function reverseGeocode(lng, lat) {
        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${tokenInput.value}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    addressInput.value = data.features[0].place_name;
                }
            })
            .catch(error => console.error('Error reverse geocoding:', error));
    }

    // Add click event for search button
    const searchButton = document.getElementById('search-address');
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            if (addressInput.value) {
                searchAddress(addressInput.value);
            }
        });
    }
});