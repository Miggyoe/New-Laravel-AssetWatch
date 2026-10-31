@php
    $user  = auth()->user();
    $panel = app(\App\Services\AdminNotifications::class)->for($user);
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

        <button type="button" id="notif-toggle" class="relative text-ink" aria-label="Notifications" aria-expanded="false" aria-controls="notif-panel">
            <x-icon name="bell" :size="20" />
            @if ($panel['unread'] > 0)
                <span class="absolute -right-0.5 -top-0.5 size-1.5 rounded-full bg-red-600"></span>
            @endif
        </button>

        <p class="text-xs font-medium text-ink">{{ $user->name }} <span class="text-[10px]">▾</span></p>
    </div>

    {{-- Notifications panel (Figma: "Notifications panel") --}}
    <div id="notif-overlay" class="fixed inset-0 z-40 hidden bg-[rgba(11,27,51,0.25)]"></div>
    <section id="notif-panel" role="dialog" aria-label="Notifications"
             class="fixed right-4 top-[64px] z-50 hidden w-[400px] max-w-[calc(100vw-2rem)] rounded-xl bg-white p-5 shadow-[0_18px_60px_rgba(11,27,51,0.2)]">
        <div class="flex items-start justify-between gap-3">
            <div class="leading-[1.45]">
                <h2 class="text-xl font-semibold">Notifications</h2>
                <p class="text-[11px] text-muted">{{ $panel['unread'] > 0 ? $panel['unread'] . ' unread campus ' . ($panel['unread'] === 1 ? 'update' : 'updates') : 'No unread campus updates' }}</p>
            </div>
            <button type="button" id="notif-close" class="text-muted hover:text-ink" aria-label="Close notifications"><x-icon name="x" :size="20" /></button>
        </div>

        <div class="mt-3.5 flex items-center gap-4 text-[11px]">
            <button type="button" data-notif-tab="all" class="font-medium text-brand">All updates</button>
            <button type="button" data-notif-tab="unread" class="text-muted hover:text-ink">Unread ({{ $panel['unread'] }})</button>
            @if ($panel['unread'] > 0)
                <form method="POST" action="{{ Route::has('admin.notifications.read') ? route('admin.notifications.read') : '#' }}">
                    @csrf
                    <button type="submit" class="font-medium text-brand hover:underline">Mark all read</button>
                </form>
            @endif
        </div>

        <ul class="mt-2 max-h-[60vh] overflow-y-auto">
            @forelse ($panel['items'] as $item)
                <li data-notif-item data-unread="{{ $item['unread'] ? 1 : 0 }}" class="border-b border-line last:border-b-0">
                    <a href="{{ $item['url'] }}" class="flex items-start gap-3 py-3.5 hover:bg-canvas/60">
                        <x-icon :name="$item['icon']" :size="18" class="mt-0.5 text-brand" />
                        <div class="min-w-0 flex-1 leading-[1.45]">
                            <p class="text-xs font-semibold">{{ $item['title'] }}</p>
                            <p class="truncate text-[10px] text-muted">{{ $item['text'] }}</p>
                            <p class="mt-1 text-[10px] text-muted">{{ $item['at']->diffForHumans() }}</p>
                        </div>
                        @if ($item['unread'])
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand" title="Unread"></span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="py-8 text-center text-xs text-muted">You're all caught up. New campus updates will appear here.</li>
            @endforelse
            <li id="notif-empty-unread" class="hidden py-8 text-center text-xs text-muted">No unread updates.</li>
        </ul>

        <a href="{{ Route::has('admin.activity-logs') ? route('admin.activity-logs') : '#' }}"
           class="mt-3 flex h-9 items-center justify-center rounded-md border border-line bg-white text-[11px] font-semibold text-ink hover:bg-canvas">View All Activity</a>
    </section>

    <script>
        (() => {
            const toggle = document.getElementById('notif-toggle');
            const panel = document.getElementById('notif-panel');
            const overlay = document.getElementById('notif-overlay');
            if (!toggle || !panel) return;

            const open = (show) => {
                panel.classList.toggle('hidden', !show);
                overlay.classList.toggle('hidden', !show);
                toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
            };

            toggle.addEventListener('click', () => open(panel.classList.contains('hidden')));
            document.getElementById('notif-close').addEventListener('click', () => open(false));
            overlay.addEventListener('click', () => open(false));
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') open(false); });

            // "All updates" / "Unread (n)" tabs
            panel.querySelectorAll('[data-notif-tab]').forEach((tab) => tab.addEventListener('click', () => {
                const onlyUnread = tab.dataset.notifTab === 'unread';
                panel.querySelectorAll('[data-notif-tab]').forEach((t) => {
                    const active = t === tab;
                    t.classList.toggle('text-brand', active);
                    t.classList.toggle('font-medium', active);
                    t.classList.toggle('text-muted', !active);
                });
                let shown = 0;
                panel.querySelectorAll('[data-notif-item]').forEach((item) => {
                    const hide = onlyUnread && item.dataset.unread !== '1';
                    item.classList.toggle('hidden', hide);
                    if (!hide) shown++;
                });
                document.getElementById('notif-empty-unread').classList.toggle('hidden', !(onlyUnread && shown === 0));
            }));
        })();
    </script>
</header>
