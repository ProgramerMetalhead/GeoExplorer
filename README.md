# GeoExplorer & GeoMapUI

Plataforma web geoespacial y temporal (4D) diseñada para importar, gestionar, consultar y visualizar capas cartográficas en formato **GeoJSON** sobre un mapa interactivo con filtrado por ventana geográfica (*Bounding Box*) y línea de tiempo histórica.

---

## Propósito del Proyecto

El objetivo principal del sistema es permitir la exploración ágil de grandes volúmenes de datos vectoriales georreferenciados (puntos, líneas y polígonos) organizados por capas temáticas y vigencia temporal (`valid_from` / `valid_to`). En lugar de cargar todos los registros en memoria en el navegador, el cliente consulta dinámicamente únicamente los elementos que intersectan con el área visible de la pantalla (*viewport*) y que son válidos para la fecha o año seleccionado en la línea de tiempo.

---

## Arquitectura y Módulos Principales

El repositorio está dividido en dos módulos independientes bajo una arquitectura **Cliente-Servidor (API REST + SPA)**:

```text
├── GeoExplorer/   # Backend API REST (Laravel 13 + PHP 8.3+ + MySQL Spatial / PostGIS)
└── GeoMapUI/      # Frontend SPA (Vue 3 + TypeScript + Pinia + Leaflet + Vite)
```

### 1. Backend: `GeoExplorer` (API Geoespacial)

Construido con **Laravel 13**, implementa una arquitectura orientada al dominio (`app/Domain`) para el procesamiento y consulta eficiente de datos espaciales bajo el sistema de referencia **WGS84 (SRID 4326)**.

* **Gestión de Capas (`LayerController` / Modelo `Layer`):**
  * Permite listar capas activas (`GET /api/layers`) y crear nuevas capas (`POST /api/layers`) con nombre, generación automática de `slug` único, color hexadecimal identificativo y estado de activación.
* **Motor de Consultas Espaciales y Temporales (`SpatialFeatureController` / `QueryFeaturesWithinBoundsAction`):**
  * Expone el endpoint `GET /api/features/bounds`.
  * Recibe las coordenadas del *Bounding Box* (`min_lng`, `min_lat`, `max_lng`, `max_lat`), un `timestamp` opcional y un arreglo de capas activas (`layer_ids[]`).
  * Construye un polígono WKT (`BoundingBoxDTO`) y ejecuta consultas espaciales indexadas mediante `ST_Intersects` y transformación `ST_AsGeoJSON`, filtrando simultáneamente por el rango temporal (`valid_from <= timestamp <= valid_to`).
  * Formatea la salida mediante `GeoJsonExporterService` como un estándar `FeatureCollection` enriquecido con metadatos de conteo (`total_count`).
* **Importación Asíncrona de GeoJSON (`GeoJsonImportController` / `ProcessGeoJsonImportJob`):**
  * Expone el endpoint `POST /api/imports/geojson` (respuesta `202 Accepted`).
  * Almacena temporalmente el archivo `.geojson` y encola el trabajo `ProcessGeoJsonImportJob`.
  * Procesa las geometrías en segundo plano insertando en bloques (*batch inserts* de 500 registros con transacciones y `ST_GeomFromGeoJSON`) y extrayendo automáticamente metadatos y vigencia temporal (`valid_from`/`fecha_inicio`, `valid_to`/`fecha_fin`), limpiando el archivo temporal al finalizar.

### 2. Frontend: `GeoMapUI` (Visor Cartográfico Interactivo)

Aplicación de página única (SPA) desarrollada con **Vue 3 (Composition API)**, **TypeScript**, **Pinia** y **Leaflet**.

* **Visor Principal y Renderizado Cartográfico (`MapViewport.vue` y `useGeoMap.ts`):**
  * Inicializa el lienzo de Leaflet con soporte para geometrías `Point`, `LineString`, `Polygon` y sus variantes multiparte.
  * Aplica estilos dinámicos por capa (color de línea/relleno y opacidad configurable) y genera *popups* automáticos con las propiedades (`properties`) de cada elemento e información de su capa.
* **Consultas Espaciales Reactivas (`useSpatialQuery.ts`):**
  * Escucha los eventos de movimiento y zoom del mapa (`moveend`), calcula los límites geográficos normalizados (`[-180, 180]`, `[-90, 90]`) y ejecuta peticiones con *debounce* (350 ms) hacia la API para evitar saturar el servidor durante el desplazamiento.
* **Control de Capas e Importación (`LayerControl.vue`, `LayerImportModal.vue`, `useLayerStore.ts`):**
  * Panel flotante que permite encender/apagar capas visibles, ajustar la opacidad en tiempo real y abrir el modal para crear nuevas capas o subir archivos `.geojson` o `.json`.
