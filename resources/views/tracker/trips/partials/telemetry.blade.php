{{-- ══════════════════════════════════════════════════════════════════════════
     Draggable Floating Vehicle Telemetry Panel
     — has a drag handle, close button, collapse button
     — starts positioned at top-right of the map canvas
══════════════════════════════════════════════════════════════════════════ --}}
<div
    id="telemetry-card"
    class="pointer-events-auto absolute right-3 top-14 z-30 w-72 select-none overflow-hidden rounded-2xl border border-slate-700/80 bg-slate-900/97 shadow-2xl backdrop-blur-md transition-shadow duration-200"
    style="max-width: calc(100% - 1.5rem);"
>
    {{-- ── Drag Handle / Header ── --}}
    <div
        id="telemetry-drag-handle"
        class="flex cursor-grab items-center justify-between border-b border-slate-800 px-4 py-2.5 active:cursor-grabbing"
        title="Drag to move"
    >
        <div class="flex items-center gap-2">
            {{-- Drag grip dots --}}
            <span class="grid grid-cols-2 gap-0.5 opacity-50">
                <span class="size-1 rounded-full bg-slate-400"></span>
                <span class="size-1 rounded-full bg-slate-400"></span>
                <span class="size-1 rounded-full bg-slate-400"></span>
                <span class="size-1 rounded-full bg-slate-400"></span>
                <span class="size-1 rounded-full bg-slate-400"></span>
                <span class="size-1 rounded-full bg-slate-400"></span>
            </span>
            <div class="flex items-center gap-1.5">
                <span class="flex size-6 items-center justify-center rounded-lg bg-blue-600/40 text-blue-400">
                    <i class="fa-solid fa-gauge-high text-[11px]"></i>
                </span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-200">Vehicle Telemetry</span>
            </div>
        </div>
        <div class="flex items-center gap-1">
            {{-- Status badge --}}
            <span
                id="telemetry-status-badge"
                class="rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-400 ring-1 ring-inset ring-slate-700"
            >STANDBY</span>
            {{-- Collapse button --}}
            <button
                id="telemetry-collapse-btn"
                type="button"
                class="ml-1.5 flex size-6 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-800 hover:text-slate-200"
                title="Collapse / Expand"
            >
                <i id="telemetry-collapse-icon" class="fa-solid fa-chevron-up text-[10px]"></i>
            </button>
            {{-- Close button --}}
            <button
                id="telemetry-close-btn"
                type="button"
                class="flex size-6 items-center justify-center rounded-lg text-slate-500 transition hover:bg-rose-900/60 hover:text-rose-400"
                title="Close telemetry panel"
            >
                <i class="fa-solid fa-xmark text-[11px]"></i>
            </button>
        </div>
    </div>

    {{-- ── Collapsible body ── --}}
    <div id="telemetry-body">
        {{-- 4 Big Metric Tiles --}}
        <div class="grid grid-cols-2 gap-px bg-slate-800/60 border-b border-slate-800">
            <div class="bg-slate-900 px-4 py-3">
                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Speed</p>
                <div class="mt-1 flex items-baseline gap-1">
                    <span id="telemetry-speed" class="font-mono text-2xl font-black text-white">0</span>
                    <span class="text-[11px] font-semibold text-slate-400">km/h</span>
                </div>
            </div>
            <div class="bg-slate-900 px-4 py-3">
                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Course</p>
                <div class="mt-1 flex items-baseline gap-1.5">
                    <span id="telemetry-course" class="font-mono text-2xl font-black text-white">0°</span>
                    <span id="telemetry-heading-arrow" class="inline-block text-sm text-sky-400 transition-transform duration-300">➤</span>
                </div>
            </div>
            <div class="bg-slate-900/80 px-4 py-3">
                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Altitude</p>
                <div class="mt-1 flex items-baseline gap-1">
                    <span id="telemetry-altitude" class="font-mono text-2xl font-black text-white">0</span>
                    <span class="text-[11px] font-semibold text-slate-400">m</span>
                </div>
            </div>
            <div class="bg-slate-900/80 px-4 py-3">
                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Satellites</p>
                <div class="mt-1 flex items-baseline gap-1">
                    <span id="telemetry-satellites" class="font-mono text-2xl font-black text-white">0</span>
                    <span class="text-[11px] font-semibold text-slate-400">sats</span>
                </div>
            </div>
        </div>

        {{-- Sensor detail rows --}}
        <div class="divide-y divide-slate-800/60 px-4 py-1 text-xs">
            @foreach([
                ['Voltage sensor',       'telemetry-voltage-sensor',   '— V',   ''],
                ['Battery Level',        'telemetry-battery-level',    '—',     'text-emerald-400'],
                ['Ignition',             'telemetry-ignition',         '—',     ''],
                ['GNSS Status',          'telemetry-gnss',             '—',     ''],
                ['Movement',             'telemetry-movement',         '—',     ''],
                ['Device Battery Voltage','telemetry-device-battery',  '— V',   ''],
                ['System Voltage',       'telemetry-system-voltage',   '— V',   ''],
                ['Timestamp',            'telemetry-timestamp',        '—',     'text-[10px]'],
            ] as [$label, $id, $default, $cls])
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-slate-500">{{ $label }}</span>
                    <span id="{{ $id }}" class="font-mono font-semibold text-slate-200 {{ $cls }}">{{ $default }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
