<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rawLocations = @json($mapLocations);
        let activeParkings = @json($mapParkings);

        // Modal elements
        const modal = document.getElementById('gps-map-modal');
        const openModalBtn = document.getElementById('btn-open-map-modal');
        const closeModalBtn = document.getElementById('btn-close-map-modal');
        const recenterBtn = document.getElementById('btn-recenter-map');
        const toggleStyleBtn = document.getElementById('btn-toggle-style');
        const styleLabel = document.getElementById('map-style-label');
        const toggleAreaContextBtn = document.getElementById('btn-toggle-area-context');
        const areaContextLabel = document.getElementById('area-context-label');
        const googleMapsExtBtn = document.getElementById('btn-open-google-maps');
        const toggleFullscreenBtn = document.getElementById('btn-toggle-fullscreen');
        const iconExpand = document.getElementById('fullscreen-icon-expand');
        const iconCollapse = document.getElementById('fullscreen-icon-collapse');
        const placeNameEl = document.getElementById('map-place-name');

        // Quick stats elements
        const statDistance = document.getElementById('map-stat-distance');
        const statSpeed = document.getElementById('map-stat-speed');
        const statTimeSpan = document.getElementById('map-stat-time-span');
        const summaryDevice = document.getElementById('summary-device');
        const summaryTime = document.getElementById('summary-time');
        const summaryCoords = document.getElementById('summary-coords');
        const summarySpeed = document.getElementById('summary-speed');
        const summaryAlt = document.getElementById('summary-alt');

        let mapInstance = null;
        let currentStyleIndex = 0;
        let markersList = [];
        let parkingMarkers = [];
        let isAreaContextActive = false;
        let detectedLocationName = null;
        let nearbyPlaceMarkers = [];
        let nearbyPlacesRequest = null;
        let nearbyPlacesBoundsKey = null;
        const nearbyPlacesStatus = document.getElementById('nearby-places-status');

        // Map Styles: Google Maps Roadmap (shows all local shops, hospital, hotel, guest house), Google Hybrid, OpenStreetMap, Carto Voyager
        const mapStyles = [
            {
                name: 'Google Maps',
                style: {
                    version: 8,
                    sources: {
                        'google-roadmap': {
                            type: 'raster',
                            tiles: [
                                'https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                                'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                                'https://mt2.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                                'https://mt3.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'
                            ],
                            tileSize: 256,
                            attribution: '&copy; Google Maps'
                        }
                    },
                    layers: [
                        {
                            id: 'google-roadmap-layer',
                            type: 'raster',
                            source: 'google-roadmap',
                            minzoom: 0,
                            maxzoom: 20
                        }
                    ]
                }
            },
            {
                name: 'Google Satellite',
                style: {
                    version: 8,
                    sources: {
                        'google-hybrid': {
                            type: 'raster',
                            tiles: [
                                'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                                'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                                'https://mt2.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                                'https://mt3.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'
                            ],
                            tileSize: 256,
                            attribution: '&copy; Google Maps Satellite'
                        }
                    },
                    layers: [
                        {
                            id: 'google-hybrid-layer',
                            type: 'raster',
                            source: 'google-hybrid',
                            minzoom: 0,
                            maxzoom: 20
                        }
                    ]
                }
            },
            {
                name: 'OpenStreetMap',
                style: {
                    version: 8,
                    sources: {
                        'osm-tiles': {
                            type: 'raster',
                            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                            tileSize: 256,
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
                        }
                    },
                    layers: [
                        {
                            id: 'osm-layer',
                            type: 'raster',
                            source: 'osm-tiles',
                            minzoom: 0,
                            maxzoom: 19
                        }
                    ]
                }
            },
            {
                name: 'Carto Voyager',
                style: {
                    version: 8,
                    sources: {
                        'carto-voyager': {
                            type: 'raster',
                            tiles: ['https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png'],
                            tileSize: 256,
                            attribution: '&copy; OpenStreetMap, &copy; CARTO'
                        }
                    },
                    layers: [
                        {
                            id: 'carto-layer',
                            type: 'raster',
                            source: 'carto-voyager',
                            minzoom: 0,
                            maxzoom: 19
                        }
                    ]
                }
            }
        ];

        // Sort locations chronologically (oldest to newest) to draw the movement trajectory correctly
        let sortedLocations = [...rawLocations].sort((a, b) => {
            if (a.recorded_timestamp && b.recorded_timestamp) {
                return a.recorded_timestamp - b.recorded_timestamp;
            }
            return a.id - b.id;
        });

        const updatesUrl = @json(route('tracker.locations.updates'));
        const isFirstPage = @json($locations->currentPage() === 1);
        let lastSeenLocationId = @json($locationCursor);
        let isPollingLocations = false;
        const recordsBody = document.getElementById('location-records-body');
        const totalCountEl = document.getElementById('location-total-count');
        const pageCountEl = document.getElementById('location-page-count');
        const pointCountEl = document.getElementById('map-point-count');
        const mapToolbarCountEl = document.getElementById('map-toolbar-count');
        const recordsSummaryEl = document.getElementById('records-summary-text');

        // Distance in meters between two lat/lng points
        function getDistanceMeters(p1, p2) {
            const toRad = (v) => (v * Math.PI) / 180;
            const R = 6371000;
            const dLat = toRad(p2.latitude - p1.latitude);
            const dLon = toRad(p2.longitude - p1.longitude);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(toRad(p1.latitude)) * Math.cos(toRad(p2.latitude)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        // Haversine formula to calculate cumulative route distance
        function calculateTotalDistanceKm(points) {
            if (points.length < 2) return 0;
            let totalKm = 0;
            for (let i = 0; i < points.length - 1; i++) {
                totalKm += getDistanceMeters(points[i], points[i + 1]) / 1000;
            }
            return totalKm;
        }

        // Reverse Geocoding to identify nearby village, town, and district
        function fetchNearbyPlaceName(lat, lng) {
            if (!placeNameEl || detectedLocationName) return;

            // Set external Google Maps link
            if (googleMapsExtBtn) {
                googleMapsExtBtn.href = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            }

            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=14`;

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    const addr = data.address || {};
                    const town = addr.village || addr.town || addr.city || addr.suburb || addr.hamlet || addr.county || 'Kralpora Area';
                    const district = addr.state_district || addr.county || 'Kupwara';
                    const state = addr.state || 'Jammu and Kashmir';

                    detectedLocationName = `${town}, ${district} (${state})`;
                    placeNameEl.textContent = detectedLocationName;
                })
                .catch(() => {
                    // Intelligent fallback based on coordinate bounding box
                    if (lat >= 34.45 && lat <= 34.55 && lng >= 74.00 && lng <= 74.15) {
                        detectedLocationName = 'Kralpora / Chowkibal, Kupwara (Jammu & Kashmir)';
                    } else {
                        detectedLocationName = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    }
                    placeNameEl.textContent = detectedLocationName;
                });
        }

        // Update stats displays
        function updateMapStats() {
            if (sortedLocations.length === 0) return;

            const totalKm = calculateTotalDistanceKm(sortedLocations);
            if (statDistance) {
                statDistance.textContent = totalKm < 1 ? Math.round(totalKm * 1000) + ' m' : totalKm.toFixed(2) + ' km';
            }

            const latestPoint = sortedLocations[sortedLocations.length - 1];
            const startPoint = sortedLocations[0];

            if (statSpeed && latestPoint) {
                statSpeed.textContent = latestPoint.speed_kmh !== null ? latestPoint.speed_kmh.toFixed(1) + ' km/h' : '—';
            }

            if (statTimeSpan && startPoint && latestPoint) {
                statTimeSpan.textContent = `#${startPoint.id} → #${latestPoint.id}`;
            }

            if (latestPoint) {
                if (summaryDevice) summaryDevice.textContent = latestPoint.device_name + ' (' + latestPoint.device_id + ')';
                if (summaryTime) summaryTime.textContent = latestPoint.recorded_at;
                if (summaryCoords) summaryCoords.textContent = `${latestPoint.latitude.toFixed(6)}, ${latestPoint.longitude.toFixed(6)}`;
                if (summarySpeed) summarySpeed.textContent = latestPoint.speed_kmh !== null ? latestPoint.speed_kmh.toFixed(1) + ' km/h' : '—';
                if (summaryAlt) summaryAlt.textContent = latestPoint.altitude_m !== null ? latestPoint.altitude_m.toFixed(1) + ' m' : '—';

                fetchNearbyPlaceName(latestPoint.latitude, latestPoint.longitude);
            }
        }

        function clearNearbyPlaceMarkers() {
            nearbyPlaceMarkers.forEach(marker => marker.remove());
            nearbyPlaceMarkers = [];
        }

        async function updateNearbyPlaces() {
            if (!mapInstance || !nearbyPlacesStatus) return;

            if (mapInstance.getZoom() < 15) {
                nearbyPlacesRequest?.abort();
                nearbyPlacesRequest = null;
                nearbyPlacesBoundsKey = null;
                clearNearbyPlaceMarkers();
                nearbyPlacesStatus.textContent = 'Zoom in to level 15 to see nearby places';
                return;
            }

            const bounds = mapInstance.getBounds();
            const south = bounds.getSouth().toFixed(5);
            const west = bounds.getWest().toFixed(5);
            const north = bounds.getNorth().toFixed(5);
            const east = bounds.getEast().toFixed(5);
            const boundsKey = `${south},${west},${north},${east}`;

            if (boundsKey === nearbyPlacesBoundsKey) return;

            nearbyPlacesRequest?.abort();
            nearbyPlacesRequest = new AbortController();
            nearbyPlacesBoundsKey = boundsKey;
            clearNearbyPlaceMarkers();
            nearbyPlacesStatus.textContent = 'Loading nearby places…';

            const query = `[out:json][timeout:15];(node["amenity"](${south},${west},${north},${east});way["amenity"](${south},${west},${north},${east});node["shop"](${south},${west},${north},${east});way["shop"](${south},${west},${north},${east});node["tourism"](${south},${west},${north},${east});way["tourism"](${south},${west},${north},${east});node["leisure"](${south},${west},${north},${east});way["leisure"](${south},${west},${north},${east}););out center 60;`;

            try {
                const response = await fetch('https://overpass-api.de/api/interpreter', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: new URLSearchParams({ data: query }),
                    signal: nearbyPlacesRequest.signal
                });

                if (!response.ok) throw new Error('Nearby places request failed');

                const data = await response.json();
                if (boundsKey !== nearbyPlacesBoundsKey) return;

                (data.elements || []).forEach(place => {
                    const latitude = place.lat ?? place.center?.lat;
                    const longitude = place.lon ?? place.center?.lon;
                    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

                    const tags = place.tags || {};
                    const name = tags.name || tags['name:en'] || tags.amenity || tags.shop || tags.tourism || tags.leisure || 'Nearby place';
                    const markerElement = document.createElement('div');
                    markerElement.className = 'flex size-6 items-center justify-center rounded-full border-2 border-white bg-amber-500 text-[10px] font-bold text-slate-950 shadow-lg';
                    markerElement.textContent = 'P';
                    markerElement.title = name;

                    const popupContent = document.createElement('div');
                    const placeName = document.createElement('strong');
                    placeName.textContent = name;
                    popupContent.appendChild(placeName);
                    const placeType = tags.amenity || tags.shop || tags.tourism || tags.leisure;
                    if (placeType) {
                        const typeLabel = document.createElement('div');
                        typeLabel.className = 'mt-1 text-xs text-slate-500';
                        typeLabel.textContent = placeType;
                        popupContent.appendChild(typeLabel);
                    }

                    const marker = new maplibregl.Marker({ element: markerElement })
                        .setLngLat([longitude, latitude])
                        .setPopup(new maplibregl.Popup({ offset: 14 }).setDOMContent(popupContent))
                        .addTo(mapInstance);
                    nearbyPlaceMarkers.push(marker);
                });

                nearbyPlacesStatus.textContent = nearbyPlaceMarkers.length
                    ? `${nearbyPlaceMarkers.length} nearby places · OpenStreetMap`
                    : 'No mapped places in this area';
            } catch (error) {
                if (error.name === 'AbortError') return;
                if (boundsKey === nearbyPlacesBoundsKey) {
                    nearbyPlacesStatus.textContent = 'Nearby places could not be loaded';
                }
            }
        }

        // Escape device-supplied text before inserting it as HTML
        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        // Helper to build rich popup HTML
        function buildPopupContent(item, badgeText, badgeColorClass) {
            const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${item.latitude},${item.longitude}`;
            return `
                <div class="text-xs space-y-2 min-w-[220px]">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-700/80 pb-1.5">
                        <span class="font-bold text-white text-sm">Record #${item.id}</span>
                        <span class="rounded px-2 py-0.5 text-[10px] font-semibold text-white ${badgeColorClass}">${badgeText}</span>
                    </div>
                    <div class="text-slate-300">
                        <p class="font-semibold text-sky-300 truncate">${escapeHtml(item.device_name)}</p>
                        <p class="text-[11px] font-mono text-slate-400">${escapeHtml(item.device_id)}</p>
                    </div>
                    <div class="space-y-1 font-mono text-[11px] text-slate-300">
                        <p><span class="text-slate-400">Position:</span> ${item.latitude.toFixed(6)}, ${item.longitude.toFixed(6)}</p>
                        <p><span class="text-slate-400">Speed:</span> ${item.speed_kmh !== null ? item.speed_kmh.toFixed(1) + ' km/h' : '—'}</p>
                        <p><span class="text-slate-400">Altitude:</span> ${item.altitude_m !== null ? item.altitude_m.toFixed(1) + ' m' : '—'}</p>
                        <p><span class="text-slate-400">Fix / Sats:</span> ${item.gps_fix ? 'Fixed' : 'No fix'} (${item.satellites ?? 0} sats)</p>
                        <p class="text-[10px] text-slate-400"><span class="text-slate-400">Time:</span> ${item.recorded_at}</p>
                    </div>
                    <div class="pt-1 border-t border-slate-700/80 flex items-center justify-between">
                        <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400 capitalize">${escapeHtml(item.source)}</span>
                        <a href="${googleMapsUrl}" target="_blank" rel="noopener noreferrer" class="text-sky-400 hover:text-sky-300 underline text-[11px] font-medium">Open in Google Maps &rarr;</a>
                    </div>
                </div>
            `;
        }

        // Create custom HTML marker element (prevents text collision when start and latest are close)
        function createHtmlMarkerElement(type) {
            const el = document.createElement('div');
            el.style.cursor = 'pointer';

            if (type === 'circuit') {
                // Unified marker when start and latest are at the exact same location (circuit loop)
                el.innerHTML = `
                    <div class="relative flex flex-col items-center select-none" style="transform: translate(0, -50%);">
                        <span class="rounded-full bg-gradient-to-r from-emerald-600 to-rose-600 px-2.5 py-0.5 text-[10px] font-bold text-white shadow-xl ring-1 ring-white/60 tracking-wider">START &amp; FINISH</span>
                        <div class="relative mt-1 flex size-5 items-center justify-center">
                            <span class="absolute inline-flex size-6 rounded-full bg-rose-500 opacity-60 map-pulse-ring"></span>
                            <div class="relative flex size-5 items-center justify-center rounded-full bg-rose-600 ring-2 ring-white shadow-xl">
                                <span class="size-2 rounded-full bg-emerald-300"></span>
                            </div>
                        </div>
                    </div>
                `;
            } else if (type === 'start') {
                el.innerHTML = `
                    <div class="flex flex-col items-center select-none" style="transform: translate(0, -50%);">
                        <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-lg ring-1 ring-emerald-300">START</span>
                        <div class="mt-0.5 flex size-5 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white shadow-xl">
                            <span class="size-2 rounded-full bg-white"></span>
                        </div>
                    </div>
                `;
            } else if (type === 'latest') {
                el.innerHTML = `
                    <div class="relative flex flex-col items-center select-none" style="transform: translate(0, -50%);">
                        <span class="rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-lg ring-1 ring-rose-300">LATEST</span>
                        <div class="relative mt-0.5 flex size-5 items-center justify-center">
                            <span class="absolute inline-flex size-6 rounded-full bg-rose-500 opacity-70 map-pulse-ring"></span>
                            <div class="relative flex size-5 items-center justify-center rounded-full bg-rose-600 ring-2 ring-white shadow-xl">
                                <span class="size-2 rounded-full bg-white"></span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                el.innerHTML = `
                    <div class="flex size-3.5 items-center justify-center rounded-full bg-white ring-2 ring-sky-600 shadow-md transition hover:scale-125 hover:ring-sky-400">
                        <span class="size-1.5 rounded-full bg-sky-600"></span>
                    </div>
                `;
            }
            return el;
        }

        // Add layers (line & waypoints)
        function addRouteLayersToMap() {
            if (!mapInstance) return;
            if (sortedLocations.length === 0) {
                renderParkingMarkers();
                return;
            }

            const lineCoordinates = sortedLocations.map(p => [p.longitude, p.latitude]);

            // GeoJSON Source for Polyline
            const routeGeoJson = {
                type: 'Feature',
                properties: {},
                geometry: {
                    type: 'LineString',
                    coordinates: lineCoordinates
                }
            };

            if (mapInstance.getSource('route-line-source')) {
                mapInstance.getSource('route-line-source').setData(routeGeoJson);
            } else {
                mapInstance.addSource('route-line-source', {
                    type: 'geojson',
                    data: routeGeoJson
                });

                // Outer casing for high contrast and clear visibility
                mapInstance.addLayer({
                    id: 'route-line-casing',
                    type: 'line',
                    source: 'route-line-source',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#0f172a',
                        'line-width': 7,
                        'line-opacity': 0.85
                    }
                });

                // Core line
                mapInstance.addLayer({
                    id: 'route-line-core',
                    type: 'line',
                    source: 'route-line-source',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#0284c7',
                        'line-width': 4.5,
                        'line-opacity': 1.0
                    }
                });
            }

            // Add HTML Markers with smart overlap collision avoidance
            clearMarkers();

            const startPoint = sortedLocations[0];
            const latestPoint = sortedLocations[sortedLocations.length - 1];
            const isCircuitLoop = sortedLocations.length > 1 && getDistanceMeters(startPoint, latestPoint) < 35;

            sortedLocations.forEach((item, index) => {
                const isStart = index === 0;
                const isLatest = index === sortedLocations.length - 1;

                let markerType = 'waypoint';
                let badgeText = `Point #${index + 1}`;
                let badgeColor = 'bg-sky-600';

                if (isCircuitLoop) {
                    if (isStart) {
                        markerType = 'circuit';
                        badgeText = 'Start & Finish';
                        badgeColor = 'bg-gradient-to-r from-emerald-600 to-rose-600';
                    } else if (isLatest) {
                        // Skip redundant overlapping label on the same coordinate
                        markerType = 'waypoint';
                        badgeText = 'Latest Position';
                        badgeColor = 'bg-rose-600';
                    }
                } else {
                    if (isStart && isLatest) {
                        markerType = 'latest';
                        badgeText = 'Current Position';
                        badgeColor = 'bg-rose-600';
                    } else if (isStart) {
                        markerType = 'start';
                        badgeText = 'Start Position';
                        badgeColor = 'bg-emerald-600';
                    } else if (isLatest) {
                        markerType = 'latest';
                        badgeText = 'Latest Position';
                        badgeColor = 'bg-rose-600';
                    }
                }

                const markerEl = createHtmlMarkerElement(markerType);
                const popup = new maplibregl.Popup({ offset: [0, -10], closeButton: true })
                    .setHTML(buildPopupContent(item, badgeText, badgeColor));

                const marker = new maplibregl.Marker({ element: markerEl })
                    .setLngLat([item.longitude, item.latitude])
                    .setPopup(popup)
                    .addTo(mapInstance);

                markersList.push({ id: item.id, marker, popup, item });
            });

            renderParkingMarkers();
        }

        function renderParkingMarkers() {
            parkingMarkers.forEach(marker => marker.remove());
            parkingMarkers = [];
            if (!mapInstance) return;

            activeParkings.forEach(parking => {
                const location = parking.location || {};
                if (!Number.isFinite(Number(location.latitude)) || !Number.isFinite(Number(location.longitude))) return;

                const markerElement = document.createElement('div');
                markerElement.className = 'flex size-8 items-center justify-center rounded-full border-2 border-white bg-amber-500 text-xs font-bold text-slate-950 shadow-lg';
                markerElement.textContent = 'P';
                markerElement.title = `${parking.device_name} · parked / no GPS update`;

                const popup = new maplibregl.Popup({ offset: 16 }).setHTML(`
                    <div class="min-w-[210px] space-y-1 text-xs">
                        <p class="font-semibold text-slate-900">${escapeHtml(parking.device_name)} · Parked / no signal</p>
                        <p class="text-slate-600">Since ${escapeHtml(parking.started_at)}</p>
                        <p class="font-mono text-slate-700">${Number(location.latitude).toFixed(6)}, ${Number(location.longitude).toFixed(6)}</p>
                        <p class="text-slate-600">Last speed: ${location.speed_kmh !== null ? `${Number(location.speed_kmh).toFixed(1)} km/h` : '—'}</p>
                    </div>
                `);

                const marker = new maplibregl.Marker({ element: markerElement })
                    .setLngLat([Number(location.longitude), Number(location.latitude)])
                    .setPopup(popup)
                    .addTo(mapInstance);
                parkingMarkers.push(marker);
            });
        }

        function clearMarkers() {
            markersList.forEach(m => m.marker.remove());
            markersList = [];
            parkingMarkers.forEach(marker => marker.remove());
            parkingMarkers = [];
        }

        function applyLocationUpdates(payload) {
            if (payload.active_parkings) {
                activeParkings = payload.active_parkings;
                renderParkingMarkers();
            }
            if (!payload.data?.length) {
                return;
            }

            lastSeenLocationId = Math.max(lastSeenLocationId, payload.cursor || lastSeenLocationId);

            const newMapLocations = payload.data.filter(location =>
                location.latitude !== null && location.longitude !== null
            );

            if (newMapLocations.length > 0) {
                const knownIds = new Set(sortedLocations.map(location => location.id));
                newMapLocations.forEach(location => {
                    if (!knownIds.has(location.id)) {
                        sortedLocations.push(location);
                    }
                });
                sortedLocations.sort((a, b) => {
                    if (a.recorded_timestamp && b.recorded_timestamp) {
                        return a.recorded_timestamp - b.recorded_timestamp;
                    }
                    return a.id - b.id;
                });

                if (pointCountEl) {
                    pointCountEl.textContent = `${sortedLocations.length} positions`;
                }

                if (mapToolbarCountEl) {
                    mapToolbarCountEl.textContent = sortedLocations.length;
                }

                if (openModalBtn) {
                    openModalBtn.disabled = false;
                    openModalBtn.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                    openModalBtn.classList.add('bg-sky-600', 'text-black', 'hover:bg-sky-700', 'cursor-pointer', 'shadow-sky-600/20');
                    openModalBtn.title = 'Open the route map';
                }

                if (mapInstance?.isStyleLoaded()) {
                    addRouteLayersToMap();
                    updateMapStats();

                    const latestPoint = sortedLocations[sortedLocations.length - 1];
                    if (latestPoint && !mapInstance.getBounds().contains([latestPoint.longitude, latestPoint.latitude])) {
                        mapInstance.easeTo({
                            center: [latestPoint.longitude, latestPoint.latitude],
                            duration: 700,
                            essential: true,
                        });
                    }
                }
            }

            if (recordsBody && payload.rows) {
                recordsBody.querySelector('[data-empty-state]')?.remove();
                recordsBody.insertAdjacentHTML('afterbegin', payload.rows);

                const rows = recordsBody.querySelectorAll('[data-location-row]');
                rows.forEach((row, index) => {
                    if (index >= 50) {
                        row.remove();
                    }
                });

                if (pageCountEl) {
                    pageCountEl.textContent = new Intl.NumberFormat().format(
                        recordsBody.querySelectorAll('[data-location-row]').length
                    );
                }
            }

            if (totalCountEl && typeof payload.total === 'number') {
                totalCountEl.textContent = new Intl.NumberFormat().format(payload.total);
            }

            if (recordsSummaryEl && typeof payload.total === 'number' && payload.total > 0) {
                recordsSummaryEl.textContent = `Showing 1–${Math.min(payload.total, 50)} of ${new Intl.NumberFormat().format(payload.total)} records`;
            }
        }

        async function pollLocationUpdates() {
            if (!isFirstPage || isPollingLocations || document.hidden) {
                return;
            }

            isPollingLocations = true;
            try {
                const response = await fetch(`${updatesUrl}?after_id=${lastSeenLocationId}`, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                });

                if (response.ok) {
                    applyLocationUpdates(await response.json());
                }
            } catch (error) {
                // The next poll retries automatically when the connection returns.
            } finally {
                isPollingLocations = false;
            }
        }

        if (isFirstPage) {
            pollLocationUpdates();
            window.setInterval(pollLocationUpdates, 5000);
        }

        // Fit map bounds to encompass all positions with contextual zoom
        function fitAllCoordinates(maxZoom = 15.5) {
            if (!mapInstance || sortedLocations.length === 0) return;

            isAreaContextActive = false;
            if (areaContextLabel) areaContextLabel.textContent = 'Show town area';

            if (sortedLocations.length === 1) {
                mapInstance.flyTo({
                    center: [sortedLocations[0].longitude, sortedLocations[0].latitude],
                    zoom: 15,
                    essential: true
                });
                return;
            }

            const bounds = new maplibregl.LngLatBounds();
            sortedLocations.forEach(p => {
                bounds.extend([p.longitude, p.latitude]);
            });

            mapInstance.fitBounds(bounds, {
                padding: { top: 90, bottom: 80, left: 70, right: 70 },
                maxZoom: maxZoom,
                duration: 1000
            });
        }

        // Toggle wide town context view
        function toggleAreaContext() {
            if (!mapInstance || sortedLocations.length === 0) return;

            if (!isAreaContextActive) {
                // Zoom out to see surrounding towns, roads, and hospital/landmark context
                const centerPoint = sortedLocations[Math.floor(sortedLocations.length / 2)];
                mapInstance.flyTo({
                    center: [centerPoint.longitude, centerPoint.latitude],
                    zoom: 13.2,
                    duration: 1000,
                    essential: true
                });
                isAreaContextActive = true;
                if (areaContextLabel) areaContextLabel.textContent = 'Fit to route';
            } else {
                fitAllCoordinates();
            }
        }

        // Initialize MapLibre
        function initMap(initialFocusRecordId = null) {
            if (mapInstance) {
                mapInstance.resize();
                if (initialFocusRecordId) {
                    focusRecordOnMap(initialFocusRecordId);
                } else {
                    fitAllCoordinates();
                }
                return;
            }

            const initialCenter = sortedLocations.length > 0
                ? [sortedLocations[sortedLocations.length - 1].longitude, sortedLocations[sortedLocations.length - 1].latitude]
                : [74.0618, 34.5020];

            mapInstance = new maplibregl.Map({
                container: 'maplibre-canvas',
                style: mapStyles[currentStyleIndex].style,
                center: initialCenter,
                zoom: 14,
                attributionControl: false
            });

            // Add Controls
            mapInstance.addControl(new maplibregl.NavigationControl({ showCompass: true, showZoom: true }), 'top-left');
            mapInstance.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-right');
            mapInstance.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

            mapInstance.on('load', () => {
                addRouteLayersToMap();
                updateMapStats();
                if (initialFocusRecordId) {
                    focusRecordOnMap(initialFocusRecordId);
                } else {
                    fitAllCoordinates();
                }
                updateNearbyPlaces();
            });

            mapInstance.on('moveend', updateNearbyPlaces);
        }

        // Focus specific record on map
        function focusRecordOnMap(recordId) {
            const target = markersList.find(m => m.id == recordId);
            if (target && mapInstance) {
                mapInstance.flyTo({
                    center: [target.item.longitude, target.item.latitude],
                    zoom: 16,
                    essential: true
                });
                setTimeout(() => {
                    target.marker.togglePopup();
                }, 400);
            }
        }

        // Open Modal
        function openModal(focusRecordId = null) {
            if (sortedLocations.length === 0 && activeParkings.length === 0) {
                alert('No GPS coordinates available to display on the map.');
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            // Let the browser paint modal before sizing canvas
            setTimeout(() => {
                initMap(focusRecordId);
            }, 50);
        }

        // Close Modal
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');

            if (document.fullscreenElement) {
                document.exitFullscreen().catch(() => {});
            }
        }

        // Style Switcher
        function switchMapStyle() {
            if (!mapInstance) return;
            currentStyleIndex = (currentStyleIndex + 1) % mapStyles.length;
            const newStyle = mapStyles[currentStyleIndex];
            if (styleLabel) styleLabel.textContent = newStyle.name;

            mapInstance.setStyle(newStyle.style);
            mapInstance.once('style.load', () => {
                addRouteLayersToMap();
                renderParkingMarkers();
            });
        }

        // Fullscreen Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                modal.requestFullscreen().then(() => {
                    iconExpand.classList.add('hidden');
                    iconCollapse.classList.remove('hidden');
                }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => {
                    iconCollapse.classList.add('hidden');
                    iconExpand.classList.remove('hidden');
                }).catch(() => {});
            }
        }

        // Event Listeners
        if (openModalBtn) {
            openModalBtn.addEventListener('click', () => openModal());
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }

        if (recenterBtn) {
            recenterBtn.addEventListener('click', () => fitAllCoordinates());
        }

        if (toggleAreaContextBtn) {
            toggleAreaContextBtn.addEventListener('click', toggleAreaContext);
        }

        if (toggleStyleBtn) {
            toggleStyleBtn.addEventListener('click', switchMapStyle);
        }

        if (toggleFullscreenBtn) {
            toggleFullscreenBtn.addEventListener('click', toggleFullscreen);
        }

        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                iconExpand.classList.add('hidden');
                iconCollapse.classList.remove('hidden');
            } else {
                iconCollapse.classList.add('hidden');
                iconExpand.classList.remove('hidden');
            }
            if (mapInstance) {
                setTimeout(() => mapInstance.resize(), 100);
            }
        });

        // Delegate row actions so newly polled rows work without new listeners.
        recordsBody?.addEventListener('click', (event) => {
            const button = event.target.closest('.js-row-focus-btn');
            if (!button) {
                return;
            }

            event.stopPropagation();
            openModal(button.getAttribute('data-id'));
        });

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
