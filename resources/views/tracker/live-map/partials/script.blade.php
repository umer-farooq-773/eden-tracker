<script>
    document.addEventListener('DOMContentLoaded', () => {
        const devices = @json($mapDevices);
        const selectedDeviceId = @json($selectedDevice?->device_id);
        const routePoints = @json($mapLocations);
        let activeParkings = @json($mapParkings);
        let cursor = @json($locationCursor);

        const canvas = document.getElementById('fleet-map-canvas');
        const searchForm = document.getElementById('vehicle-filter-form');
        const searchInput = document.getElementById('vehicle-search');
        const dropdownToggle = document.getElementById('vehicle-dropdown-toggle');
        const deviceOptions = document.getElementById('tracker-device-options');
        const deviceIdInput = document.getElementById('vehicle-device-id');
        const searchError = document.getElementById('vehicle-search-error');
        const statusLabel = document.getElementById('fleet-status-label');
        const liveStatus = document.getElementById('fleet-live-status');
        const liveIndicator = document.getElementById('fleet-live-indicator');
        const mapTitle = document.getElementById('fleet-map-title');
        const mapSubtitle = document.getElementById('fleet-map-subtitle');
        const mapStyleButton = document.getElementById('fleet-map-style');
        const fitButton = document.getElementById('fleet-map-fit');
        if (!canvas || !window.maplibregl) return;

        const deviceStates = new Map(devices.map(device => [device.device_id, device]));
        let trackedRoute = [...routePoints].sort((a, b) => a.id - b.id);
        let map = null;
        let mapLoaded = false;
        let mapStyleIndex = 0;
        let isPolling = false;
        let lastPollFailed = null;
        let highlightedDeviceIndex = -1;
        const deviceMarkers = new Map();
        const parkingMarkers = new Map();
        let startMarker = null;
        let latestMarker = null;

        const mapStyles = [
            {
                name: 'Google Maps',
                style: {
                    version: 8,
                    sources: { base: { type: 'raster', tiles: ['https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', 'https://mt2.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', 'https://mt3.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'], tileSize: 256, attribution: '&copy; Google Maps' } },
                    layers: [{ id: 'base-layer', type: 'raster', source: 'base', minzoom: 0, maxzoom: 20 }]
                }
            },
            {
                name: 'Google Satellite',
                style: {
                    version: 8,
                    sources: { base: { type: 'raster', tiles: ['https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', 'https://mt2.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', 'https://mt3.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'], tileSize: 256, attribution: '&copy; Google Maps' } },
                    layers: [{ id: 'base-layer', type: 'raster', source: 'base', minzoom: 0, maxzoom: 20 }]
                }
            },
            {
                name: 'OpenStreetMap',
                style: {
                    version: 8,
                    sources: { base: { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '&copy; OpenStreetMap contributors' } },
                    layers: [{ id: 'base-layer', type: 'raster', source: 'base', minzoom: 0, maxzoom: 19 }]
                }
            },
            {
                name: 'Carto Voyager',
                style: {
                    version: 8,
                    sources: { base: { type: 'raster', tiles: ['https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png'], tileSize: 256, attribution: '&copy; OpenStreetMap &copy; CARTO' } },
                    layers: [{ id: 'base-layer', type: 'raster', source: 'base', minzoom: 0, maxzoom: 19 }]
                }
            }
        ];

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[character]));
        }

        function validLocation(location) {
            return location
                && location.latitude !== null
                && location.latitude !== undefined
                && location.longitude !== null
                && location.longitude !== undefined
                && Number.isFinite(Number(location.latitude))
                && Number.isFinite(Number(location.longitude));
        }

        function currentLocationCount() {
            return [...deviceStates.values()].filter(device => validLocation(device.location)).length;
        }

        function isDeviceOnline(device) {
            const lastSeen = device.last_seen_at ? Date.parse(device.last_seen_at) : NaN;
            return Number.isFinite(lastSeen) && Date.now() - lastSeen <= 5 * 60 * 1000;
        }

        function closeDeviceOptions() {
            deviceOptions.classList.add('hidden');
            searchInput.setAttribute('aria-expanded', 'false');
            searchInput.removeAttribute('aria-activedescendant');
            highlightedDeviceIndex = -1;
        }

        function selectDevice(device) {
            searchInput.value = device.device_id;
            deviceIdInput.value = device.device_id;
            searchError.classList.add('hidden');
            closeDeviceOptions();
        }

        function renderDeviceOptions(filter = searchInput.value) {
            const normalizedFilter = filter.trim().toLowerCase();
            const matches = [...deviceStates.values()]
                .filter(device => `${device.device_id} ${device.name}`.toLowerCase().includes(normalizedFilter))
                .sort((first, second) => first.device_id.localeCompare(second.device_id));

            highlightedDeviceIndex = -1;
            deviceOptions.replaceChildren();

            if (!matches.length) {
                const emptyState = document.createElement('p');
                emptyState.className = 'px-4 py-3 text-sm text-slate-500';
                emptyState.textContent = 'No vehicles found.';
                deviceOptions.append(emptyState);
            } else {
                matches.forEach((device, index) => {
                    const online = isDeviceOnline(device);
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.id = `tracker-device-option-${index}`;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.className = 'flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5 text-left transition last:border-b-0 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none';

                    const identity = document.createElement('span');
                    identity.className = 'flex min-w-0 items-center gap-3';

                    const indicator = document.createElement('span');
                    indicator.className = `size-2.5 shrink-0 rounded-full ${online ? 'bg-emerald-500' : 'bg-slate-400'}`;
                    indicator.setAttribute('aria-hidden', 'true');

                    const label = document.createElement('span');
                    label.className = 'truncate text-sm font-medium text-slate-800';
                    label.textContent = device.device_id;
                    label.title = device.name;
                    identity.append(indicator, label);

                    const status = document.createElement('span');
                    status.className = `shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ${online ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`;
                    status.textContent = online ? 'ONLINE' : 'OFFLINE';

                    option.append(identity, status);
                    option.addEventListener('click', () => selectDevice(device));
                    deviceOptions.append(option);
                });
            }

            deviceOptions.classList.remove('hidden');
            searchInput.setAttribute('aria-expanded', 'true');
        }

        function setConnectionState(isOnline) {
            if (lastPollFailed === !isOnline) return;
            lastPollFailed = !isOnline;
            liveStatus.textContent = isOnline ? 'Live · updates every 5 seconds' : 'Reconnecting to tracker…';
            liveIndicator.classList.toggle('bg-emerald-500', isOnline);
            liveIndicator.classList.toggle('bg-rose-500', !isOnline);
        }

        function makeVehicleMarker(device, location, isLatest = false) {
            const element = document.createElement('button');
            element.type = 'button';
            element.title = `${device.name} (${device.device_id})`;
            element.className = `relative flex size-10 items-center justify-center rounded-full border-2 border-white ${isLatest ? 'bg-rose-600' : 'bg-indigo-600'} text-lg text-white shadow-lg ring-1 ring-slate-900/10 transition hover:scale-110`;
            element.textContent = '🚚';

            const popup = new maplibregl.Popup({ offset: 20, closeButton: true }).setHTML(`
                <div class="min-w-[210px] space-y-1.5 text-xs">
                    <p class="font-semibold text-white">${escapeHtml(device.name)}</p>
                    <p class="font-mono text-slate-400">${escapeHtml(device.device_id)}</p>
                    <p class="text-slate-300">Position: ${Number(location.latitude).toFixed(6)}, ${Number(location.longitude).toFixed(6)}</p>
                    <p class="text-slate-300">Speed: ${location.speed_kmh !== null ? `${Number(location.speed_kmh).toFixed(1)} km/h` : '—'}</p>
                    <p class="text-slate-400">Updated: ${escapeHtml(location.recorded_at || '—')}</p>
                </div>
            `);

            const marker = new maplibregl.Marker({ element })
                .setLngLat([Number(location.longitude), Number(location.latitude)])
                .setPopup(popup)
                .addTo(map);

            if (!selectedDeviceId) {
                element.addEventListener('dblclick', () => {
                    window.location.href = `${@json(route('tracker.live-map'))}?device_id=${encodeURIComponent(device.device_id)}`;
                });
            }

            return marker;
        }

        function makeParkingMarker(parking) {
            const location = parking.location || {};
            if (!validLocation(location)) return null;

            const element = document.createElement('div');
            element.className = 'flex size-8 items-center justify-center rounded-full border-2 border-white bg-amber-500 text-xs font-bold text-slate-950 shadow-lg';
            element.textContent = 'P';
            element.title = `${parking.device_name} · parked / no signal`;
            const popup = new maplibregl.Popup({ offset: 16 }).setHTML(`
                <div class="min-w-[200px] space-y-1 text-xs">
                    <p class="font-semibold text-white">${escapeHtml(parking.device_name)} · Parked / no signal</p>
                    <p class="text-slate-300">Since ${escapeHtml(parking.started_at)}</p>
                    <p class="font-mono text-slate-400">${Number(location.latitude).toFixed(6)}, ${Number(location.longitude).toFixed(6)}</p>
                </div>
            `);

            return new maplibregl.Marker({ element })
                .setLngLat([Number(location.longitude), Number(location.latitude)])
                .setPopup(popup)
                .addTo(map);
        }

        function featureForRoute() {
            const coordinates = trackedRoute.filter(validLocation)
                .map(point => [Number(point.longitude), Number(point.latitude)]);

            if (coordinates.length < 2) {
                return { type: 'FeatureCollection', features: [] };
            }

            return {
                type: 'FeatureCollection',
                features: [{
                    type: 'Feature',
                    properties: {},
                    geometry: { type: 'LineString', coordinates }
                }]
            };
        }

        function syncRoute() {
            if (!selectedDeviceId || !mapLoaded) return;
            const source = map.getSource('vehicle-route');
            if (source) source.setData(featureForRoute());

            const points = trackedRoute.filter(validLocation);
            if (!points.length) return;

            startMarker?.remove();
            latestMarker?.remove();
            const selectedDevice = deviceStates.get(selectedDeviceId) || { name: 'Vehicle', device_id: selectedDeviceId };
            const first = points[0];
            const latest = points[points.length - 1];
            const startElement = document.createElement('div');
            startElement.className = 'flex size-7 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-[10px] font-bold text-white shadow-lg';
            startElement.textContent = 'S';
            startMarker = new maplibregl.Marker({ element: startElement }).setLngLat([Number(first.longitude), Number(first.latitude)]).addTo(map);

            latestMarker = makeVehicleMarker(selectedDevice, latest, true);
        }

        function syncAllVehicleMarkers() {
            if (selectedDeviceId || !mapLoaded) return;
            const activeIds = new Set();

            deviceStates.forEach(device => {
                if (!validLocation(device.location)) return;
                activeIds.add(device.device_id);
                const current = deviceMarkers.get(device.device_id);
                if (current?.locationId === device.location.id) return;

                current?.marker.remove();
                deviceMarkers.set(device.device_id, {
                    locationId: device.location.id,
                    marker: makeVehicleMarker(device, device.location),
                });
            });

            deviceMarkers.forEach((entry, deviceId) => {
                if (!activeIds.has(deviceId)) {
                    entry.marker.remove();
                    deviceMarkers.delete(deviceId);
                }
            });
        }

        function syncParkingMarkers() {
            if (!mapLoaded) return;
            const filteredParkings = selectedDeviceId
                ? activeParkings.filter(parking => parking.device_id === selectedDeviceId)
                : activeParkings;
            const activeIds = new Set(filteredParkings.map(parking => parking.id));

            parkingMarkers.forEach((marker, parkingId) => {
                if (!activeIds.has(parkingId)) {
                    marker.remove();
                    parkingMarkers.delete(parkingId);
                }
            });

            filteredParkings.forEach(parking => {
                if (parkingMarkers.has(parking.id)) return;
                const marker = makeParkingMarker(parking);
                if (marker) parkingMarkers.set(parking.id, marker);
            });
        }

        function fitVehicles() {
            if (!map) return;
            const coordinates = selectedDeviceId
                ? trackedRoute.filter(validLocation).map(location => [Number(location.longitude), Number(location.latitude)])
                : [...deviceStates.values()].filter(device => validLocation(device.location)).map(device => [Number(device.location.longitude), Number(device.location.latitude)]);
            activeParkings.filter(parking => !selectedDeviceId || parking.device_id === selectedDeviceId)
                .filter(parking => validLocation(parking.location))
                .forEach(parking => coordinates.push([Number(parking.location.longitude), Number(parking.location.latitude)]));

            if (coordinates.length === 1) {
                map.flyTo({ center: coordinates[0], zoom: 15, essential: true });
                return;
            }
            if (coordinates.length > 1) {
                const bounds = new maplibregl.LngLatBounds();
                coordinates.forEach(coordinate => bounds.extend(coordinate));
                map.fitBounds(bounds, { padding: 70, maxZoom: 15, duration: 800 });
            }
        }

        function updateSummary() {
            const currentCount = currentLocationCount();
            statusLabel.textContent = selectedDeviceId ? 'Tracking selected vehicle' : `Tracking ${currentCount} vehicles`;
            if (selectedDeviceId) {
                const device = deviceStates.get(selectedDeviceId);
                mapTitle.textContent = device?.name || 'Selected vehicle';
                mapSubtitle.textContent = `${selectedDeviceId} · ${trackedRoute.length} route positions`;
            } else {
                mapTitle.textContent = 'All vehicles';
                mapSubtitle.textContent = `Latest location for ${currentCount} active vehicle${currentCount === 1 ? '' : 's'}`;
            }
        }

        function initMap() {
            const selected = selectedDeviceId ? deviceStates.get(selectedDeviceId) : null;
            const selectedLocation = selected?.location;
            const allLocations = [...deviceStates.values()].map(device => device.location).filter(validLocation);
            const firstLocation = selectedLocation || trackedRoute[trackedRoute.length - 1] || allLocations[0];
            const center = validLocation(firstLocation)
                ? [Number(firstLocation.longitude), Number(firstLocation.latitude)]
                : [74.0618, 34.5020];

            map = new maplibregl.Map({
                container: 'fleet-map-canvas',
                style: mapStyles[mapStyleIndex].style,
                center,
                zoom: selectedLocation ? 14 : 5,
                attributionControl: false
            });
            map.addControl(new maplibregl.NavigationControl({ showCompass: true, showZoom: true }), 'top-right');
            map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-left');
            map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

            map.on('load', () => {
                mapLoaded = true;
                if (selectedDeviceId) {
                    map.addSource('vehicle-route', { type: 'geojson', data: featureForRoute() });
                    map.addLayer({
                        id: 'vehicle-route-line',
                        type: 'line',
                        source: 'vehicle-route',
                        layout: { 'line-join': 'round', 'line-cap': 'round' },
                        paint: { 'line-color': '#0284c7', 'line-width': 5, 'line-opacity': 0.95 }
                    });
                    syncRoute();
                } else {
                    syncAllVehicleMarkers();
                }
                syncParkingMarkers();
                updateSummary();
                fitVehicles();
            });
        }

        async function pollLocations() {
            if (isPolling) return;
            isPolling = true;
            try {
                const query = new URLSearchParams({ after_id: String(cursor) });
                const response = await fetch(`${@json(route('tracker.locations.updates'))}?${query}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Tracker update request failed.');
                const payload = await response.json();
                cursor = Math.max(cursor, Number(payload.cursor || cursor));
                setConnectionState(true);

                (payload.data || []).forEach(location => {
                    const device = deviceStates.get(location.device_id) || {
                        id: null,
                        device_id: location.device_id,
                        name: location.device_name,
                        last_seen_at: null,
                        location: null
                    };
                    device.location = location;
                    device.last_seen_at = new Date().toISOString();
                    deviceStates.set(location.device_id, device);

                    if (selectedDeviceId === location.device_id && location.latitude !== null && location.longitude !== null) {
                        if (!trackedRoute.some(point => point.id === location.id)) trackedRoute.push(location);
                    }
                });

                if (Array.isArray(payload.active_parkings)) activeParkings = payload.active_parkings;
                if (selectedDeviceId) syncRoute();
                else syncAllVehicleMarkers();
                syncParkingMarkers();
                updateSummary();
                if (!deviceOptions.classList.contains('hidden')) renderDeviceOptions();
            } catch (error) {
                setConnectionState(false);
            } finally {
                isPolling = false;
            }
        }

        searchForm?.addEventListener('submit', event => {
            const searchValue = searchInput.value.trim().toLowerCase();
            if (!searchValue) {
                deviceIdInput.value = '';
                searchError.classList.add('hidden');
                closeDeviceOptions();
                return;
            }

            const device = devices.find(candidate =>
                candidate.device_id.toLowerCase() === searchValue
                || `${candidate.name} (${candidate.device_id})`.toLowerCase() === searchValue
                || candidate.name.toLowerCase() === searchValue
            ) || devices.find(candidate => `${candidate.name} ${candidate.device_id}`.toLowerCase().includes(searchValue));

            if (!device) {
                event.preventDefault();
                searchError.classList.remove('hidden');
                renderDeviceOptions(searchInput.value);
                return;
            }

            searchError.classList.add('hidden');
            deviceIdInput.value = device.device_id;
            closeDeviceOptions();
        });
        searchInput?.addEventListener('focus', () => renderDeviceOptions());
        searchInput?.addEventListener('input', () => {
            searchError.classList.add('hidden');
            renderDeviceOptions();
        });
        searchInput?.addEventListener('keydown', event => {
            const options = [...deviceOptions.querySelectorAll('[role="option"]')];
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                if (deviceOptions.classList.contains('hidden') || !options.length) {
                    renderDeviceOptions();
                    return;
                }

                event.preventDefault();
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                highlightedDeviceIndex = highlightedDeviceIndex < 0
                    ? (direction > 0 ? 0 : options.length - 1)
                    : (highlightedDeviceIndex + direction + options.length) % options.length;
                options.forEach((option, index) => {
                    const isHighlighted = index === highlightedDeviceIndex;
                    option.setAttribute('aria-selected', String(isHighlighted));
                    option.classList.toggle('bg-slate-50', isHighlighted);
                });
                searchInput.setAttribute('aria-activedescendant', options[highlightedDeviceIndex].id);
                options[highlightedDeviceIndex].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter' && !deviceOptions.classList.contains('hidden') && highlightedDeviceIndex >= 0) {
                event.preventDefault();
                const selectedOption = options[highlightedDeviceIndex];
                const selectedDevice = deviceStates.get(selectedOption.querySelector('span.truncate')?.textContent);
                if (selectedDevice) selectDevice(selectedDevice);
            } else if (event.key === 'Escape') {
                closeDeviceOptions();
            }
        });
        dropdownToggle?.addEventListener('click', () => {
            if (deviceOptions.classList.contains('hidden')) {
                searchInput.focus();
                renderDeviceOptions('');
            } else {
                closeDeviceOptions();
            }
        });
        document.addEventListener('click', event => {
            if (!searchForm.contains(event.target)) closeDeviceOptions();
        });

        mapStyleButton?.addEventListener('click', () => {
            if (!map) return;
            mapStyleIndex = (mapStyleIndex + 1) % mapStyles.length;
            mapStyleButton.textContent = `Map style: ${mapStyles[mapStyleIndex].name}`;
            map.setStyle(mapStyles[mapStyleIndex].style);
            map.once('style.load', () => {
                if (selectedDeviceId) {
                    map.addSource('vehicle-route', { type: 'geojson', data: featureForRoute() });
                    map.addLayer({
                        id: 'vehicle-route-line',
                        type: 'line',
                        source: 'vehicle-route',
                        layout: { 'line-join': 'round', 'line-cap': 'round' },
                        paint: { 'line-color': '#0284c7', 'line-width': 5, 'line-opacity': 0.95 }
                    });
                    syncRoute();
                } else {
                    syncAllVehicleMarkers();
                }
                syncParkingMarkers();
            });
        });
        fitButton?.addEventListener('click', fitVehicles);

        initMap();
        updateSummary();
        pollLocations();
        window.setInterval(pollLocations, 5000);
    });
</script>
