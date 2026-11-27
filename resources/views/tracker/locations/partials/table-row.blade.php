<tr data-location-row data-id="{{ $location->id }}" class="transition-colors hover:bg-sky-50/40">
    <td data-label="Record" class="whitespace-nowrap px-5 py-4 font-mono text-xs font-medium text-slate-500 sm:px-6">
        #{{ $location->id }}
    </td>
    <td data-label="GPS device" class="px-5 py-4">
        @if ($location->trackerDevice)
            <div class="flex min-w-0 items-center gap-3">
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
    <td data-label="Position" class="whitespace-nowrap px-5 py-4">
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
    <td data-label="Altitude" class="whitespace-nowrap px-5 py-4 text-slate-700">
        {{ $location->altitude_m !== null ? number_format((float) $location->altitude_m, 1).' m' : '—' }}
    </td>
    <td data-label="Speed" class="whitespace-nowrap px-5 py-4 text-slate-700">
        {{ $location->speed_kmh !== null ? number_format((float) $location->speed_kmh, 1).' km/h' : '—' }}
    </td>
    <td data-label="Satellites" class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $location->satellites ?? '—' }}</td>
    <td data-label="HDOP" class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $location->hdop ?? '—' }}</td>
    <td data-label="GPS fix" class="whitespace-nowrap px-5 py-4">
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
    <td data-label="Source" class="whitespace-nowrap px-5 py-4">
        <span @class([
            'inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold capitalize',
            'bg-sky-50 text-sky-700' => $location->source === 'live',
            'bg-violet-50 text-violet-700' => $location->source === 'simulated',
        ])>
            {{ $location->source }}
        </span>
    </td>
    <td data-label="Timestamps (PKT)" class="whitespace-nowrap px-5 py-4">
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
