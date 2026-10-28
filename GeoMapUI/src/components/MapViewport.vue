<script setup lang="ts">
  import { ref, onMounted, watch } from 'vue';
  import { useGeoMap } from '../composables/useGeoMap';
  import { useSpatialQuery } from '../composables/useSpatialQuery';
  import { useLayerStore } from '../stores/useLayerStore';
  import { useTimelineStore } from '../stores/useTimelineStore';
  import LayerControl from './map/LayerControl.vue';
  import LayerImportModal from './map/LayerImportModal.vue';
  import TimelineSlider from './timeline/TimelineSlider.vue';
  import { AppConfig } from '@/config/index.ts';

  const mapContainer = ref<HTMLDivElement | null>(null);

  const { map, initMap, updateGeoJson, updateLayerOpacity } = useGeoMap();
  const { isLoading, totalCount, debouncedFetch } = useSpatialQuery(`${AppConfig.apiBaseUrl}/features/bounds`);
  
  const layerStore = useLayerStore();
  const timelineStore = useTimelineStore();

  const isImportModalOpen = ref(false);

  const reloadViewport = () => {
    if (!map.value) return;

    if (layerStore.layers.length > 0 && layerStore.activeLayersIds.length === 0) {
      updateGeoJson({ type: 'FeatureCollection', features: [] });
      totalCount.value = 0;
      return;
    }

    debouncedFetch(
      map.value,
      (data) => updateGeoJson(data),
      timelineStore.currentIsoTimestamp, // Timestamp dinámico
      layerStore.activeLayersIds         // Capas activas
    );
  };

  watch(
    () => timelineStore.currentYear,
    () => {
      reloadViewport();
    }
  );

  // Modificaciones de opacidad
  const onOpacityChange = ({ layerId, opacity }: { layerId: number; opacity: number }) => {
    updateLayerOpacity(layerId, opacity);
  };

  const onImportStarted = async (batchId: string) => {
    console.log(`Lote iniciado: ${batchId}`);
    await layerStore.fetchLayers();
    setTimeout(() => {
      reloadViewport();
    }, 1000);
  };

  onMounted(() => {
    if (mapContainer.value) {
      const mapInstance = initMap(mapContainer.value, [23.7369, -99.1412], 14);
      // Escuchar cuando el usuario termina de hacer pan o zoom
      mapInstance.on('moveend', reloadViewport);
      // Carga inicial
      reloadViewport();
    }
  });
</script>

<template>
  <div class="map-wrapper">
    <!-- Panel de control de capas con evento de apertura -->
    <LayerControl
      @change-visibility="reloadViewport"
      @change-opacity="onOpacityChange"
      @open-import="isImportModalOpen = true"
    />

    <!-- Overlay de estado -->
    <div class="map-status-badge">
      <span v-if="isLoading" class="spinner">⏳ Cargando...</span>
      <span v-else>📍 Elementos en pantalla: <strong>{{ totalCount }}</strong></span>
    </div>

    <!-- Modal de Importación -->
    <LayerImportModal
      :is-open="isImportModalOpen"
      @close="isImportModalOpen = false"
      @import-started="onImportStarted"
    />

    <!-- Timeline Slider y Mapa -->
    <TimelineSlider @time-change="reloadViewport" />
    <div ref="mapContainer" class="map-container"></div>
  </div>
</template>

<style scoped>
  .map-wrapper {
    position: relative;
    width: 100%;
    height: 100vh;
  }

  .map-container {
    width: 100%;
    height: 100%;
  }

  .map-status-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    font-family: system-ui, sans-serif;
    font-size: 13px;
    color: #333;
  }
</style>
