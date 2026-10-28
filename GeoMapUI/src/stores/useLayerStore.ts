import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { AppConfig } from '@/config';

export interface LayerItem {
    id: number;
    name: string;
    slug: string;
    color: string;
    visible: boolean;
    opacity: number; // Valor entre 0 y 1
}

export const useLayerStore = defineStore('layers', () => {
    const layers = ref<LayerItem[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const createLayer = async (name: string, color: string): Promise<LayerItem> => {
        const response = await axios.post(`${AppConfig.apiBaseUrl}/layers`, {
            name,
            color,
            is_active: true,
        });

        const newLayer: LayerItem = {
            id: response.data.data?.id ?? response.data.id,
            name: response.data.data?.name ?? response.data.name,
            slug: response.data.data?.slug ?? response.data.slug,
            color: response.data.data?.color ?? response.data.color,
            visible: true,
            opacity: 0.8,
        };

        layers.value.push(newLayer);
        return newLayer;
    };
    
    //IDs de las capas que deben ser consultadas a la API
    const activeLayersIds = computed(() => 
        layers.value.filter((l) => l.visible).map((l) => l.id)
    );

    const fetchLayers = async (apiUrl = `${AppConfig.apiBaseUrl}/layers`) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await axios.get(apiUrl);
            const rawData = response.data.data || response.data;

            layers.value = rawData.map((item: any) => ({
                id: item.id,
                name: item.name,
                slug: item.slug,
                color: item.color || '#3388ff',
                visible: true,
                opacity: 0.8,
            }));
        } catch (err: any) {
            error.value = err.message || 'Error al cargar capas';
        } finally {
            isLoading.value = false;
        }
    };

    const toggleVisibility = (layerId: number) => {
        const layer = layers.value.find((l) => l.id === layerId);
        if (layer) {
            layer.visible = !layer.visible;
        }
    };

    const setOpacity = (layerId: number, opacity: number) => {
        const layer = layers.value.find((l) => l.id === layerId);
        if (layer) {
            layer.opacity = Math.max(0, Math.min(1, opacity));
        }
    };

    return {
        layers,
        isLoading,
        error,
        activeLayersIds,
        fetchLayers,
        createLayer,
        toggleVisibility,
        setOpacity,
    };
});