<script setup lang="ts">
    import { ref } from 'vue';
    import axios from 'axios';
    import { useLayerStore } from '../../stores/useLayerStore';

    const props = defineProps<{
    isOpen: boolean;
    }>();

    const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'import-started', batchId: string): void;
    }>();

    const layerStore = useLayerStore();

    // Modos de asignación de capa
    const layerMode = ref<'existing' | 'new'>('existing');
    const selectedLayerId = ref<number | ''>('');
    const newLayerName = ref('');
    const newLayerColor = ref('#3b82f6');

    // Manejo del archivo
    const file = ref<File | null>(null);
    const isDragging = ref(false);

    // Estado de la petición
    const isUploading = ref(false);
    const uploadProgress = ref(0);
    const statusMessage = ref<string | null>(null);
    const errorMessage = ref<string | null>(null);

    const onFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        validateAndAssignFile(target.files[0]);
    }
    };

    const onDrop = (event: DragEvent) => {
    isDragging.value = false;
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        validateAndAssignFile(event.dataTransfer.files[0]);
    }
    };

    const validateAndAssignFile = (selectedFile: File) => {
    errorMessage.value = null;
    const extension = selectedFile.name.split('.').pop()?.toLowerCase();
    
    if (extension !== 'json' && extension !== 'geojson') {
        errorMessage.value = 'Solo se admiten archivos .geojson o .json';
        return;
    }

    file.value = selectedFile;
    };

    const handleUpload = async () => {
    if (!file.value) {
        errorMessage.value = 'Por favor selecciona un archivo vectorial.';
        return;
    }

    isUploading.value = true;
    uploadProgress.value = 0;
    errorMessage.value = null;
    statusMessage.value = 'Iniciando subida...';

    try {
        let targetLayerId: number;

        // 1. Si eligió crear una capa nueva, la creamos primero
        if (layerMode.value === 'new') {
        if (!newLayerName.value.trim()) {
            throw new Error('Debes ingresar el nombre de la nueva capa.');
        }
        statusMessage.value = 'Registrando nueva capa...';
        const created = await layerStore.createLayer(newLayerName.value.trim(), newLayerColor.value);
        targetLayerId = created.id;
        } else {
        if (!selectedLayerId.value) {
            throw new Error('Debes seleccionar una capa de destino.');
        }
        targetLayerId = Number(selectedLayerId.value);
        }

        // 2. Preparar el FormData para el endpoint asíncrono
        const formData = new FormData();
        formData.append('layer_id', targetLayerId.toString());
        formData.append('file', file.value);

        statusMessage.value = 'Subiendo archivo al servidor...';

        const response = await axios.post('http://127.0.0.1:8000/api/imports/geojson', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            }
        },
        });

        statusMessage.value = '¡Archivo encolado! Procesando geometrías en segundo plano.';
        emit('import-started', response.data.batch_id);

        // Reiniciar y cerrar tras breve espera
        setTimeout(() => {
        resetForm();
        emit('close');
        }, 1400);

    } catch (err: any) {
        errorMessage.value = err.response?.data?.message || err.message || 'Error durante la subida.';
    } finally {
        isUploading.value = false;
    }
    };

    const resetForm = () => {
    file.value = null;
    uploadProgress.value = 0;
    statusMessage.value = null;
    errorMessage.value = null;
    newLayerName.value = '';
    };
</script>

