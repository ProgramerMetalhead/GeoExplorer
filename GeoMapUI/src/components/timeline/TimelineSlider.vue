<script setup lang="ts">
    import { useTimelineStore } from '../../stores/useTimelineStore';

    const emit = defineEmits<{
    (e: 'time-change', isoDate: string): void;
    }>();

    const timelineStore = useTimelineStore();

    const onSliderInput = (event: Event) => {
        const target = event.target as HTMLInputElement;
        timelineStore.setYear(parseInt(target.value, 10));
        emit('time-change', timelineStore.currentIsoTimestamp);
    };

    const onTogglePlayback = () => {
        timelineStore.togglePlay();
        emit('time-change', timelineStore.currentIsoTimestamp);
    };

    const onStep = (direction: 'prev' | 'next') => {
    if (direction === 'prev') timelineStore.prevYear();
    if (direction === 'next') timelineStore.nextYear();
    emit('time-change', timelineStore.currentIsoTimestamp);
    };
</script>

<template>
  <div class="timeline-floating-card">
    <div class="timeline-header">
      <div class="playback-controls">
        <button class="ctrl-btn" @click="onStep('prev')" title="Año anterior">
          ⏮
        </button>
        <button class="ctrl-btn play-btn" @click="onTogglePlayback" :title="timelineStore.isPlaying ? 'Pausar' : 'Reproducir'">
          {{ timelineStore.isPlaying ? '⏸' : '▶' }}
        </button>
        <button class="ctrl-btn" @click="onStep('next')" title="Año siguiente">
          ⏭
        </button>
      </div>

      <!-- Indicador destacado del año activo -->
      <div class="current-year-display">
        <span class="year-label">Año en mapa:</span>
        <strong class="year-value">{{ timelineStore.currentYear }}</strong>
      </div>
    </div>

    <!-- Barra de desplazamiento (Scrubber) -->
    <div class="slider-wrapper">
      <span class="range-bound">{{ timelineStore.minYear }}</span>
      <input
        type="range"
        :min="timelineStore.minYear"
        :max="timelineStore.maxYear"
        :value="timelineStore.currentYear"
        @input="onSliderInput"
        class="timeline-range"
      />
      <span class="range-bound">{{ timelineStore.maxYear }}</span>
    </div>
  </div>
</template>

<style scoped>
  .timeline-floating-card {
    position: absolute;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    width: 90%;
    max-width: 680px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 14px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.6);
    padding: 14px 20px;
    font-family: system-ui, -apple-system, sans-serif;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .timeline-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .playback-controls {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .ctrl-btn {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
  }

  .ctrl-btn:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
  }

  .play-btn {
    background: #2563eb;
    color: #ffffff;
    border-color: #1d4ed8;
    font-size: 16px;
  }

  .play-btn:hover {
    background: #1d4ed8;
  }

  .current-year-display {
    display: flex;
    align-items: baseline;
    gap: 8px;
  }

  .year-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .year-value {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
  }

  .slider-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .range-bound {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
  }

  .timeline-range {
    flex-grow: 1;
    height: 6px;
    border-radius: 3px;
    background: #e2e8f0;
    outline: none;
    accent-color: #2563eb;
    cursor: pointer;
  }
</style>