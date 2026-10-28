import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useTimelineStore = defineStore('timeline', () => {
    // Rango de años configurable
    const minYear = ref<number>(1850);
    const maxYear = ref<number>(2026);
    const currentYear = ref<number>(1920);

    // Estado del reproductor
    const isPlaying = ref<boolean>(false);
    const playbackSpeed = ref<number>(1200);
    let timerId: ReturnType<typeof setInterval> | null = null;

    // Formato ISO para enviar a la API de laravel
    const currentIsoTimestamp = computed( () => {
        return `${currentYear.value}-01-01T00:00:00Z`
    });

    const setYear = (year: number) => {
        currentYear.value = Math.max(minYear.value, Math.min(maxYear.value, year));
    };

    const nextYear = () => {
        if (currentYear.value >= maxYear.value) {
            pause();
            currentYear.value = minYear.value;
        } else {
            currentYear.value++;
        }
    }

    const prevYear = () => {
        if (currentYear.value > minYear.value) {
            currentYear.value--;
        }
    }

    const play = () => {
        if (isPlaying.value) return;
        isPlaying.value = true;

        // Si ya llegó al final, reinicia al inicio
        if (currentYear.value >= maxYear.value) {
        currentYear.value = minYear.value;
        }

        timerId = setInterval(() => {
        nextYear();
        }, playbackSpeed.value);
    };

    const pause = () => {
        isPlaying.value = false;
        if (timerId) {
        clearInterval(timerId);
        timerId = null;
        }
    };

    const togglePlay = () => {
        if (isPlaying.value) {
        pause();
        } else {
        play();
        }
    };

    return {
        minYear,
        maxYear,
        currentYear,
        currentIsoTimestamp,
        isPlaying,
        playbackSpeed,
        setYear,
        nextYear,
        prevYear,
        play,
        pause,
        togglePlay,
    };
    
})