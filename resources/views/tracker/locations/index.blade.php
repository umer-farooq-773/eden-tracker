<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">
    <title>GPS Location Activity | {{ config('app.name', 'Tracker') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" />
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
    <style>
        .maplibregl-popup-content {
            background: #0f172a !important;
            color: #f8fafc !important;
            border-radius: 0.75rem !important;
            padding: 0.85rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(51, 65, 85, 0.8) !important;
            font-family: inherit !important;
        }
        .maplibregl-popup-anchor-top .maplibregl-popup-tip { border-bottom-color: #0f172a !important; }
        .maplibregl-popup-anchor-bottom .maplibregl-popup-tip { border-top-color: #0f172a !important; }
        .maplibregl-popup-anchor-left .maplibregl-popup-tip { border-right-color: #0f172a !important; }
        .maplibregl-popup-anchor-right .maplibregl-popup-tip { border-left-color: #0f172a !important; }
        .maplibregl-popup-close-button {
            color: #94a3b8 !important;
            padding: 4px 8px !important;
            font-size: 16px !important;
        }
        .maplibregl-popup-close-button:hover {
            color: #ffffff !important;
            background: transparent !important;
        }
        @keyframes mapPulse {
            0% { transform: scale(0.95); opacity: 0.9; }
            50% { transform: scale(1.4); opacity: 0.3; }
            100% { transform: scale(0.95); opacity: 0.9; }
        }
        .map-pulse-ring {
            animation: mapPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-80 bg-gradient-to-br from-sky-100 via-indigo-50 to-transparent"></div>

    <main class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-10 lg:py-12">
        <header class="mb-8 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sky-300 shadow-lg shadow-slate-900/15">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" />
                        <circle cx="12" cy="9" r="2.5" />
                    </svg>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">GPS tracking</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Location activity</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Review device positions, signal quality, and the latest location updates.
                    </p>
                </div>
            </div>

            <div class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3.5 py-2 text-sm font-medium text-slate-600 shadow-sm">
                <svg class="size-4 text-sky-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Location history
            </div>
        </header>

        <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Location summary">
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total location records</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($locations->total()) }}</p>
                    </div>
                    <div class="flex size-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Across all registered GPS devices</p>
            </article>

            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Records on this page</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($locations->count()) }}</p>
                    </div>
                    <div class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14M5 10h14M5 15h8M5 20h8" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Showing the newest records first</p>
            </article>

            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:col-span-2 xl:col-span-1">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Current page</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ $locations->currentPage() }}
                            <span class="text-lg font-medium text-slate-400">/ {{ max($locations->lastPage(), 1) }}</span>
                        </p>
                    </div>
                    <div class="flex size-11 items-center justify-center rounded-xl bg-violet-50 text-violet-700">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="4" y="4" width="16" height="16" rx="3" />
                            <path stroke-linecap="round" d="M8 9h8M8 13h8M8 17h4" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Each page contains up to 50 records</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/[0.04]" aria-labelledby="records-heading">
            @if (session('status'))
                <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="records-heading" class="text-base font-semibold text-slate-950">GPS records</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($locations->total() > 0)
                            Showing {{ number_format($locations->firstItem()) }}–{{ number_format($locations->lastItem()) }} of {{ number_format($locations->total()) }} records
                        @else
                            No location records yet
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="text-xs font-medium text-slate-500">Times shown in PKT (UTC+5)</div>
                    <button
                        type="button"
                        id="btn-open-map-modal"
                        @class([
                            'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2',
                            'bg-sky-600 text-black hover:bg-sky-700 focus:ring-sky-500 cursor-pointer shadow-sky-600/20' => $mapLocations->isNotEmpty(),
                            'bg-slate-200 text-slate-400 cursor-not-allowed' => $mapLocations->isEmpty(),
                        ])
                        @if ($mapLocations->isEmpty()) disabled title="No GPS coordinates available on this page" @endif
                        aria-label="Open Full Screen Route Map"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21" />
                            <line x1="9" y1="3" x2="9" y2="18" />
                            <line x1="15" y1="6" x2="15" y2="21" />
                        </svg>
                        <span>Full screen map</span>
                        <span class="rounded bg-sky-500/80 px-1.5 py-0.5 text-[10px] font-mono text-white">
                            {{ $mapLocations->count() }}
                        </span>
                    </button>
                    @if ($locations->total() > 0)
                        <form method="POST" action="{{ route('tracker.locations.destroy-all') }}" >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-black shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 cursor-pointer">
                                Delete all
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50/90 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <th scope="col" class="px-5 py-3.5 sm:px-6">Record</th>
                            <th scope="col" class="px-5 py-3.5">GPS device</th>
                            <th scope="col" class="px-5 py-3.5">Position</th>
                            <th scope="col" class="px-5 py-3.5">Altitude</th>
                            <th scope="col" class="px-5 py-3.5">Speed</th>
                            <th scope="col" class="px-5 py-3.5">Satellites</th>
                            <th scope="col" class="px-5 py-3.5">HDOP</th>
                            <th scope="col" class="px-5 py-3.5">GPS fix</th>
                            <th scope="col" class="px-5 py-3.5">Source</th>
                            <th scope="col" class="px-5 py-3.5">Timestamps (PKT)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($locations as $location)
                            <tr class="transition-colors hover:bg-sky-50/40">
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-medium text-slate-500 sm:px-6">
                                    #{{ $location->id }}
                                </td>
                                <td class="px-5 py-4">
                                    @if ($location->trackerDevice)
                                        <div class="flex min-w-48 items-center gap-3">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <rect x="6" y="3" width="12" height="18" rx="3" />
                                                    <path stroke-linecap="round" d="M10 7h4M10 17h4" />
                                                </svg>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold text-slate-900">
                                                    {{ $location->trackerDevice->name ?: $location->trackerDevice->device_id }}
                                                </span>
                                                <span class="mt-0.5 block truncate font-mono text-xs text-slate-500">
                                                    {{ $location->trackerDevice->device_id }}
                                                </span>
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-slate-400">Unregistered device</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($location->latitude !== null && $location->longitude !== null)
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex size-6 items-center justify-center rounded-md bg-sky-50 text-sky-700 transition hover:bg-sky-100 hover:text-sky-900 cursor-pointer js-row-focus-btn"
                                                data-id="{{ $location->id }}"
                                                data-lat="{{ $location->latitude }}"
                                                data-lng="{{ $location->longitude }}"
                                                title="View record #{{ $location->id }} on map"
                                                aria-label="View record #{{ $location->id }} on map"
                                            >
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="3" />
                                                    <path d="M12 2v3m0 14v3M2 12h3m14 0h3" />
                                                </svg>
                                            </button>
                                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($location->latitude.','.$location->longitude) }}" target="_blank" rel="noopener noreferrer" class="font-mono text-xs font-medium text-sky-700 underline decoration-sky-300 underline-offset-4 transition hover:text-sky-900 hover:decoration-sky-600" aria-label="Open coordinates {{ $location->latitude }}, {{ $location->longitude }} in Google Maps">
                                                {{ $location->latitude }}, {{ $location->longitude }}
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-slate-400">No position</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                    {{ $location->altitude_m !== null ? number_format((float) $location->altitude_m, 1).' m' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                    {{ $location->speed_kmh !== null ? number_format((float) $location->speed_kmh, 1).' km/h' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $location->satellites ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $location->hdop ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($location->gps_fix)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                            Fixed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                            <span class="size-1.5 rounded-full bg-amber-500"></span>
                                            No fix
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span @class([
                                        'inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold capitalize',
                                        'bg-sky-50 text-sky-700' => $location->source === 'live',
                                        'bg-violet-50 text-violet-700' => $location->source === 'simulated',
                                    ])>
                                        {{ $location->source }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="space-y-1.5 text-xs">
                                        <p class="text-slate-800">
                                            <span class="mr-1.5 text-slate-400">Recorded</span>
                                            {{ $location->recorded_at?->timezone('Asia/Karachi')->format('M j, Y · g:i:s A') ?? '—' }}
                                        </p>
                                        <p class="text-slate-600">
                                            <span class="mr-1.5 text-slate-400">Received</span>
                                            {{ $location->received_at?->timezone('Asia/Karachi')->format('M j, Y · g:i:s A') ?? '—' }}
                                        </p>
                                        <p class="text-slate-500">
                                            <span class="mr-1.5 text-slate-400">Created</span>
                                            {{ $location->created_at?->timezone('Asia/Karachi')->format('M j, Y · g:i:s A') ?? '—' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-20 text-center">
                                    <span class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" />
                                            <circle cx="12" cy="9" r="2.5" />
                                        </svg>
                                    </span>
                                    <p class="font-semibold text-slate-900">No GPS records yet</p>
                                    <p class="mt-1 text-sm text-slate-500">Location updates will appear here when a registered device sends them.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($locations->hasPages())
                <div class="border-t border-slate-200 bg-slate-50/70 px-4 py-4 sm:px-6">
                    {{ $locations->links() }}
                </div>
            @endif
        </section>

        <footer class="py-6 text-center text-xs text-slate-400">
            GPS location activity · Secure device tracking
        </footer>
    </main>

    <!-- Full Screen GPS Route Map Modal -->
    <div
        id="gps-map-modal"
        class="fixed inset-0 z-50 hidden flex-col bg-slate-950/80 backdrop-blur-sm transition-opacity"
        role="dialog"
        aria-modal="true"
        aria-labelledby="map-modal-title"
    >
        <div class="relative flex h-full w-full flex-col bg-slate-900 text-slate-100 shadow-2xl">
            <!-- Modal Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900/95 px-4 py-3 sm:px-6">
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
            <div class="relative flex-1 bg-slate-950">
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

    <!-- Map Data and Controller Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rawLocations = @json($mapLocations);

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
            const sortedLocations = [...rawLocations].sort((a, b) => {
                if (a.recorded_timestamp && b.recorded_timestamp) {
                    return a.recorded_timestamp - b.recorded_timestamp;
                }
                return a.id - b.id;
            });

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
                            <p class="font-semibold text-sky-300 truncate">${item.device_name}</p>
                            <p class="text-[11px] font-mono text-slate-400">${item.device_id}</p>
                        </div>
                        <div class="space-y-1 font-mono text-[11px] text-slate-300">
                            <p><span class="text-slate-400">Position:</span> ${item.latitude.toFixed(6)}, ${item.longitude.toFixed(6)}</p>
                            <p><span class="text-slate-400">Speed:</span> ${item.speed_kmh !== null ? item.speed_kmh.toFixed(1) + ' km/h' : '—'}</p>
                            <p><span class="text-slate-400">Altitude:</span> ${item.altitude_m !== null ? item.altitude_m.toFixed(1) + ' m' : '—'}</p>
                            <p><span class="text-slate-400">Fix / Sats:</span> ${item.gps_fix ? 'Fixed' : 'No fix'} (${item.satellites ?? 0} sats)</p>
                            <p class="text-[10px] text-slate-400"><span class="text-slate-400">Time:</span> ${item.recorded_at}</p>
                        </div>
                        <div class="pt-1 border-t border-slate-700/80 flex items-center justify-between">
                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400 capitalize">${item.source}</span>
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
                if (!mapInstance || sortedLocations.length === 0) return;

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
            }

            function clearMarkers() {
                markersList.forEach(m => m.marker.remove());
                markersList = [];
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
                if (sortedLocations.length === 0) {
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

            // Row Focus Buttons
            document.querySelectorAll('.js-row-focus-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const recordId = btn.getAttribute('data-id');
                    openModal(recordId);
                });
            });

            // Close on ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Row Focus Buttons
            document.querySelectorAll('.js-row-focus-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const recordId = btn.getAttribute('data-id');
                    openModal(recordId);
                });
            });

            // Close on ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
