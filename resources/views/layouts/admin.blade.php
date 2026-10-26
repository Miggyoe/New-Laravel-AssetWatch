<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | MCC AssetWatch</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas font-sans text-ink antialiased">
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('admin.partials.topbar')

            <main class="flex flex-col gap-[18px] p-4 sm:p-6">
                @if (session('status'))
                    <div role="status" class="flex items-center gap-2.5 rounded-md bg-[#e8f7f3] p-3 text-[#0f9d8a]">
                        <x-icon name="circle-check" :size="16" />
                        <p class="text-xs font-semibold">{{ session('status') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
