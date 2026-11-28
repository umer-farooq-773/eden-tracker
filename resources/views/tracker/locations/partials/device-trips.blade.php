<section id="vehicle-trips" class="mb-6 scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="device-trips-heading">
    @if ($errors->has('tracker'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first('tracker') }}</div>
    @endif
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="device-trips-heading" class="text-base font-semibold text-slate-950">Vehicle trips</h2>
            <p class="mt-1 text-sm text-slate-500">Trip routes save GPS coordinates, speed, altitude, signal quality, and timestamps.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $devices->count() }} active devices</span>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($devices as $device)
            <article id="vehicle-trip-{{ $device->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $device->name }}</p>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $device->device_id }}</p>
                    <p class="mt-2 text-xs {{ $device->activeTrip ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $device->activeTrip ? 'Trip active since '.$device->activeTrip->started_at->timezone('Asia/Karachi')->format('M j, g:i A') : ($device->is_trip_tracking ? 'Waiting for GPS update' : 'No active trip') }}
                    </p>
                </div>
                @if ($device->is_trip_tracking)
                    <form method="POST" action="{{ route('tracker.trips.stop', $device) }}">
                        @csrf
                        <button type="submit" class="shrink-0 rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">Stop trip</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('tracker.trips.start', $device) }}">
                        @csrf
                        <button type="submit" class="shrink-0 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">Start trip</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">No active tracker devices are registered.</p>
        @endforelse
    </div>
</section>