<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <div class="modal-title">
          <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <h3>Importar Archivo GeoJSON</h3>
        </div>
        <button class="close-btn" @click="emit('close')">✕</button>
      </header>

      <div class="modal-body">
        <!-- Selector de Capa Destino -->
        <fieldset class="form-section">
          <legend class="section-label">Destino de la geometría</legend>
          
          <div class="mode-selector">
            <button 
              type="button" 
              class="tab-btn" 
              :class="{ active: layerMode === 'existing' }" 
              @click="layerMode = 'existing'"
            >
              Capa Existente
            </button>
            <button 
              type="button" 
              class="tab-btn" 
              :class="{ active: layerMode === 'new' }" 
              @click="layerMode = 'new'"
            >
              Nueva Capa
            </button>
          </div>

          <!-- Capa existente -->
          <div v-if="layerMode === 'existing'" class="input-group">
            <select v-model="selectedLayerId" class="select-input">
              <option value="" disabled>Selecciona una capa...</option>
              <option v-for="l in layerStore.layers" :key="l.id" :value="l.id">
                {{ l.name }}
              </option>
            </select>
          </div>

          <!-- Nueva capa -->
          <div v-else class="new-layer-fields">
            <input 
              v-model="newLayerName" 
              type="text" 
              placeholder="Nombre (ej. Rutas Históricas 1920)" 
              class="text-input" 
            />
            <div class="color-picker-wrapper">
              <label for="layer-color">Color:</label>
              <input id="layer-color" v-model="newLayerColor" type="color" class="color-input" />
            </div>
          </div>
        </fieldset>

        <!-- Dropzone para arrastrar y soltar -->
        <div 
          class="dropzone" 
          :class="{ dragging: isDragging, 'has-file': !!file }"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="onDrop"
          @click="($refs.fileInput as HTMLInputElement).click()"
        >
          <input 
            ref="fileInput" 
            type="file" 
            accept=".json,.geojson" 
            class="hidden-input" 
            @change="onFileSelect" 
          />

          <div v-if="!file" class="dropzone-empty">
            <span class="upload-icon">📁</span>
            <p class="dropzone-text"><strong>Haz clic</strong> o arrastra tu archivo <strong>.geojson</strong> aquí</p>
            <span class="file-hint">Límite máximo recomendado: 100 MB</span>
          </div>

          <div v-else class="dropzone-file">
            <span class="file-badge">🗺️</span>
            <div class="file-info">
              <strong class="file-name">{{ file.name }}</strong>
              <small class="file-size">{{ (file.size / (1024 * 1024)).toFixed(2) }} MB</small>
            </div>
            <button type="button" class="remove-btn" @click.stop="file = null">Cambiar</button>
          </div>
        </div>

        <!-- Barra de Progreso y Mensajes -->
        <div v-if="isUploading || uploadProgress > 0" class="progress-section">
          <div class="progress-bar-track">
            <div class="progress-bar-fill" :style="{ width: `${uploadProgress}%` }"></div>
          </div>
          <span class="progress-percentage">{{ uploadProgress }}% subido</span>
        </div>

        <div v-if="statusMessage" class="status-box success">
          {{ statusMessage }}
        </div>

        <div v-if="errorMessage" class="status-box error">
          {{ errorMessage }}
        </div>
      </div>

      <footer class="modal-footer">
        <button class="btn btn-secondary" :disabled="isUploading" @click="emit('close')">
          Cancelar
        </button>
        <button 
          class="btn btn-primary" 
          :disabled="isUploading || !file || (layerMode === 'existing' && !selectedLayerId)" 
          @click="handleUpload"
        >
          {{ isUploading ? 'Procesando...' : 'Iniciar Importación' }}
        </button>
      </footer>
    </div>
  </div>
</template>

<style scoped>
    .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    }

    .modal-card {
    width: 100%;
    max-width: 500px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    font-family: system-ui, -apple-system, sans-serif;
    display: flex;
    flex-direction: column;
    }

    .modal-header {
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    }

    .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .modal-title h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
    }

    .header-icon {
    width: 20px;
    height: 20px;
    color: #2563eb;
    }

    .close-btn {
    background: transparent;
    border: none;
    font-size: 16px;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    }

    .close-btn:hover {
    color: #334155;
    }

    .modal-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    }

    .form-section {
    border: none;
    padding: 0;
    margin: 0;
    }

    .section-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 8px;
    }

    .mode-selector {
    display: flex;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 3px;
    margin-bottom: 10px;
    }

    .tab-btn {
    flex: 1;
    border: none;
    background: transparent;
    padding: 6px 12px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s;
    }

    .tab-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .select-input, .text-input {
    width: 100%;
    padding: 9px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 13.5px;
    outline: none;
    box-sizing: border-box;
    }

    .select-input:focus, .text-input:focus {
    border-color: #2563eb;
    }

    .new-layer-fields {
    display: flex;
    gap: 10px;
    align-items: center;
    }

    .color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #475569;
    }

    .color-input {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    cursor: pointer;
    }

    /* Dropzone */
    .dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 24px 16px;
    text-align: center;
    cursor: pointer;
    background: #f8fafc;
    transition: all 0.2s;
    }

    .dropzone.dragging {
    border-color: #2563eb;
    background: #eff6ff;
    }

    .dropzone.has-file {
    border-color: #93c5fd;
    background: #f0fdf4;
    }

    .hidden-input {
    display: none;
    }

    .upload-icon {
    font-size: 32px;
    }

    .dropzone-text {
    font-size: 13.5px;
    color: #334155;
    margin: 6px 0;
    }

    .file-hint {
    font-size: 11px;
    color: #94a3b8;
    }

    .dropzone-file {
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
    }

    .file-badge {
    font-size: 24px;
    }

    .file-info {
    flex-grow: 1;
    padding: 0 12px;
    }

    .file-name {
    display: block;
    font-size: 13.5px;
    color: #0f172a;
    word-break: break-all;
    }

    .file-size {
    color: #64748b;
    font-size: 11px;
    }

    .remove-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 6px;
    cursor: pointer;
    }

    /* Progreso */
    .progress-section {
    display: flex;
    flex-direction: column;
    gap: 4px;
    }

    .progress-bar-track {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    }

    .progress-bar-fill {
    height: 100%;
    background: #2563eb;
    transition: width 0.2s ease;
    }

    .progress-percentage {
    font-size: 11px;
    color: #64748b;
    text-align: right;
    }

    .status-box {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12.5px;
    }

    .status-box.success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
    }

    .status-box.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    }

    .modal-footer {
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    }

    .btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    }

    .btn-secondary {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    }

    .btn-primary {
    background: #2563eb;
    color: #ffffff;
    }

    .btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    }
</style>