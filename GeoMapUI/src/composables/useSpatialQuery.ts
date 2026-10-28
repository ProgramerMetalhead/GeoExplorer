import { ref } from 'vue';
import axios from 'axios';
import debounce from 'lodash.debounce';
import type L from 'leaflet';

const clamp = (value: number, min: number, max: number): number =>
  Math.max(min, Math.min(max, value));

export function useSpatialQuery(apiUrl: string) {
  const isLoading = ref(false);
  const totalCount = ref(0);
  const error = ref<string | null>(null);

  const fetchFeaturesInView = async (
    mapInstance: L.Map, 
    onSuccess: (geoJson: any) => void,
    timestamp: string | null = null,
    layerIds: number[] = []
  ) => {
    isLoading.value = true;
    error.value = null;

    try {
      const bounds = mapInstance.getBounds();
      const minLng = clamp(bounds.getWest(), -180, 180);
      const maxLng = clamp(bounds.getEast(), -180, 180);
      const minLat = clamp(bounds.getSouth(), -90, 90);
      const maxLat = clamp(bounds.getNorth(), -90, 90);

      const params: Record<string, any> = {
        min_lng: Math.min(minLng, maxLng),
        min_lat: Math.min(minLat, maxLat),
        max_lng: Math.max(minLng, maxLng),
        max_lat: Math.max(minLat, maxLat),
      };

      if (timestamp) {
        params.timestamp = timestamp;
      }

      if (layerIds && layerIds.length > 0) {
        params.layer_ids = layerIds;
      }

      const response = await axios.get(apiUrl, { params });
      totalCount.value = response.data.metadata?.total_count ?? 0;
      onSuccess(response.data);
    } catch (err: any) {
      error.value = err.message || 'Error al cargar geometrías';
    } finally {
      isLoading.value = false;
    }
  };

  // Debounce para evitar llamadas repetidas mientras el usuario arrastra el mapa
  const debouncedFetch = debounce(fetchFeaturesInView, 350);

  return {
    isLoading,
    totalCount,
    error,
    debouncedFetch,
  };
}
