@extends('layouts.app')

@section('title', 'Trip History')
@section('page-title', 'Trip History')
@section('page-subtitle', 'Review vehicle trajectories, trips, stops, and historical telemetry playback.')

@push('styles')
    @include('tracker.locations.partials.map-styles')
    <style>
        /* ══════════════════════════════════════════════════════
           FULL-PAGE WORKSPACE  (desktop = side-by-side, mobile = stacked)
        ══════════════════════════════════════════════════════ */

        /* Pull the page out of the main padding so it can fill the viewport */
        #trip-history-page {
            margin: -1.5rem -1rem -1.5rem -1rem;
        }
        @media (min-width: 640px) {
            #trip-history-page { margin: -1.5rem -1.5rem -1.5rem -1.5rem; }
        }
        @media (min-width: 1024px) {
            #trip-history-page { margin: -1.5rem -2rem -1.5rem -2rem; }
        }

        /* Desktop: the workspace (below filter bar) fills remaining screen height */
        @media (min-width: 1024px) {
            #trip-workspace {
                height: calc(100dvh - var(--filter-bar-height, 110px));
                min-height: 500px;
                overflow: hidden;
            }
            #trip-sidebar {
                width: 300px;
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                border-right: 1px solid #e2e8f0;
                background: #fff;
            }
            #trip-stop-scroll {
                flex: 1 1 0;
                min-height: 0;
                overflow-y: auto;
            }
            #trip-map-col {
                flex: 1 1 0;
                min-width: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            #trip-map-wrap {
                flex: 1 1 0;
                min-height: 0;
                position: relative;
            }
        }

        /* Mobile: everything stacks, no fixed height */
        @media (max-width: 1023px) {
            #trip-workspace { display: block; }
            #trip-sidebar   { width: 100%; }
            #trip-stop-scroll { max-height: 320px; overflow-y: auto; }
            #trip-map-col   { display: flex; flex-direction: column; }
            #trip-map-wrap  { position: relative; height: 55vmax; min-height: 280px; max-height: 500px; }
        }

        /* ── Map canvas always fills its container ── */
        #trip-map-canvas { position: absolute; inset: 0; width: 100%; height: 100%; }

        /* ── Vehicle marker ── */
        .vehicle-playback-marker { cursor: pointer; }

        /* ── Stop markers ── */
        .custom-stop-marker { cursor: pointer; transition: transform 0.15s ease; }
        .custom-stop-marker:hover { transform: scale(1.06); z-index: 30 !important; }

        /* ── Active trip / stop card ── */
        .trip-card.active { background: #eff6ff; border-left-width: 4px; }
        .stop-card.active { background: #fffbeb; border-left-color: #f59e0b; }

        /* ── Custom range slider ── */
        #player-timeline-slider {
            -webkit-appearance: none; appearance: none;
            height: 5px; border-radius: 9999px;
            background: #cbd5e1; cursor: pointer; outline: none; width: 100%;
        }
        #player-timeline-slider::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 15px; height: 15px; border-radius: 50%;
            background: #2563eb; border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(37,99,235,.5); cursor: pointer;
        }
        #player-timeline-slider::-moz-range-thumb {
            width: 15px; height: 15px; border-radius: 50%;
            background: #2563eb; border: 2px solid #fff; cursor: pointer;
        }

        /* ── Telemetry panel ── */
        #telemetry-card.is-hidden { display: none !important; }
        #telemetry-card { position: absolute; cursor: default; user-select: none; z-index: 30; }
        #telemetry-drag-handle { cursor: grab; }
        #telemetry-drag-handle:active { cursor: grabbing; }

        /* ── Play/Pause two-state button ── */
        #btn-player-toggle[data-playing="true"]  #play-state  { display: none; }
        #btn-player-toggle[data-playing="true"]  #pause-state { display: flex; }
        #btn-player-toggle[data-playing="false"] #play-state  { display: flex; }
        #btn-player-toggle[data-playing="false"] #pause-state { display: none; }

        /* ── Metric cards stretch evenly ── */
        .metric-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }

        /* ── Filter bar scroll on very small screens ── */
        @media (max-width: 639px) {
            #trip-filter-row { flex-wrap: wrap; }
        }
    </style>
@endpush

