<header class="sticky top-0 z-30 flex h-22 shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" id="btn-open-sidebar" class="inline-flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <div class="min-w-0">
            <h1 class="truncate text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">@yield('page-title', 'Dashboard')</h1>
            @hasSection('page-subtitle')
                <p class="mt-0.5 hidden text-sm text-slate-500 sm:block">@yield('page-subtitle')</p>
            @endif
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-3">
        {{-- Page specific buttons: @section('header-actions') --}}
        @yield('header-actions')

        @include('layouts.partials.notifications')

        @auth
            <div class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </span>
                <span class="hidden text-sm font-medium text-slate-700 md:block">{{ auth()->user()->name }}</span>
            </div>
        @endauth
    </div>
</header>
