@php
    $user = auth()->user();
    $displayName = $user?->name ?? 'Guest';
@endphp

<header class="flex h-[72px] items-center justify-between gap-4 border-b border-line bg-white px-4 sm:px-7">
    <div class="flex items-center gap-3">
        <button type="button" data-sidebar-toggle class="rounded-md p-1.5 text-muted hover:bg-canvas lg:hidden" aria-label="Toggle navigation">
            <x-icon name="menu" :size="20" />
        </button>
        <p class="text-[11px] text-muted">
            Workspace&nbsp; /&nbsp;
            <span class="font-medium text-ink">@yield('breadcrumb', 'Dashboard')</span>
        </p>
    </div>

    <div class="flex items-center gap-3">
        <button type="button" class="hidden text-ink sm:block" aria-label="Help">
            <x-icon name="circle-help" :size="22" />
        </button>

        <form method="GET" action="{{ Route::has('admin.reports.index') ? route('admin.reports.index') : '#' }}"
              class="hidden h-10 w-[250px] items-center rounded-lg bg-canvas pl-4 pr-3 md:flex">
            <input type="search" name="q" placeholder="Search campus records..."
                   class="w-full bg-transparent text-xs text-ink placeholder:text-muted focus:outline-none">
            <button type="submit" class="text-muted" aria-label="Search"><x-icon name="search" :size="18" /></button>
        </form>

        <button type="button" class="relative text-ink" aria-label="Notifications">
            <x-icon name="bell" :size="20" />
            {{-- Show only when there are unread notifications --}}
            <span class="absolute -right-0.5 -top-0.5 size-1.5 rounded-full bg-red-600"></span>
        </button>

        <p class="text-xs font-medium text-ink">{{ $displayName }} <span class="text-[10px]">▾</span></p>
    </div>
</header>