@section('content')
<div id="trip-history-page">

    {{-- ════════════════════════════════════════════════════════
         FILTER BAR — compact single-row layout, fully responsive
    ════════════════════════════════════════════════════════ --}}
    <div id="trip-filter-bar" class="border-b border-slate-200 bg-white shadow-sm">
        <form id="trip-filter-form" action="{{ route('tracker.trips.index') }}" method="GET">

            {{-- ROW 1: all controls in one flex row, wrapping gracefully --}}
            <div class="flex flex-wrap items-center gap-2 px-3 py-2.5 sm:gap-3 sm:px-4">

                {{-- ① Vehicle / Unit --}}
                <div class="flex min-w-0 flex-1 flex-col" style="min-width:160px;max-width:280px;">
                    <label for="device_id" class="mb-0.5 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                        <i class="fa-solid fa-truck text-blue-500"></i> Vehicle / Unit
                    </label>
                    <div class="relative flex items-center">
                        <span class="pointer-events-none absolute left-2 flex size-5 flex-shrink-0 items-center justify-center rounded bg-blue-600 text-white">
                            <i class="fa-solid fa-truck text-[8px]"></i>
                        </span>
                        <select id="device_id" name="device_id"
                            class="w-full appearance-none rounded-lg border-2 border-slate-200 bg-white py-1.5 pl-9 pr-6 text-xs font-bold text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            @foreach ($devices as $device)
                                <option value="{{ $device->device_id }}" @selected($selectedDevice?->device_id === $device->device_id)>
                                    {{ $device->device_id }} • {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-1.5 flex size-4 items-center justify-center rounded bg-slate-100 text-slate-500">
                            <i class="fa-solid fa-chevron-down text-[8px]"></i>
                        </span>
                    </div>
                </div>

                {{-- ② Time Range presets --}}
                <div class="flex flex-col flex-shrink-0">
                    <label class="mb-0.5 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                        <i class="fa-regular fa-calendar-days text-blue-500"></i> Time Range
                    </label>
                    <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 text-[11px] font-bold whitespace-nowrap">
                        <input type="hidden" name="preset" id="preset-input" value="{{ $preset }}">
                        @foreach (['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'Week', 'month' => 'Month'] as $key => $lbl)
                            <button type="button" data-preset="{{ $key }}"
                                class="preset-btn rounded-md px-2.5 py-1 transition {{ $preset === $key ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- ③ From --}}
                <div class="flex flex-col flex-shrink-0">
                    <label for="from" class="mb-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400">From</label>
                    <input type="datetime-local" id="from" name="from" value="{{ $from }}"
                        class="rounded-lg border-2 border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>

                {{-- ④ To --}}
                <div class="flex flex-col flex-shrink-0">
                    <label for="to" class="mb-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400">To</label>
                    <input type="datetime-local" id="to" name="to" value="{{ $to }}"
                        class="rounded-lg border-2 border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>

                {{-- ⑤ Show Track — aligns with inputs via self-end --}}
                <button type="submit"
                    class="mt-auto inline-flex flex-shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-bold text-white shadow-md shadow-blue-600/20 transition hover:bg-blue-700 active:scale-95 sm:px-5 sm:py-2">
                    <i class="fa-solid fa-play text-xs"></i>
                    Show Track
                </button>
            </div>

            {{-- ROW 2: secondary toolbar (Color, Thickness, Annotations, Trip Detector, Full View) --}}
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 border-t border-slate-100 px-3 py-1.5 text-xs sm:px-4">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">

                    {{-- Color Mode --}}
                    <div class="flex items-center gap-1">
                        <i class="fa-solid fa-palette text-[9px] text-slate-400"></i>
                        <span class="font-semibold text-slate-500">Color:</span>
                        <div class="relative ml-0.5">
                            <select id="route-color-mode"
                                class="appearance-none rounded-md border border-slate-200 bg-slate-50 py-0.5 pl-2 pr-5 text-[11px] font-semibold text-slate-700 outline-none focus:border-blue-400">
                                <option value="by_trips">By trips</option>
                                <option value="single">Single</option>
                                <option value="by_speed">By speed</option>
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-1 flex items-center text-slate-400">
                                <i class="fa-solid fa-chevron-down text-[8px]"></i>
                            </span>
                        </div>
                    </div>

                    {{-- Thickness --}}
                    <div class="flex items-center gap-1">
                        <i class="fa-solid fa-pen-nib text-[9px] text-slate-400"></i>
                        <span class="font-semibold text-slate-500">Thickness:</span>
                        <div class="relative ml-0.5">
                            <select id="route-thickness"
                                class="appearance-none rounded-md border border-slate-200 bg-slate-50 py-0.5 pl-2 pr-5 text-[11px] font-semibold text-slate-700 outline-none focus:border-blue-400">
                                <option value="4">4px</option>
                                <option value="5">5px</option>
                                <option value="7" selected>7px</option>
                                <option value="9">9px</option>
                                <option value="12">12px</option>
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-1 flex items-center text-slate-400">
                                <i class="fa-solid fa-chevron-down text-[8px]"></i>
                            </span>
                        </div>
                    </div>

                    {{-- Annotations toggle --}}
                    <label class="flex cursor-pointer select-none items-center gap-1.5">
                        <input type="checkbox" id="toggle-annotations" class="sr-only peer" checked>
                        <div class="relative h-4 w-8 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:size-3 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-4 transition-colors"></div>
                        <span class="font-semibold text-slate-600">Annotations</span>
                    </label>

                    {{-- Trip Detector toggle --}}
                    <label class="flex cursor-pointer select-none items-center gap-1.5">
                        <input type="checkbox" id="toggle-trip-detector" class="sr-only peer" checked>
                        <div class="relative h-4 w-8 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:size-3 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-4 transition-colors"></div>
                        <span class="font-semibold text-slate-600">Trip Detector</span>
                    </label>
                </div>

                {{-- Full View --}}
                <button id="btn-app-fullscreen" type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100">
                    <i class="fa-solid fa-expand text-[10px]"></i>
                    Full View
                </button>
            </div>
        </form>
    </div>

    {{-- ════════════════════════════════════════════════════════
         WORKSPACE  (desktop = flex row | mobile = stacked)
    ════════════════════════════════════════════════════════ --}}
    <div id="trip-workspace" class="flex flex-col lg:flex-row">

        {{-- ── LEFT SIDEBAR ── --}}
        <div id="trip-sidebar">

            {{-- Asset Card --}}
            <div class="border-b border-slate-100 bg-white p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/30">
                        <i class="fa-solid fa-truck text-lg" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-bold text-slate-900">{{ $selectedDevice?->name ?? 'No Vehicle Selected' }}</h2>
                        <p class="truncate font-mono text-[11px] text-slate-400">{{ $selectedDevice?->device_id ?? 'Please select a vehicle' }}</p>
                    </div>
                </div>

                {{-- 4 Metric Cards --}}
                <div class="metric-grid mt-3">
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Mileage</p>
                        <p class="mt-0.5 font-mono text-sm font-black text-slate-900">
                            {{ number_format($totalMileageKm, 2) }}<span class="ml-0.5 text-[10px] font-normal text-slate-500">km</span>
                        </p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Duration</p>
                        <p class="mt-0.5 font-mono text-sm font-black text-slate-900">{{ $totalDurationFormatted ?: '0h 0m' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Moving Time</p>
                        <p class="mt-0.5 font-mono text-sm font-black text-emerald-600">{{ $movingTimeFormatted ?: '0h 0m' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Max Speed</p>
                        <p class="mt-0.5 font-mono text-sm font-black text-rose-600">
                            {{ number_format($maxSpeedKmh, 0) }}<span class="ml-0.5 text-[10px] font-normal text-slate-500">km/h</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Trips / Stops Tabs --}}
            <div class="flex border-b border-slate-200 bg-slate-50 text-xs font-bold">
                <button type="button" id="tab-btn-trips"
                    class="flex flex-1 items-center justify-center gap-1.5 border-b-2 border-blue-600 py-2.5 text-blue-600">
                    <i class="fa-solid fa-route text-[11px]" aria-hidden="true"></i>
                    Trips ({{ count($trips) }})
                </button>
                <button type="button" id="tab-btn-stops"
                    class="flex flex-1 items-center justify-center gap-1.5 border-b-2 border-transparent py-2.5 text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-square-parking text-[11px]" aria-hidden="true"></i>
                    Stops ({{ count($stops) }})
                </button>
            </div>

            {{-- Scrollable list --}}
            <div id="trip-stop-scroll" class="bg-white">

                {{-- Trips --}}
                <div id="tab-content-trips" class="divide-y divide-slate-100">
                    @forelse ($trips as $trip)
                        <div class="trip-card cursor-pointer border-l-4 bg-white px-4 py-3 transition hover:bg-blue-50"
                            style="border-left-color: {{ $trip['color'] }};"
                            data-trip-id="{{ $trip['id'] }}"
                            data-trip-index="{{ $trip['number'] }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="size-2.5 flex-shrink-0 rounded-full" style="background: {{ $trip['color'] }};"></span>
                                    <span class="truncate text-sm font-bold text-slate-900">{{ $trip['name'] }}</span>
                                </div>
                                <span class="ml-2 flex-shrink-0 font-mono text-xs font-bold text-blue-600">{{ $trip['distance_km'] }} km</span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">
                                <i class="fa-regular fa-clock mr-1 text-slate-400"></i>{{ $trip['time_range'] }}
                            </p>
                            <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                                <span><i class="fa-regular fa-hourglass mr-1 text-slate-400"></i>{{ $trip['duration_formatted'] }}</span>
                                <span class="font-medium">⚡ {{ $trip['avg_speed_kmh'] }} km/h</span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-12 text-center text-slate-400">
                            <i class="fa-solid fa-route mb-3 text-4xl text-slate-200"></i>
                            <p class="text-xs font-semibold">No trips in this time range</p>
                            <p class="mt-1 text-[11px]">Select a vehicle and click "Show Track"</p>
                        </div>
                    @endforelse
                </div>

                {{-- Stops --}}
                <div id="tab-content-stops" class="hidden divide-y divide-slate-100">
                    @forelse ($stops as $stop)
                        <div class="stop-card cursor-pointer border-l-4 border-l-amber-400 bg-white px-4 py-3 transition hover:bg-amber-50"
                            data-stop-id="{{ $stop['id'] }}"
                            data-lat="{{ $stop['lat'] }}"
                            data-lng="{{ $stop['lng'] }}">
                            <div class="flex items-center justify-between">
                                <span class="rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">{{ $stop['name'] }}</span>
                                <span class="ml-2 rounded bg-amber-50 px-2 py-0.5 font-mono text-[11px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                    {{ $stop['duration_formatted'] }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                <i class="fa-regular fa-clock mr-1 text-slate-400"></i>{{ $stop['time_range'] }}
                            </p>
                            <p class="mt-1 font-mono text-[10px] text-slate-400">
                                <i class="fa-solid fa-location-dot mr-1 text-amber-500"></i>{{ $stop['coords_formatted'] }}
                            </p>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-12 text-center text-slate-400">
                            <i class="fa-solid fa-square-parking mb-3 text-4xl text-slate-200"></i>
                            <p class="text-xs font-semibold">No stops recorded</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── MAP + PLAYER COLUMN ── --}}
        <div id="trip-map-col">

            {{-- Map Canvas --}}
            <div id="trip-map-wrap" class="bg-slate-900">
                <div id="trip-map-canvas"></div>

                {{-- Top-left overlay buttons --}}
                <div class="pointer-events-none absolute left-3 top-3 z-20 flex flex-wrap items-center gap-1.5">
                    <button id="btn-map-fit" type="button"
                        class="pointer-events-auto inline-flex items-center gap-1 rounded-lg border border-slate-700/80 bg-slate-900/90 px-2.5 py-1.5 text-xs font-semibold text-slate-200 shadow backdrop-blur transition hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-expand text-[10px]"></i>
                        <span class="hidden sm:inline">Fit Track</span>
                    </button>
                    <button id="btn-toggle-telemetry" type="button"
                        class="pointer-events-auto inline-flex items-center gap-1 rounded-lg border border-slate-700/80 bg-slate-900/90 px-2.5 py-1.5 text-xs font-semibold text-slate-200 shadow backdrop-blur transition hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-gauge-high text-[10px] text-blue-400"></i>
                        <span class="hidden sm:inline">Telemetry</span>
                    </button>
                    <button id="btn-map-style-toggle" type="button"
                        class="pointer-events-auto inline-flex items-center gap-1 rounded-lg border border-slate-700/80 bg-slate-900/90 px-2.5 py-1.5 text-xs font-semibold text-slate-200 shadow backdrop-blur transition hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-layer-group text-[10px] text-sky-400"></i>
                        <span id="trip-map-style-label" class="hidden sm:inline">OpenStreetMap</span>
                        <span class="sm:hidden">Map</span>
                    </button>
                </div>

                {{-- Status pill top-right --}}
                <div class="pointer-events-none absolute right-3 top-3 z-20">
                    <div id="trip-status-pill"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-slate-900/90 px-3 py-1.5 text-xs font-semibold text-emerald-400 shadow backdrop-blur">
                        <span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>
                        <span id="trip-status-text">
                            @if(count($gpsPoints) > 0) {{ count($gpsPoints) }} GPS Points @else Ready @endif
                        </span>
                    </div>
                </div>

                {{-- Floating Telemetry Card --}}
                @include('tracker.trips.partials.telemetry')
            </div>

            {{-- Bottom Player Bar --}}
            @include('tracker.trips.partials.player')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Measure the filter bar height once and use it in CSS for desktop workspace height
    function setWorkspaceHeight() {
        const filterBar = document.getElementById('trip-filter-bar');
        const workspace = document.getElementById('trip-workspace');
        if (!filterBar || !workspace || window.innerWidth < 1024) return;

        const headerEl  = document.querySelector('header, nav, #app-header') || { offsetHeight: 64 };
        const headerH   = headerEl.offsetHeight || 64;
        const filterH   = filterBar.offsetHeight;
        workspace.style.height = `calc(100dvh - ${headerH + filterH}px)`;
    }

    document.addEventListener('DOMContentLoaded', setWorkspaceHeight);
    window.addEventListener('resize', setWorkspaceHeight);
</script>
    @include('tracker.trips.partials.script')
@endpush
