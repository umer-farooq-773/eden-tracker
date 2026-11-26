<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">
    <title>GPS Location Activity | {{ config('app.name', 'Tracker') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
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
                    @if ($locations->total() > 0)
                        <form method="POST" action="{{ route('tracker.locations.destroy-all') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-black shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
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
                                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($location->latitude.','.$location->longitude) }}" target="_blank" rel="noopener noreferrer" class="font-mono text-xs font-medium text-sky-700 underline decoration-sky-300 underline-offset-4 transition hover:text-sky-900 hover:decoration-sky-600" aria-label="Open coordinates {{ $location->latitude }}, {{ $location->longitude }} in Google Maps">
                                            {{ $location->latitude }}, {{ $location->longitude }}
                                        </a>
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
</body>
</html>
