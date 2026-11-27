<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/[0.04]" aria-labelledby="records-heading">
    @if (session('status'))
        <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800" role="status">
            {{ session('status') }}
        </div>
    @endif
    @include('tracker.locations.partials.toolbar')

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
                    @include('tracker.locations.partials.table-row')
                @empty
                    @include('tracker.locations.partials.table-empty')
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
