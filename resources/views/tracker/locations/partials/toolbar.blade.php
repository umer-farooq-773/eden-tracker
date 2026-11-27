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
            <form method="POST" action="{{ route('tracker.locations.destroy-all') }}" onsubmit="return confirm('Delete all location records? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-black shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 cursor-pointer">
                    Delete all
                </button>
            </form>
        @endif
    </div>
</div>
