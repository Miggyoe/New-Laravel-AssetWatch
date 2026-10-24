<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') | MCC AssetWatch</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-ink antialiased">
@php
    // Export the "University campus" image from Figma (node 85:1513) to public/images/campus.jpg
    $campus = collect(['jpg', 'jpeg', 'png', 'webp'])->first(fn ($ext) => file_exists(public_path("images/campus.$ext")));
@endphp

<div class="flex min-h-screen">
    {{-- Compact brand bar for phones --}}
    <div class="fixed inset-x-0 top-0 z-10 flex items-center gap-[11px] bg-navy px-6 py-4 md:hidden">
        <x-brand />
    </div>

    {{-- Brand introduction --}}
    <aside class="hidden shrink-0 flex-col gap-9 bg-navy p-8 md:flex md:basis-[47.2%] xl:p-14">
        <x-brand />

        <div class="flex flex-col gap-[18px] pt-9">
            <p class="text-[11px] font-semibold leading-[1.45] text-navy-text">MCC FACILITIES &amp; MAINTENANCE</p>
            <h2 class="text-[28px] font-semibold leading-[1.45] text-white xl:text-[38px]">A better campus starts<br>with better care.</h2>
            <p class="max-w-[490px] text-sm leading-[1.45] text-navy-text xl:text-[15px]">
                One workspace to report, coordinate, and resolve campus facility issues—with accountability at every step.
            </p>
        </div>

        <div class="h-[274px] w-full overflow-hidden rounded-xl bg-navy-line">
            @if ($campus)
                <img src="{{ asset("images/campus.$campus") }}" alt="MCC campus" class="size-full object-cover">
            @else
                <div class="size-full bg-gradient-to-br from-navy-line to-brand/40"></div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-icon name="shield-check" :size="22" class="text-teal" />
            <div class="flex flex-col gap-1 leading-[1.45]">
                <p class="text-xs font-semibold text-white">Secure, role-based access</p>
                <p class="text-[11px] text-navy-text">For authorized administrators and maintenance personnel.</p>
            </div>
        </div>

        <p class="mt-auto text-[10px] leading-[1.45] text-navy-label">© {{ now()->year }} MCC AssetWatch · Main Campus</p>
    </aside>

    {{-- Sign in area --}}
    <main class="flex min-w-0 flex-1 flex-col justify-center gap-7 px-6 pb-[62px] pt-28 sm:px-12 md:py-[62px] xl:px-[100px]">
        @yield('content')
    </main>
</div>
</body>
</html>
