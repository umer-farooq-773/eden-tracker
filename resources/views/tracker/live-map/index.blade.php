@extends('layouts.app')

@section('title', 'Live Map')
@section('page-title', 'Live Map')
@section('page-subtitle', 'Live fleet positions and vehicle routes.')

@push('styles')
    @include('tracker.locations.partials.map-styles')
    <style>
        #fleet-map-canvas { position: absolute; inset: 0; }
    </style>
@endpush

@section('content')
    <section class="flex min-h-[calc(100dvh-11rem)] flex-col gap-4" aria-labelledby="fleet-map-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-600 text-2xl text-white shadow-lg shadow-indigo-900/20" aria-hidden="true">🚚</span>
                <div>
                    <h2 id="fleet-map-heading" class="text-lg font-bold text-slate-950">Fleet Tracking</h2>
                    <p class="text-sm text-slate-500">Live positions &amp; vehicle status</p>
                </div>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <span class="size-2.5 animate-pulse rounded-full bg-emerald-500"></span>
                <span id="fleet-status-label">Tracking {{ $devices->count() }} vehicles</span>
            </div>
        </div>

        <form id="vehicle-filter-form" action="{{ route('tracker.live-map') }}" method="GET" class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <label for="vehicle-search" class="text-sm font-semibold text-slate-600">Vehicle</label>
            <div class="relative min-w-56 flex-1">
                <div class="relative">
                    <input
                        id="vehicle-search"
                        type="search"
                        value="{{ $selectedDevice?->device_id }}"
                        placeholder="Search vehicle…"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-controls="tracker-device-options"
                        aria-expanded="false"
                        aria-label="Search vehicles"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                    >
                    <button id="vehicle-dropdown-toggle" type="button" aria-label="Show vehicle options" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 transition hover:text-slate-800 focus:outline-none focus:text-indigo-600">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
                <div id="tracker-device-options" role="listbox" aria-label="Vehicles" class="absolute left-0 top-full z-50 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-900/15"></div>
            </div>
            <input id="vehicle-device-id" type="hidden" name="device_id" value="{{ $selectedDevice?->device_id }}">
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">Go</button>
            <a href="{{ route('tracker.live-map') }}" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-cyan-700 focus:outline-none focus:ring-4 focus:ring-cyan-200">Show all vehicles</a>
            <span class="ml-auto rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600">{{ $devices->count() }} vehicles</span>
            <p id="vehicle-search-error" class="hidden basis-full text-sm text-rose-600" role="alert">Choose a registered vehicle from the list.</p>
        </form>

        <div class="relative min-h-[28rem] flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 shadow-lg shadow-slate-900/10" id="fleet-map-shell">
            <div id="fleet-map-canvas" class="h-full w-full"></div>

            <div class="pointer-events-none absolute left-4 top-4 z-10 flex flex-col gap-2">
                <div class="pointer-events-auto rounded-xl border border-white/70 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
                    <p id="fleet-map-title" class="text-sm font-bold text-slate-900">{{ $selectedDevice?->name ?? 'All vehicles' }}</p>
                    <p id="fleet-map-subtitle" class="mt-0.5 text-xs text-slate-500">{{ $selectedDevice ? $selectedDevice->device_id.' · Live route' : 'Latest location for each active vehicle' }}</p>
                </div>
                <div class="pointer-events-auto flex flex-wrap gap-2">
                    <button id="fleet-map-style" type="button" class="rounded-lg border border-white/70 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow transition hover:bg-white">Map style: Google Maps</button>
                    <button id="fleet-map-fit" type="button" class="rounded-lg border border-white/70 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow transition hover:bg-white">Fit vehicles</button>
                </div>
            </div>

            <div class="pointer-events-none absolute bottom-4 left-4 z-10 rounded-xl border border-white/70 bg-white/95 px-4 py-3 text-xs text-slate-600 shadow-lg backdrop-blur">
                <p class="mb-2 font-semibold text-slate-900">Map legend</p>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <span class="inline-flex items-center gap-2"><span class="size-3 rounded-full bg-rose-500 ring-2 ring-white"></span>Current position</span>
                    <span class="inline-flex items-center gap-2"><span class="h-1 w-5 rounded-full bg-sky-500"></span>Selected vehicle route</span>
                    <span class="inline-flex items-center gap-2"><span class="flex size-4 items-center justify-center rounded-full bg-amber-500 text-[9px] font-bold text-slate-950 ring-2 ring-white">P</span>Parked / no signal</span>
                </div>
            </div>

            <div class="absolute bottom-4 right-4 z-10 flex items-center gap-2 rounded-xl border border-white/70 bg-white/95 px-3 py-2 text-xs font-medium text-slate-600 shadow-lg backdrop-blur" role="status" aria-live="polite">
                <span id="fleet-live-indicator" class="size-2 rounded-full bg-emerald-500"></span>
                <span id="fleet-live-status">Connecting to live updates…</span>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @include('tracker.live-map.partials.script')
@endpush
