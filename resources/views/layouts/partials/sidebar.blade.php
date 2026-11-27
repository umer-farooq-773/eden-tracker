@php
    // Add or change menu items here. Set 'route' to your real route name.
    $menu = [
        ['label' => 'Live Map', 'route' => 'tracker.live-map', 'active' => 'tracker.live-map*',
         'icon' => 'fa-solid fa-map-location-dot'],
        ['label' => 'Devices', 'active' => 'tracker.devices.index', 'route' => 'tracker.devices.*',
         'icon' => 'fa-solid fa-mobile-screen-button'],
        ['label' => 'Location Activity', 'route' => 'tracker.locations.index', 'active' => 'tracker.locations.*',
         'icon' => 'fa-solid fa-location-dot'],
        ['label' => 'Trip History', 'route' => 'tracker.trips.index', 'active' => 'tracker.trips.*',
         'icon' => 'fa-solid fa-route'],
        ['label' => 'Reports', 'route' => 'tracker.reports.index', 'active' => 'tracker.reports.*',
         'icon' => 'fa-solid fa-chart-column'],
        ['label' => 'Settings', 'route' => 'tracker.settings.index', 'active' => 'tracker.settings.*',
         'icon' => 'fa-solid fa-sliders'],
    ];
@endphp

{{-- Dark backdrop (mobile only) --}}
<div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/60 lg:hidden"></div>

<aside id="app-sidebar" class="invisible fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-[#14213d] text-slate-300 transition-all duration-200 lg:visible lg:translate-x-0" aria-label="Sidebar">
    {{-- Brand --}}
    <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
        <div class="flex items-center gap-3">
            <svg class="size-8 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linejoin="round" d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" />
                <circle cx="12" cy="9" r="2.5" />
            </svg>
            <div>
                <p class="text-base font-bold leading-tight text-white">EDEN TRACKER</p>
                <p class="text-xs text-slate-400">Fleet Tracking System</p>
            </div>
        </div>
        <button type="button" id="btn-close-sidebar" class="cursor-pointer text-2xl leading-none text-slate-400 hover:text-white lg:hidden" aria-label="Close menu">&times;</button>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($menu as $item)
            @php
                $exists = Route::has($item['route']);
                $active = request()->routeIs($item['active']);
            @endphp
            <a @if ($exists) href="{{ route($item['route']) }}" @else aria-disabled="true" title="Coming soon" @endif
               @if ($active) aria-current="page" @endif
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                   'bg-blue-600 text-white shadow-lg shadow-blue-900/30' => $active,
                   'text-slate-300 hover:bg-white/10 hover:text-white' => ! $active && $exists,
                   'cursor-not-allowed text-slate-500' => ! $active && ! $exists,
               ])>
                <i class="{{ $item['icon'] }} fa-fw shrink-0 text-base" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Logout --}}
    {{-- <div class="border-t border-white/10 px-3 py-3">
        <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
            @csrf
            <button type="submit" @disabled(! Route::has('logout'))
                class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-rose-300 transition hover:bg-rose-500/15 hover:text-rose-200 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 4H5v16h5M15 8l4 4-4 4m4-4H9" />
                </svg>
                <span>Logout</span>
            </button>
        </form>
    </div> --}}

    {{-- Company --}}
    <div class="px-6 pb-5 pt-2">
        <p class="text-sm font-semibold text-white">Eden Spell</p>
        <p class="text-xs text-slate-400">Technologies</p>
    </div>
</aside>
