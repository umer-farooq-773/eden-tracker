<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @include('layouts.partials.sidebar')

    <div class="flex min-h-screen min-w-0 flex-col lg:pl-64">
        @include('layouts.partials.header')

        <main class="w-full min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>

        @include('layouts.partials.footer')
    </div>

    @stack('modals')

    @include('layouts.partials.sidebar-script')
    @stack('scripts')
</body>
</html>
