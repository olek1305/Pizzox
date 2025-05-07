<template>
  <footer class="bg-gray-700 text-white py-4">
    <div class="container mx-auto px-4">
      <div class="flex flex-wrap justify-between">
        <!-- Restaurant Info -->
        <div class="text-center my-auto">
          <h3 class="text-xl font-bold mb-3">{{ restaurantName }}</h3>
          <p class="mb-4">{{ restaurantAddress }}</p>
          <p class="text-sm">&copy; {{ currentYear }} {{ restaurantName }}. {{ $t('footer.all_rights_reserved') || 'All rights reserved' }}</p>
        </div>

        <!-- Map -->
        <div class="w-full md:w-1/2 h-48">
          <div id="map" class="h-full rounded-lg"></div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

const props = defineProps({
  restaurantName: {
    type: String,
    default: 'Pizzox'
  },
  restaurantAddress: {
    type: String,
    default: 'mnull'
  },
  latitude: {
    type: Number,
    default: 53.132293
  },
  longitude: {
    type: Number,
    default: 17.930851
  },
  mapboxToken: {
    type: String,
    default: 'null'
  }
});

const currentYear = ref(new Date().getFullYear());

onMounted(() => {
  if (props.mapboxToken && props.latitude && props.longitude) {
    mapboxgl.accessToken = props.mapboxToken;

    const map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/streets-v11',
      center: [props.longitude, props.latitude],
      zoom: 14
    });

    // Add marker for restaurant location
    new mapboxgl.Marker()
        .setLngLat([props.longitude, props.latitude])
        .addTo(map);

    // Add navigation controls
    map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');
  }
});
</script>