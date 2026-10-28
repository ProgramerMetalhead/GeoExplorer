import { shallowRef, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export function useGeoMap() {
  const map = shallowRef<L.Map | null>(null);
  const geoJsonLayer = shallowRef<L.GeoJSON | null>(null);

  const initMap = (container: HTMLElement, initialCoords: [number, number] = [23.7369, -99.1412], zoom = 13) => {
    map.value = L.map(container, {
      preferCanvas: true, // Renderiza geometrías con Canvas para alto rendimiento
      zoomControl: false, // Desactivamos posición por defecto (topleft) para evitar solapamiento
    }).setView(initialCoords, zoom);

    L.control.zoom({ position: 'bottomleft' }).addTo(map.value);

    // Capa base limpia (CartoDB Positron / OpenStreetMap)
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map.value);

    // Capa vacía donde inyectaremos las geometrías de la API
    geoJsonLayer.value = L.geoJSON(undefined, {
      style: (feature) => ({
        color: feature?.properties?.color || '#3388ff',
        weight: 2,
        fillOpacity: 0.35,
      }),
      pointToLayer: (feature, latlng) => {
        return L.circleMarker(latlng, {
          radius: 8,
          fillColor: feature.properties.color || '#e63946',
          color: '#ffffff',
          weight: 2,
          opacity: 1,
          fillOpacity: 0.85,
        });
      },
      onEachFeature: (feature, layer) => {
        if (feature.properties) {
          const title = feature.properties.name || 'Sin título';
          const layerName = feature.properties.layer_name || '';
          layer.bindPopup(`
            <div style="font-family: sans-serif; font-size: 13px;">
              <strong style="display:block; font-size: 14px;">${title}</strong>
              <span style="color: #666;">Capa: ${layerName}</span>
              ${feature.properties.valid_from ? `<br><small>Periodo: ${feature.properties.valid_from.substring(0, 10)}</small>` : ''}
            </div>
          `);
        }
      },
    }).addTo(map.value);

    return map.value;
  };
  
  const updateLayerOpacity = (layerId: number, opacity: number) => {
    if (!geoJsonLayer.value) return;

    geoJsonLayer.value.eachLayer((layer: any) => {
      // Verificamos si la entidad pertenece a la capa modificada
      if (layer.feature?.properties?.layer_id === layerId) {
        if (typeof layer.setStyle === 'function') {
          // Para poligonos, lineas y CircleMarkers
          layer.setStyle({
            fillOpacity: opacity * 0.5,
            opacity: opacity,
          });
        }
      }
    });
  };

  const updateGeoJson = (data: any) => {
    if (!geoJsonLayer.value) return;
    geoJsonLayer.value.clearLayers();
    geoJsonLayer.value.addData(data);
  };

  onBeforeUnmount(() => {
    map.value?.remove();
    map.value = null;
  });

  return {
    map,
    initMap,
    updateGeoJson,
    updateLayerOpacity,
  };
}
