{{-- ══════════════════════════════════════════════════════════════════════════
     Bottom Playback Controller Bar
     Clear visual state for every button: playing = red pause icon on green bg,
     paused = white play icon on blue bg.
══════════════════════════════════════════════════════════════════════════ --}}
<div class="flex-shrink-0 border-t border-slate-200 bg-white px-4 py-2 shadow-[0_-2px_8px_rgba(0,0,0,0.06)]">
    <div class="flex flex-wrap items-center gap-3">

        {{-- ── Playback Buttons ── --}}
        <div class="flex items-center gap-1">

            {{-- Jump to Start --}}
            <button id="btn-player-start" type="button" title="Jump to Start"
                class="group flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 active:scale-90">
                <i class="fa-solid fa-backward-step text-[11px]"></i>
            </button>

            {{-- Step Back --}}
            <button id="btn-player-prev" type="button" title="Previous point (←)"
                class="group flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 active:scale-90">
                <i class="fa-solid fa-backward text-[11px]"></i>
            </button>

            {{-- ── PLAY / PAUSE — clearly two distinct visual states ── --}}
            <button id="btn-player-toggle" type="button" title="Play (Space)"
                class="relative flex size-11 items-center justify-center rounded-xl transition active:scale-90"
                data-playing="false"
            >
                {{-- PAUSED state: solid blue with white play icon + "PLAY" label ring --}}
                <span id="play-state"
                    class="absolute inset-0 flex items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-500/40 ring-2 ring-blue-300/50">
                    <i class="fa-solid fa-play translate-x-px text-base text-white"></i>
                </span>
                {{-- PLAYING state: solid green with white pause icon (hidden by default) --}}
                <span id="pause-state"
                    class="absolute inset-0 hidden items-center justify-center rounded-xl bg-emerald-600 shadow-lg shadow-emerald-500/40 ring-2 ring-emerald-300/50">
                    <i class="fa-solid fa-pause text-base text-white"></i>
                </span>
            </button>

            {{-- Step Forward --}}
            <button id="btn-player-next" type="button" title="Next point (→)"
                class="group flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 active:scale-90">
                <i class="fa-solid fa-forward text-[11px]"></i>
            </button>

            {{-- Jump to End --}}
            <button id="btn-player-end" type="button" title="Jump to End"
                class="group flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 active:scale-90">
                <i class="fa-solid fa-forward-step text-[11px]"></i>
            </button>
        </div>

        {{-- ── Timeline Scrubber ── --}}
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <span id="player-time-elapsed" class="flex-shrink-0 font-mono text-xs font-semibold text-slate-500">0:00</span>
            <input
                id="player-timeline-slider"
                type="range"
                min="0" max="100" value="0" step="0.01"
                aria-label="Timeline Scrubber"
                class="min-w-0 flex-1"
            >
            <span id="player-time-total" class="flex-shrink-0 font-mono text-xs font-semibold text-slate-400">{{ $totalDurationFormatted ?: '0:00' }}</span>
        </div>

        {{-- ── Speed Selector ── --}}
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-bold text-slate-400">Speed:</span>
            <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 p-0.5 text-xs font-bold">
                @foreach(['0.5' => '0.5x', '1' => '1x', '2' => '2x', '4' => '4x', '8' => '8x', '16' => '16x'] as $val => $label)
                    <button
                        type="button"
                        data-speed="{{ $val }}"
                        class="player-speed-btn rounded-md px-2 py-1 transition {{ $val === '1' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- ── Timestamp Clock ── --}}
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 shadow-sm">
            <i class="fa-regular fa-clock text-sm text-blue-500" aria-hidden="true"></i>
            <div class="text-left">
                <p id="player-current-time" class="font-mono text-xs font-black leading-none text-slate-800">00:00:00</p>
                <p id="player-current-date" class="mt-0.5 font-mono text-[10px] leading-none text-slate-400">—</p>
            </div>
        </div>

    </div>
</div>
