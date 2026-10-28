// src/config/app.ts
export const AppConfig = {
    apiBaseUrl: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
    map: {
        defaultZoom: Number(import.meta.env.VITE_MAP_DEFAULT_ZOOM) || 12,
        defaultCenter: [
            Number(import.meta.env.VITE_MAP_LAT) || 23.7369,
            Number(import.meta.env.VITE_MAP_LNG) || -99.1412,
        ] as [number, number]
    },
    isProduction: import.meta.env.PROD
} as const;