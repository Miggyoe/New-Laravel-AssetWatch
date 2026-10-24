@php
    use Illuminate\Support\Str;

    // [label, icon, route name]. Routes that don't exist yet fall back to "#".
    $nav = [
        ['Dashboard',             'layout-dashboard',       'admin.dashboard'],
        ['Incident Reports',      'clipboard-list',         'admin.reports.index'],
        ['Work Orders',           'wrench',                 'admin.work-orders.index'],
        ['Maintenance Personnel', 'users',                  'admin.personnel.index'],
        ['Campus Locations',      'map-pin',                'admin.locations.index'],
        ['Asset Categories',      'layers',                 'admin.categories.index'],
        ['Analytics and Reports', 'chart-no-axes-combined', 'admin.analytics'],
        ['Activity Logs',         'history',                'admin.activity-logs'],
        ['Settings',              'settings',               'admin.settings'],
    ];

    $user = auth()->user();
    $initials = Str::of($user->name)->explode(' ')->filter()->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->take(2)->implode('');
@endphp

<aside id="sidebar"
       class="flex w-[232px] shrink-0 flex-col gap-7 bg-navy px-4 pb-5 pt-[27px]
              max-lg:hidden max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:z-40
              lg:sticky lg:top-0 lg:h-screen">
    {{-- Brand --}}
    <x-brand />

    {{-- Navigation --}}
    <nav class="flex flex-col gap-2 overflow-y-auto">
        <p class="text-[9px] font-semibold text-navy-label">WORKSPACE</p>
        <ul class="flex flex-col gap-[5px]">
            @foreach ($nav as [$label, $icon, $route])
                @php
                    $pattern = $route === 'admin.dashboard' ? $route : Str::beforeLast($route, '.') . '.*';
                    $active = request()->routeIs($pattern);
                @endphp
                <li>
                    <a href="{{ Route::has($route) ? route($route) : '#' }}"
                       @if ($active) aria-current="page" @endif
                       class="flex h-[42px] items-center gap-3 rounded-md px-3 text-xs transition-colors
                              {{ $active ? 'bg-brand font-semibold text-white' : 'text-navy-text hover:bg-navy-line hover:text-white' }}">
                        <x-icon :name="$icon" />
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="flex-1"></div>

    {{-- Workspace footer --}}
    <div class="flex flex-col gap-[15px]">
        <p class="text-[11px] text-navy-text">MCC • Main Campus</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2.5 px-3 py-2 text-xs text-navy-text hover:text-white">
                <x-icon name="log-out" />
                Logout
            </button>
        </form>

        <div class="h-px bg-navy-line"></div>

        <div class="flex items-center gap-2.5">
            @if ($user->avatar)
                <img src="{{ $user->avatar }}" alt="" class="size-8 rounded-full object-cover">
            @else
                <div class="flex size-8 items-center justify-center rounded-full bg-navy-line text-[11px] font-semibold text-white">{{ $initials }}</div>
            @endif
            <div class="min-w-0 leading-normal">
                <p class="truncate text-xs font-semibold text-white">{{ $user->name }}</p>
                <p class="text-[10px] text-navy-text">{{ ucfirst($user->role === 'admin' ? 'Administrator' : $user->role) }}</p>
            </div>
        </div>
    </div>
</aside>
