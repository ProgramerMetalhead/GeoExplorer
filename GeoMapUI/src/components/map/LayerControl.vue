<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { useLayerStore } from '../../stores/useLayerStore';

    const emit = defineEmits<{
        (e: 'change-visibility'): void;
        (e: 'change-opacity', payload: {layerId: number, opacity: number}): void;
        (e: 'open-import'): void;
    }>();

    const layerStore = useLayerStore();
    const isCollapsed = ref(false);

    onMounted(() => {
        layerStore.fetchLayers();
    });

    const handleToggle = (id: number) => {
        layerStore.toggleVisibility(id);
        emit('change-visibility');
    }

    const handleOpacityChange = (id: number, event: Event) => {
        const target = event.target as HTMLInputElement;
        const opacityValue = parseFloat(target.value);
        layerStore.setOpacity(id, opacityValue);
        emit('change-opacity', {layerId: id, opacity: opacityValue});
    }
</script>

<template>
    <aside class="layer-control-panel" :class="{ collapsed: isCollapsed }">
    <!-- Header con botón colapsable -->
    <header class="panel-header">
      <div class="header-title">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
        </svg>
        <span v-if="!isCollapsed">Capas del Territorio</span>
      </div>
      <button 
        class="toggle-btn" 
        @click="isCollapsed = !isCollapsed" 
        :title="isCollapsed ? 'Desplegar capas' : 'Colapsar panel'"
      >
        {{ isCollapsed ? '◀' : '▼' }}
      </button>
    </header>

    <!-- Contenido del panel -->
    <div v-show="!isCollapsed" class="panel-content">
      <div v-if="layerStore.isLoading" class="loading-state">
        Cargando capas disponibles...
      </div>

      <div v-else-if="layerStore.error" class="error-state">
        {{ layerStore.error }}
      </div>

      <div v-else-if="layerStore.layers.length === 0" class="empty-state">
        No hay capas registradas.
      </div>

      <ul v-else class="layer-list">
        <li 
          v-for="layer in layerStore.layers" 
          :key="layer.id" 
          class="layer-item"
          :class="{ disabled: !layer.visible }"
        >
          <!-- Fila principal: Switch, Color y Nombre -->
          <div class="layer-main-row">
            <label class="switch">
              <input 
                type="checkbox" 
                :checked="layer.visible" 
                @change="handleToggle(layer.id)"
              />
              <span class="slider round" :style="{ backgroundColor: layer.visible ? layer.color : '#ccc' }"></span>
            </label>

            <span class="color-dot" :style="{ backgroundColor: layer.color }"></span>
            <span class="layer-name" :title="layer.name">{{ layer.name }}</span>
          </div>

          <!-- Fila secundaria: Control de Opacidad (solo si está activa) -->
          <div v-if="layer.visible" class="opacity-slider-row">
            <span class="opacity-label">Opacidad: {{ Math.round(layer.opacity * 100) }}%</span>
            <input 
              type="range" 
              min="0.1" 
              max="1" 
              step="0.05" 
              :value="layer.opacity"
              @input="handleOpacityChange(layer.id, $event)"
              class="range-input"
            />
          </div>
        </li>
      </ul>
      <!-- Dentro del template de LayerControl.vue, debajo de </ul> -->
      <div class="panel-actions">
        <button class="import-action-btn" @click="$emit('open-import')">
          <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Importar Capa GeoJSON
        </button>
      </div>
    </div>
  </aside>
</template>
<style scoped>
    .layer-control-panel {
    position: absolute;
    top: 16px;
    left: 16px;
    z-index: 1000;
    width: 290px;
    max-height: calc(100vh - 100px);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.5);
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    overflow: hidden;
    transition: width 0.25s ease, height 0.25s ease;
    }

    .layer-control-panel.collapsed {
    width: auto;
    }

    .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: rgba(248, 249, 250, 0.85);
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    .header-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
    }

    .icon {
    width: 18px;
    height: 18px;
    }

    .toggle-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 11px;
    color: #64748b;
    padding: 4px;
    border-radius: 4px;
    }

    .toggle-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #0f172a;
    }

    .panel-content {
    padding: 12px 14px;
    overflow-y: auto;
    max-height: 400px;
    }

    .layer-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    }

    .layer-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    transition: opacity 0.2s ease, border-color 0.2s ease;
    }

    .layer-item.disabled {
    opacity: 0.6;
    border-color: #f1f5f9;
    }

    .layer-main-row {
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    }

    .layer-name {
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-grow: 1;
    }

    /* Interruptor Switch */
    .switch {
    position: relative;
    display: inline-block;
    width: 32px;
    height: 18px;
    flex-shrink: 0;
    }

    .switch input {
    opacity: 0;
    width: 0;
    height: 0;
    }

    .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 34px;
    }

    .slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    }

    input:checked + .slider:before {
    transform: translateX(14px);
    }

    /* Control deslizante de opacidad */
    .opacity-slider-row {
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px dashed #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 4px;
    }

    .opacity-label {
    font-size: 11px;
    color: #64748b;
    }

    .range-input {
    width: 100%;
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    outline: none;
    accent-color: #2563eb;
    cursor: pointer;
    }

    .loading-state, .error-state, .empty-state {
    font-size: 12px;
    text-align: center;
    padding: 12px;
    color: #64748b;
    }
    .error-state {
    color: #ef4444;
    }
    .panel-actions {
      margin-top: 12px;
      padding-top: 10px;
      border-top: 1px solid #f1f5f9;
    }

    .import-action-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      background: #eff6ff;
      color: #2563eb;
      border: 1px dashed #93c5fd;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .import-action-btn:hover {
      background: #dbeafe;
      border-color: #3b82f6;
    }

    .action-icon {
      width: 14px;
      height: 14px;
    }
</style>