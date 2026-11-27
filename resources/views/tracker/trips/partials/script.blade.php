<script>
document.addEventListener('DOMContentLoaded', () => {
    // ─── Data ────────────────────────────────────────────────────────────────
    const gpsPoints = @json($gpsPoints);
    const trips     = @json($trips);
    const stops     = @json($stops);

    // ─── DOM Refs ─────────────────────────────────────────────────────────────
    const mapWrapper         = document.getElementById('trip-map-wrapper');
    const btnMapFit          = document.getElementById('btn-map-fit');
    const btnToggleTelemetry = document.getElementById('btn-toggle-telemetry');
    const btnMapStyleToggle  = document.getElementById('btn-map-style-toggle');
    const mapStyleLabel      = document.getElementById('trip-map-style-label');
    const telemetryCard      = document.getElementById('telemetry-card');
    const telemetryBody      = document.getElementById('telemetry-body');
    const telemetryCollapseBtn = document.getElementById('telemetry-collapse-btn');
    const telemetryCollapseIcon= document.getElementById('telemetry-collapse-icon');
    const telemetryCloseBtn  = document.getElementById('telemetry-close-btn');
    const statusPillText     = document.getElementById('trip-status-text');

    // Telemetry value elements
    const elSpeed    = document.getElementById('telemetry-speed');
    const elCourse   = document.getElementById('telemetry-course');
    const elCourseArrow = document.getElementById('telemetry-heading-arrow');
    const elAltitude = document.getElementById('telemetry-altitude');
    const elSats     = document.getElementById('telemetry-satellites');
    const elBadge    = document.getElementById('telemetry-status-badge');
    const elTS       = document.getElementById('telemetry-timestamp');

    // Player elements
    const btnPlay    = document.getElementById('btn-player-toggle');
    const playState  = document.getElementById('play-state');
    const pauseState = document.getElementById('pause-state');
    const btnPrev    = document.getElementById('btn-player-prev');
    const btnNext    = document.getElementById('btn-player-next');
    const btnStart   = document.getElementById('btn-player-start');
    const btnEnd     = document.getElementById('btn-player-end');
    const slider     = document.getElementById('player-timeline-slider');
    const elTime     = document.getElementById('player-current-time');
    const elDate     = document.getElementById('player-current-date');
    const elElapsed  = document.getElementById('player-time-elapsed');
    const speedBtns  = document.querySelectorAll('.player-speed-btn');

    // Toolbar
    const selColor      = document.getElementById('route-color-mode');
    const selThick      = document.getElementById('route-thickness');
    const togAnnot      = document.getElementById('toggle-annotations');
    const btnFullscreen = document.getElementById('btn-app-fullscreen');

    // Tabs
    const tabBtnTrips = document.getElementById('tab-btn-trips');
    const tabBtnStops = document.getElementById('tab-btn-stops');
    const tabTrips    = document.getElementById('tab-content-trips');
    const tabStops    = document.getElementById('tab-content-stops');

    // Filter form
    const presetBtns  = document.querySelectorAll('.preset-btn');
    const presetInput = document.getElementById('preset-input');
    const filterForm  = document.getElementById('trip-filter-form');

    // ─── State ────────────────────────────────────────────────────────────────
    let map          = null;
    let styleIndex   = 0;
    let isPlaying    = false;
    let playSpeed    = 1;
    let currentIndex = 0;
    let animTimer    = null;
    let vehicleMarker= null;
    let annMarkers   = [];
    let telemetryCollapsed = false;

    // ─── Map Styles ───────────────────────────────────────────────────────────
    const mapStyles = [
        {
            name: 'OpenStreetMap',
            style: { version: 8, sources: { base: { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenStreetMap contributors' } }, layers: [{ id: 'base', type: 'raster', source: 'base' }] }
        },
        {
            name: 'Google Maps',
            style: { version: 8, sources: { base: { type: 'raster', tiles: ['https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'], tileSize: 256, attribution: '© Google Maps' } }, layers: [{ id: 'base', type: 'raster', source: 'base' }] }
        },
        {
            name: 'Google Satellite',
            style: { version: 8, sources: { base: { type: 'raster', tiles: ['https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'], tileSize: 256, attribution: '© Google Maps' } }, layers: [{ id: 'base', type: 'raster', source: 'base' }] }
        },
        {
            name: 'Carto Voyager',
            style: { version: 8, sources: { base: { type: 'raster', tiles: ['https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenStreetMap © CARTO' } }, layers: [{ id: 'base', type: 'raster', source: 'base' }] }
        }
    ];

    // ─── Map Init ─────────────────────────────────────────────────────────────
    if (!window.maplibregl) return;

    const initCenter = gpsPoints.length > 0 ? [gpsPoints[0].lng, gpsPoints[0].lat] : [73.077, 33.638];

    map = new maplibregl.Map({
        container: 'trip-map-canvas',
        style: mapStyles[styleIndex].style,
        center: initCenter,
        zoom: gpsPoints.length > 0 ? 13 : 5,
        attributionControl: false
    });

    map.addControl(new maplibregl.NavigationControl({ showCompass: true, showZoom: true }), 'bottom-left');
    map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

    map.on('load', () => {
        renderRoutes();
        createVehicleMarker();
        renderAnnotations();
        fitBounds();
        applyTelemetry(currentIndex);
    });

    window.addEventListener('resize', () => map?.resize());

    // ─── Route Rendering ──────────────────────────────────────────────────────
    function renderRoutes() {
        if (!map) return;
        const thickness = parseInt(selThick?.value || '7', 10);
        const colorMode = selColor?.value || 'by_trips';

        // Clear old layers
        for (let i = 0; i < 20; i++) {
            ['casing', 'core'].forEach(t => { if (map.getLayer(`trip-${t}-${i}`)) map.removeLayer(`trip-${t}-${i}`); });
            if (map.getSource(`trip-src-${i}`)) map.removeSource(`trip-src-${i}`);
        }
        ['single-casing', 'single-core'].forEach(id => { if (map.getLayer(id)) map.removeLayer(id); });
        if (map.getSource('single-src')) map.removeSource('single-src');

        if (colorMode === 'by_trips' && trips.length > 0) {
            trips.forEach((trip, idx) => {
                const coords = (trip.points && trip.points.length > 0)
                    ? trip.points.map(p => [p.lng, p.lat])
                    : gpsPoints.map(p => [p.lng, p.lat]);
                if (coords.length < 2) return;

                map.addSource(`trip-src-${idx}`, { type: 'geojson', data: { type: 'Feature', geometry: { type: 'LineString', coordinates: coords } } });
                map.addLayer({ id: `trip-casing-${idx}`, type: 'line', source: `trip-src-${idx}`, layout: { 'line-join': 'round', 'line-cap': 'round' }, paint: { 'line-color': '#1e293b', 'line-width': thickness + 3, 'line-opacity': 0.55 } });
                map.addLayer({ id: `trip-core-${idx}`, type: 'line', source: `trip-src-${idx}`, layout: { 'line-join': 'round', 'line-cap': 'round' }, paint: { 'line-color': trip.color || '#2563eb', 'line-width': thickness, 'line-opacity': 1 } });
            });
        } else {
            if (gpsPoints.length < 2) return;
            const coords = gpsPoints.map(p => [p.lng, p.lat]);
            const color  = colorMode === 'by_speed' ? '#0ea5e9' : '#2563eb';
            map.addSource('single-src', { type: 'geojson', data: { type: 'Feature', geometry: { type: 'LineString', coordinates: coords } } });
            map.addLayer({ id: 'single-casing', type: 'line', source: 'single-src', layout: { 'line-join': 'round', 'line-cap': 'round' }, paint: { 'line-color': '#1e293b', 'line-width': thickness + 3, 'line-opacity': 0.55 } });
            map.addLayer({ id: 'single-core', type: 'line', source: 'single-src', layout: { 'line-join': 'round', 'line-cap': 'round' }, paint: { 'line-color': color, 'line-width': thickness, 'line-opacity': 1 } });
        }
    }

    // ─── Vehicle Marker ───────────────────────────────────────────────────────
    function createVehicleMarker() {
        if (!map || gpsPoints.length === 0) return;
        const el = document.createElement('div');
        el.innerHTML = `
            <div class="relative flex size-9 items-center justify-center rounded-full bg-blue-600 shadow-xl ring-2 ring-white">
                <i class="fa-solid fa-truck text-xs text-white"></i>
                <div id="marker-heading-ind" class="absolute -top-2 text-[11px] text-blue-500 font-black" style="transform-origin:center 110%;">▲</div>
            </div>`;
        vehicleMarker = new maplibregl.Marker({ element: el, anchor: 'center' })
            .setLngLat([gpsPoints[0].lng, gpsPoints[0].lat])
            .addTo(map);
    }

    // ─── Annotations ──────────────────────────────────────────────────────────
    function renderAnnotations() {
        annMarkers.forEach(m => m.remove());
        annMarkers = [];

        if (!togAnnot?.checked || gpsPoints.length === 0) return;

        // Track Start
        addLabelMarker(gpsPoints[0], 'Track Start', '#16a34a');

        // Track End
        if (gpsPoints.length > 1) addLabelMarker(gpsPoints[gpsPoints.length - 1], 'Track End', '#dc2626');

        // Vehicle Stops
        stops.forEach(stop => {
            if (!stop.lat || !stop.lng) return;
            const el = document.createElement('div');
            el.className = 'custom-stop-marker';
            el.innerHTML = `
                <div class="flex cursor-pointer items-center gap-1.5 rounded-full border-2 border-amber-600 bg-amber-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-lg">
                    <i class="fa-solid fa-square-parking text-[10px]"></i>
                    <span>Vehicle Stop (${stop.duration_formatted})</span>
                </div>`;
            const popup = new maplibregl.Popup({ offset: [0, -8], closeOnClick: true }).setHTML(`
                <div class="p-1 text-xs">
                    <p class="font-bold text-amber-400">${stop.name} — ${stop.duration_formatted}</p>
                    <p class="mt-1 text-slate-300">${stop.time_range}</p>
                    <p class="mt-1 font-mono text-slate-400">📍 ${stop.coords_formatted}</p>
                </div>`);
            const m = new maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([stop.lng, stop.lat])
                .setPopup(popup)
                .addTo(map);
            annMarkers.push(m);
        });

        // Max Speed
        let maxPt = gpsPoints[0];
        gpsPoints.forEach(p => { if (p.speed > maxPt.speed) maxPt = p; });
        if (maxPt && maxPt.speed > 5) {
            const el = document.createElement('div');
            el.innerHTML = `
                <div class="flex items-center gap-1 rounded-full bg-rose-600 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-lg">
                    <i class="fa-solid fa-bolt text-[10px]"></i>
                    <span>Max Speed: ${Math.round(maxPt.speed)} km/h</span>
                </div>`;
            annMarkers.push(new maplibregl.Marker({ element: el, anchor: 'bottom' }).setLngLat([maxPt.lng, maxPt.lat]).addTo(map));
        }

        // Trip start labels (by_trips mode only, 2+ trips)
        if (selColor?.value === 'by_trips' && trips.length > 1) {
            trips.forEach(trip => {
                if (!trip.points || trip.points.length === 0) return;
                const p0 = trip.points[0];
                const el = document.createElement('div');
                el.innerHTML = `<div class="rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow" style="background:${trip.color}">${trip.name} Start</div>`;
                annMarkers.push(new maplibregl.Marker({ element: el, anchor: 'bottom' }).setLngLat([p0.lng, p0.lat]).addTo(map));
            });
        }
    }

    function addLabelMarker(pt, label, color) {
        const el = document.createElement('div');
        el.innerHTML = `
            <div class="flex flex-col items-center">
                <div class="rounded-full px-2.5 py-0.5 text-[11px] font-bold text-white shadow-lg" style="background:${color};">${label}</div>
                <div class="mt-0.5 size-3 rounded-full border-2 border-white shadow" style="background:${color};"></div>
            </div>`;
        annMarkers.push(new maplibregl.Marker({ element: el, anchor: 'bottom' }).setLngLat([pt.lng, pt.lat]).addTo(map));
    }

    // ─── Fit Bounds ───────────────────────────────────────────────────────────
    function fitBounds(pts) {
        const points = pts || gpsPoints;
        if (!map || points.length === 0) return;
        const bounds = new maplibregl.LngLatBounds();
        points.forEach(p => bounds.extend([p.lng, p.lat]));
        map.fitBounds(bounds, { padding: { top: 80, bottom: 60, left: 40, right: 300 }, maxZoom: 16, duration: 800 });
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function hhmmss(seconds) {
        seconds = Math.max(0, Math.round(seconds));
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        return `${m}:${String(s).padStart(2,'0')}`;
    }

    // ─── Telemetry Update ─────────────────────────────────────────────────────
    function applyTelemetry(index) {
        if (index < 0 || index >= gpsPoints.length) return;
        const pt = gpsPoints[index];

        if (elSpeed)      elSpeed.textContent    = Math.round(pt.speed);
        if (elCourse)     elCourse.textContent   = `${Math.round(pt.course)}°`;
        if (elCourseArrow) elCourseArrow.style.transform = `rotate(${pt.course}deg)`;
        if (elAltitude)   elAltitude.textContent = Math.round(pt.altitude);
        if (elSats)       elSats.textContent     = pt.satellites;
        if (elTS)         elTS.textContent       = pt.time_formatted;

        if (elBadge) {
            if (pt.speed > 2) {
                elBadge.className = 'rounded-full bg-emerald-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-400 ring-1 ring-inset ring-emerald-700';
                elBadge.textContent = `MOVING (${Math.round(pt.speed)} km/h)`;
            } else {
                elBadge.className = 'rounded-full bg-amber-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-400 ring-1 ring-inset ring-amber-700';
                elBadge.textContent = 'STOPPED';
            }
        }

        // Player clock
        if (elTime) elTime.textContent = pt.time_short;
        if (elDate) elDate.textContent = pt.time_formatted.split('·')[0]?.trim() ?? '';

        // Elapsed
        if (elElapsed && gpsPoints.length > 1) {
            const elapsedSec = pt.timestamp - gpsPoints[0].timestamp;
            if (elElapsed) elElapsed.textContent = hhmmss(elapsedSec);
        }

        // Slider sync
        if (slider && gpsPoints.length > 1) {
            slider.value = ((index / (gpsPoints.length - 1)) * 100).toFixed(2);
        }

        // Vehicle marker
        if (vehicleMarker) {
            vehicleMarker.setLngLat([pt.lng, pt.lat]);
            const ind = document.getElementById('marker-heading-ind');
            if (ind) ind.style.transform = `rotate(${pt.course}deg)`;
        }
    }

    // ─── Seek ─────────────────────────────────────────────────────────────────
    function seekTo(index, pan = true) {
        if (gpsPoints.length === 0) return;
        currentIndex = Math.max(0, Math.min(index, gpsPoints.length - 1));
        applyTelemetry(currentIndex);
        if (pan && map) {
            const pt = gpsPoints[currentIndex];
            if (!map.getBounds().contains([pt.lng, pt.lat])) map.panTo([pt.lng, pt.lat]);
        }
    }

    // ─── Playback ─────────────────────────────────────────────────────────────
    function setPlayingState(playing) {
        isPlaying = playing;
        if (btnPlay) btnPlay.setAttribute('data-playing', playing ? 'true' : 'false');
        if (statusPillText) {
            statusPillText.textContent = playing
                ? `Playing (${playSpeed}x)`
                : `${gpsPoints.length} GPS Points Loaded`;
        }
    }

    function startPlay() {
        if (isPlaying || gpsPoints.length <= 1) return;
        setPlayingState(true);
        const ms = Math.max(30, 1000 / playSpeed);
        animTimer = setInterval(() => {
            if (currentIndex >= gpsPoints.length - 1) { stopPlay(); return; }
            seekTo(currentIndex + 1);
        }, ms);
    }

    function stopPlay() {
        clearInterval(animTimer);
        animTimer = null;
        setPlayingState(false);
    }

    function togglePlay() {
        if (isPlaying) stopPlay();
        else { if (currentIndex >= gpsPoints.length - 1) currentIndex = 0; startPlay(); }
    }

    // ─── Player Events ────────────────────────────────────────────────────────
    btnPlay?.addEventListener('click', togglePlay);
    btnPrev?.addEventListener('click', () => { stopPlay(); seekTo(currentIndex - 1); });
    btnNext?.addEventListener('click', () => { stopPlay(); seekTo(currentIndex + 1); });
    btnStart?.addEventListener('click', () => { stopPlay(); seekTo(0); });
    btnEnd?.addEventListener('click',   () => { stopPlay(); seekTo(gpsPoints.length - 1); });

    slider?.addEventListener('input', (e) => {
        stopPlay();
        seekTo(Math.round((parseFloat(e.target.value) / 100) * (gpsPoints.length - 1)));
    });

    speedBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            speedBtns.forEach(b => b.className = 'player-speed-btn rounded-md px-2 py-1 transition text-slate-500 hover:text-slate-800');
            btn.className = 'player-speed-btn rounded-md bg-blue-600 px-2 py-1 text-white shadow-sm';
            playSpeed = parseFloat(btn.dataset.speed || '1');
            if (isPlaying) { stopPlay(); startPlay(); }
        });
    });

    // ─── Telemetry: Draggable, Collapsible, Closeable ─────────────────────────
    if (telemetryCard) {
        // ── Draggable ──
        const dragHandle = document.getElementById('telemetry-drag-handle');
        let dragging = false, dragX = 0, dragY = 0, startLeft = 0, startTop = 0;

        // Convert from right-positioned to left-positioned for free drag
        function convertToAbsolute() {
            if (telemetryCard.style.left) return; // already converted
            const mapCanvas = document.getElementById('trip-map-canvas');
            const parentRect = telemetryCard.parentElement.getBoundingClientRect();
            const cardRect = telemetryCard.getBoundingClientRect();
            telemetryCard.style.right = 'auto';
            telemetryCard.style.left = `${cardRect.left - parentRect.left}px`;
            telemetryCard.style.top  = `${cardRect.top  - parentRect.top}px`;
        }

        dragHandle?.addEventListener('mousedown', (e) => {
            convertToAbsolute();
            dragging = true;
            dragX = e.clientX;
            dragY = e.clientY;
            startLeft = parseInt(telemetryCard.style.left, 10) || 0;
            startTop  = parseInt(telemetryCard.style.top,  10) || 0;
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!dragging) return;
            const dx = e.clientX - dragX;
            const dy = e.clientY - dragY;
            const parent = telemetryCard.parentElement;
            const maxX = parent.clientWidth  - telemetryCard.offsetWidth;
            const maxY = parent.clientHeight - telemetryCard.offsetHeight;
            telemetryCard.style.left = `${Math.max(0, Math.min(maxX, startLeft + dx))}px`;
            telemetryCard.style.top  = `${Math.max(0, Math.min(maxY, startTop  + dy))}px`;
        });

        document.addEventListener('mouseup', () => {
            dragging = false;
            document.body.style.userSelect = '';
        });

        // Touch drag support
        dragHandle?.addEventListener('touchstart', (e) => {
            convertToAbsolute();
            dragging = true;
            dragX = e.touches[0].clientX;
            dragY = e.touches[0].clientY;
            startLeft = parseInt(telemetryCard.style.left, 10) || 0;
            startTop  = parseInt(telemetryCard.style.top,  10) || 0;
        }, { passive: true });

        document.addEventListener('touchmove', (e) => {
            if (!dragging) return;
            const dx = e.touches[0].clientX - dragX;
            const dy = e.touches[0].clientY - dragY;
            const parent = telemetryCard.parentElement;
            const maxX = parent.clientWidth  - telemetryCard.offsetWidth;
            const maxY = parent.clientHeight - telemetryCard.offsetHeight;
            telemetryCard.style.left = `${Math.max(0, Math.min(maxX, startLeft + dx))}px`;
            telemetryCard.style.top  = `${Math.max(0, Math.min(maxY, startTop  + dy))}px`;
        }, { passive: true });

        document.addEventListener('touchend', () => { dragging = false; });

        // ── Collapse / Expand ──
        telemetryCollapseBtn?.addEventListener('click', () => {
            telemetryCollapsed = !telemetryCollapsed;
            if (telemetryBody) {
                telemetryBody.style.display = telemetryCollapsed ? 'none' : '';
            }
            if (telemetryCollapseIcon) {
                telemetryCollapseIcon.className = telemetryCollapsed
                    ? 'fa-solid fa-chevron-down text-[10px]'
                    : 'fa-solid fa-chevron-up text-[10px]';
            }
        });

        // ── Close ──
        telemetryCloseBtn?.addEventListener('click', () => {
            telemetryCard.classList.add('is-hidden');
            // Re-show via the Telemetry overlay button
        });
    }

    // ─── Telemetry toggle (map overlay button) ────────────────────────────────
    btnToggleTelemetry?.addEventListener('click', () => {
        if (!telemetryCard) return;
        if (telemetryCard.classList.contains('is-hidden')) {
            telemetryCard.classList.remove('is-hidden');
            btnToggleTelemetry.classList.add('border-blue-500/50', 'text-blue-300');
        } else {
            telemetryCard.classList.add('is-hidden');
            btnToggleTelemetry.classList.remove('border-blue-500/50', 'text-blue-300');
        }
    });

    // ─── Map Style Switcher ───────────────────────────────────────────────────
    btnMapStyleToggle?.addEventListener('click', () => {
        styleIndex = (styleIndex + 1) % mapStyles.length;
        const s = mapStyles[styleIndex];
        if (mapStyleLabel) mapStyleLabel.textContent = s.name;
        map.setStyle(s.style);
        map.once('style.load', () => { renderRoutes(); renderAnnotations(); });
    });

    // ─── Fit Track ────────────────────────────────────────────────────────────
    btnMapFit?.addEventListener('click', () => fitBounds());

    // ─── Toolbar Changes ──────────────────────────────────────────────────────
    selColor?.addEventListener('change', () => { renderRoutes(); renderAnnotations(); });
    selThick?.addEventListener('change', renderRoutes);
    togAnnot?.addEventListener('change', renderAnnotations);

    // ─── Fullscreen ───────────────────────────────────────────────────────────
    btnFullscreen?.addEventListener('click', () => {
        const target = mapWrapper || document.documentElement;
        if (!document.fullscreenElement) target.requestFullscreen().catch(() => {});
        else document.exitFullscreen().catch(() => {});
    });
    document.addEventListener('fullscreenchange', () => setTimeout(() => map?.resize(), 150));

    // ─── Tabs ─────────────────────────────────────────────────────────────────
    const ACTIVE_TAB   = 'flex flex-1 items-center justify-center gap-1.5 border-b-2 border-blue-600 py-2.5 text-blue-600 transition';
    const INACTIVE_TAB = 'flex flex-1 items-center justify-center gap-1.5 border-b-2 border-transparent py-2.5 text-slate-500 transition hover:text-slate-800';

    tabBtnTrips?.addEventListener('click', () => {
        tabBtnTrips.className = ACTIVE_TAB; tabBtnStops.className = INACTIVE_TAB;
        tabTrips?.classList.remove('hidden'); tabStops?.classList.add('hidden');
    });
    tabBtnStops?.addEventListener('click', () => {
        tabBtnStops.className = ACTIVE_TAB; tabBtnTrips.className = INACTIVE_TAB;
        tabStops?.classList.remove('hidden'); tabTrips?.classList.add('hidden');
    });

    // ─── Trip Cards ───────────────────────────────────────────────────────────
    document.querySelectorAll('.trip-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.trip-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');

            const idx = parseInt(card.dataset.tripIndex || '1', 10) - 1;
            const trip = trips[idx];
            if (!trip) return;

            const pts = (trip.points && trip.points.length > 0) ? trip.points : gpsPoints;
            if (pts.length === 0) return;

            const b = new maplibregl.LngLatBounds();
            pts.forEach(p => b.extend([p.lng, p.lat]));
            map.fitBounds(b, { padding: 80, maxZoom: 16, duration: 600 });

            const p0 = pts[0];
            const ptIdx = gpsPoints.findIndex(gp => Math.abs(gp.lat - p0.lat) < 0.0003 && Math.abs(gp.lng - p0.lng) < 0.0003);
            if (ptIdx !== -1) seekTo(ptIdx, false);
        });
    });

    // ─── Stop Cards ───────────────────────────────────────────────────────────
    document.querySelectorAll('.stop-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.stop-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const lat = parseFloat(card.dataset.lat);
            const lng = parseFloat(card.dataset.lng);
            if (lat && lng && map) map.flyTo({ center: [lng, lat], zoom: 16, essential: true, duration: 600 });
        });
    });

    // ─── Preset Buttons ───────────────────────────────────────────────────────
    presetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (presetInput) presetInput.value = btn.dataset.preset;
            filterForm?.submit();
        });
    });

    // ─── Keyboard Shortcuts ───────────────────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;
        switch (e.code) {
            case 'Space':      e.preventDefault(); togglePlay(); break;
            case 'ArrowLeft':  stopPlay(); seekTo(currentIndex - 1); break;
            case 'ArrowRight': stopPlay(); seekTo(currentIndex + 1); break;
            case 'Home':       stopPlay(); seekTo(0); break;
            case 'End':        stopPlay(); seekTo(gpsPoints.length - 1); break;
        }
    });
});
</script>
