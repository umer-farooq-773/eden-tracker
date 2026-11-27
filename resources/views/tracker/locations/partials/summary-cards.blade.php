<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Location summary">
    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Total location records</p>
                <p id="location-total-count" class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($locations->total()) }}</p>
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
                <p id="location-page-count" class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($locations->count()) }}</p>
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
