<!-- Full Screen GPS Route Map Modal -->
<div
    id="gps-map-modal"
    class="fixed inset-0 z-[1000] hidden flex-col bg-slate-950/80 backdrop-blur-sm transition-opacity"
    role="dialog"
    aria-modal="true"
    aria-labelledby="map-modal-title"
>
    <div class="relative flex h-dvh min-h-0 w-full flex-col bg-slate-900 text-slate-100 shadow-2xl">
        <!-- Modal Header -->
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900/95 px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-sky-500/20 text-sky-400 ring-1 ring-sky-500/30">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21" />
                        <line x1="9" y1="3" x2="9" y2="18" />
                        <line x1="15" y1="6" x2="15" y2="21" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 id="map-modal-title" class="text-base font-semibold text-white">GPS Route & Position History</h3>
                        <span id="map-point-count" class="rounded-full bg-sky-500/20 px-2.5 py-0.5 text-xs font-medium text-sky-300 ring-1 ring-inset ring-sky-500/30">
                            {{ $mapLocations->count() }} positions
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Chronological trajectory with MapLibre &amp; Google Maps / OpenStreetMap</p>
                </div>
            </div>

            <!-- Location & Stats Badges -->
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <!-- Dynamic Area Location Pill -->
                <div id="map-location-pill" class="flex items-center gap-1.5 rounded-lg border border-sky-500/40 bg-sky-500/10 px-3 py-1.5 text-xs font-medium text-sky-300 shadow-sm">
                    <svg class="size-3.5 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" />
                        <circle cx="12" cy="9" r="2.5" />
                    </svg>
                    <span>Near:</span>
                    <span id="map-place-name" class="font-semibold text-white">Locating area...</span>
                </div>

                <div class="hidden items-center gap-2.5 md:flex">
                    <div class="rounded-lg border border-slate-800 bg-slate-800/80 px-3 py-1.5 shadow-sm">
                        <span class="text-slate-400">Total Route:</span>
                        <span id="map-stat-distance" class="ml-1 font-mono font-semibold text-white">0.00 km</span>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-800/80 px-3 py-1.5 shadow-sm">
                        <span class="text-slate-400">Latest Speed:</span>
                        <span id="map-stat-speed" class="ml-1 font-mono font-semibold text-white">—</span>
                    </div>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center gap-2">
                <!-- Zoom to Town / Area Context Toggle -->
                <button
                    id="btn-toggle-area-context"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-slate-700 hover:text-white cursor-pointer"
                    title="Zoom out to see surrounding towns, roads, and landmarks"
                >
                    <svg class="size-3.5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <span id="area-context-label" class="hidden sm:inline">Show town area</span>
                </button>

                <!-- Recenter / Fit Route Button -->
                <button
                    id="btn-recenter-map"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-slate-700 hover:text-white cursor-pointer"
                    title="Fit all positions in view"
                >
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                    </svg>
                    <span class="hidden sm:inline">Fit route</span>
                </button>

                <!-- Toggle Map Style -->
                <button
                    id="btn-toggle-style"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-sky-500/50 bg-sky-950/40 px-3 py-1.5 text-xs font-medium text-sky-200 transition hover:bg-sky-900/60 hover:text-white cursor-pointer"
                    title="Switch between Google Maps, Google Satellite, and OpenStreetMap"
                >
                    <svg class="size-3.5 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span id="map-style-label" class="hidden sm:inline">Google Maps</span>
                </button>

                <!-- Direct Google Maps external button -->
                <a
                    id="btn-open-google-maps"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-slate-700 hover:text-white"
                    title="Open this route in Google Maps"
                >
                    <svg class="size-3.5 text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span class="hidden sm:inline">Google Maps</span>
                </a>

                <!-- Fullscreen Browser API Toggle -->
                <button
                    id="btn-toggle-fullscreen"
                    type="button"
                    class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800 p-1.5 text-slate-200 transition hover:bg-slate-700 hover:text-white cursor-pointer"
                    title="Toggle full screen mode"
                >
                    <svg id="fullscreen-icon-expand" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                    </svg>
                    <svg id="fullscreen-icon-collapse" class="hidden size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3" />
                    </svg>
                </button>

                <!-- Close Modal Button -->
                <button
                    id="btn-close-map-modal"
                    type="button"
                    class="inline-flex items-center rounded-lg bg-rose-600/90 p-1.5 text-white transition hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-400 cursor-pointer"
                    title="Close map (Esc)"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Map Container -->
        <div class="relative min-h-0 flex-1 bg-slate-950">
            <div id="maplibre-canvas" class="h-full w-full"></div>

            <div id="nearby-places-status" class="absolute right-4 bottom-4 z-10 rounded-lg border border-slate-700/80 bg-slate-900/90 px-3 py-2 text-xs text-slate-200 shadow-xl" role="status" aria-live="polite">
                Zoom in to level 15 to see nearby places
            </div>

            <!-- Floating Legend -->
            <div class="pointer-events-none absolute bottom-5 left-5 z-10">
                <div class="pointer-events-auto rounded-xl border border-slate-700/80 bg-slate-900/90 p-3 shadow-xl backdrop-blur-md text-xs text-slate-200">
                    <p class="mb-2 font-semibold text-white flex items-center gap-1.5">
                        <span class="size-2 rounded-full bg-sky-400"></span>
                        Route Legend
                    </p>
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="flex size-3 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-emerald-300"></span>
                            <span class="text-slate-300">Start Position</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="flex size-3 items-center justify-center rounded-full bg-rose-500 ring-2 ring-rose-300"></span>
                            <span class="text-slate-300">Latest Position</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="flex size-2.5 items-center justify-center rounded-full bg-white ring-2 ring-sky-500"></span>
                            <span class="text-slate-300">Waypoint (Click for info)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-1 w-5 rounded bg-sky-500 shadow-sm shadow-sky-500"></span>
                            <span class="text-slate-300">Trajectory Line</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick info card (top right) -->
            <div class="pointer-events-none absolute top-4 right-4 z-10 hidden sm:block">
                <div id="map-quick-summary" class="pointer-events-auto w-72 rounded-xl border border-slate-700/80 bg-slate-900/90 p-3.5 shadow-xl backdrop-blur-md text-xs text-slate-300">
                    <p class="font-semibold text-white">Latest Device Fix</p>
                    <p id="summary-device" class="mt-1 font-medium text-sky-400 truncate">—</p>
                    <div class="mt-2 space-y-1 text-[11px] text-slate-400">
                        <p>Recorded: <span id="summary-time" class="text-slate-200 font-mono">—</span></p>
                        <p>Coordinates: <span id="summary-coords" class="text-slate-200 font-mono">—</span></p>
                        <div class="flex gap-4 pt-1">
                            <p>Speed: <span id="summary-speed" class="text-slate-200 font-mono">—</span></p>
                            <p>Alt: <span id="summary-alt" class="text-slate-200 font-mono">—</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