* **Filtro de Línea de Tiempo (`TimelineSlider.vue`, `useTimelineStore.ts`):**
  * Control deslizable (slider) con soporte de reproducción automática (*play/pause*) y activación/desactivación del filtro temporal para explorar la evolución histórica de los elementos en el mapa.

---

## Requisitos Previos

* **PHP** >= 8.3 (con extensiones `pdo_mysql`, `mbstring`, `xml`, `bcmath`, `fileinfo`)
* **Composer** >= 2.x
* **Base de datos relacional con soporte espacial:** **MySQL 8.0+** (recomendado por el uso nativo de `ST_GeomFromGeoJSON`, `ST_Intersects` y `axis-order=long-lat`)
* **Node.js** `^22.18.0` o `>=24.12.0` y **npm**

---

## Guía de Instalación y Despliegue Local

### Paso 1: Clonar el repositorio

```bash
git clone https://github.com/ProgramerMetalhead/GeoExplorer.git
cd GeoExplorer
```

### Paso 2: Configurar y desplegar el Backend (`GeoExplorer`)

1. Ingresa al directorio del backend e instala las dependencias de PHP:
   ```bash
   cd GeoExplorer
   composer install
   ```

2. Crea el archivo de variables de entorno y genera la clave de la aplicación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configura la conexión a tu base de datos **MySQL 8+** en el archivo `GeoExplorer/.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=geoexplorer
   DB_USERNAME=root
   DB_PASSWORD=tu_password

   QUEUE_CONNECTION=database
   ```

4. Ejecuta las migraciones (creará las tablas `layers`, `features` con su índice espacial `SPATIAL INDEX`, y las tablas de colas `jobs`):
   ```bash
   php artisan migrate
   ```

5. Inicia el servidor de desarrollo de la API y el trabajador de colas (necesario para procesar las importaciones GeoJSON en segundo plano):
   ```bash
   # Terminal 1: Servidor HTTP (http://127.0.0.1:8000)
   php artisan serve

   # Terminal 2: Worker de colas para importación de GeoJSON
   php artisan queue:work
   ```

### Paso 3: Configurar y desplegar el Frontend (`GeoMapUI`)

1. En una nueva terminal, ingresa al directorio del frontend e instala las dependencias:
   ```bash
   cd GeoMapUI
   npm install
   ```

2. Verifica o crea el archivo `GeoMapUI/.env` apuntando a la URL de la API del backend:
   ```env
   VITE_API_BASE_URL=http://127.0.0.1:8000/api
   VITE_MAP_DEFAULT_ZOOM=13
   ```

3. Inicia el servidor de desarrollo de Vite:
   ```bash
   npm run dev
   ```
   La aplicación estará disponible en `http://localhost:5173`.

---

## Despliegue en Producción

1. **Frontend (`GeoMapUI`):**
   * Actualiza `VITE_API_BASE_URL` en `.env.production` con el dominio público de tu API.
   * Compila los archivos estáticos optimizados:
     ```bash
     cd GeoMapUI
     npm run build
     ```
   * Sirve el contenido generado en `GeoMapUI/dist` mediante **Nginx**, **Apache**, **Vercel** o **Cloudflare Pages**.

2. **Backend (`GeoExplorer`):**
   * Configura `APP_ENV=production`, `APP_DEBUG=false` y las credenciales de base de datos en `GeoExplorer/.env`.
   * Instala dependencias de producción y optimiza cachés:
     ```bash
     cd GeoExplorer
     composer install --no-dev --optimize-autoloader
     php artisan migrate --force
     php artisan config:cache
     php artisan route:cache
     ```
   * Configura **Supervisor** (o equivalente en tu servidor/nube) para mantener en ejecución permanente `php artisan queue:work --tries=2 --timeout=600` y procesar las cargas de archivos GeoJSON.

---

## Resumen de Endpoints de la API

| Método | Endpoint | Descripción | Parámetros Principales |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/layers` | Lista todas las capas activas | Ninguno |
| `POST` | `/api/layers` | Crea una nueva capa | `name` (req), `color` (`#RRGGBB`), `is_active` (bool) |
| `GET` | `/api/features/bounds` | Obtiene el `FeatureCollection` dentro del área visible | `min_lng`, `min_lat`, `max_lng`, `max_lat`, `timestamp` (opc), `layer_ids[]` (opc) |
| `POST` | `/api/imports/geojson` | Encola la importación masiva de un archivo GeoJSON | `layer_id` (req), `file` (archivo `.json`/`.geojson`, máx. 50 MB) |
