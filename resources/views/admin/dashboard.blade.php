@extends('layouts.admin')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $reportsUrl = Route::has('admin.reports.index') ? route('admin.reports.index') : '#';
        $logUrl = Route::has('admin.activity-logs') ? route('admin.activity-logs') : '#';
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">{{ $greeting }}, Administrator</h1>
            <p class="text-xs text-muted">Monitor campus facility reports and maintenance activities in one place.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ now()->format('D, j F Y') }}</p>
            <a href="{{ $reportsUrl }}" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">View All Reports</a>
        </div>
    </div>

    {{-- Summary statistics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Reports"        :value="$stats['total']"        note="Current snapshot" icon="clipboard-list" icon-class="text-brand" />
        <x-stat-card label="Pending Verification" :value="$stats['pending']"      note="Awaiting review"  icon="clock-3"        icon-class="text-[#a16207]" />
        <x-stat-card label="In Progress"          :value="$stats['in_progress']"  note="Active repairs"   icon="wrench"         icon-class="text-brand" />
        <x-stat-card label="Resolved Reports"     :value="$stats['resolved_ytd']" note="Year to date"     icon="circle-check"   icon-class="text-teal" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-[18px] xl:grid-cols-[680fr_462fr]">
        {{-- Reports overview --}}
        <section class="flex min-w-0 flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
            <div class="flex items-center justify-between gap-3">
                <div class="flex flex-col gap-[3px]">
                    <h2 class="text-[15px] font-semibold leading-[1.45]">Reports overview</h2>
                    <p class="text-[10px] text-muted">Reports received and resolved · {{ $trend['period'] }}</p>
                </div>
                <form method="GET" class="relative">
                    <select name="range" onchange="this.form.submit()"
                            class="appearance-none rounded-md border border-line bg-white py-2.5 pl-3.5 pr-9 text-xs focus:outline-none focus:ring-2 focus:ring-brand/30">
                        @foreach ([7 => 'Last 7 days', 14 => 'Last 14 days', 30 => 'Last 30 days'] as $value => $text)
                            <option value="{{ $value }}" @selected($range === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2" />
                </form>
            </div>

            <div class="flex gap-5 text-[10px] text-muted">
                <span class="flex items-center gap-1.5"><span class="size-2 rounded bg-brand"></span>Received</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded bg-teal"></span>Resolved</span>
            </div>

            <svg viewBox="0 0 640 150" class="h-auto w-full" role="img"
                 aria-label="Reports received and resolved per day, {{ $trend['period'] }}">
                @foreach ($trend['ticks'] as $tick)
                    @php $gy = 115 - ($tick / $trend['top']) * 105; @endphp
                    <line x1="30" x2="640" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#e2e8f0" />
                    <text x="0" y="{{ $gy + 3 }}" font-size="9" fill="#64748b">{{ $tick }}</text>
                @endforeach

                <polyline points="{{ $trend['received_points'] }}" fill="none" stroke="#2563eb" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                <polyline points="{{ $trend['resolved_points'] }}" fill="none" stroke="#0f9d8a" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

                @foreach ($trend['labels'] as $label)
                    @if ($label['show'])
                        <text x="{{ $label['x'] }}" y="140" text-anchor="middle" font-size="9" fill="#64748b">{{ $label['text'] }}</text>
                    @endif
                @endforeach
            </svg>
        </section>

        {{-- Status distribution --}}
        <section class="flex min-w-0 flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
            <div>
                <h2 class="text-[15px] font-semibold leading-[1.45]">Report status distribution</h2>
                <p class="text-[11px] leading-[1.45] text-muted">Current snapshot · all {{ number_format($total) }} reports</p>
            </div>

            <div class="flex flex-wrap items-center gap-6">
                <div class="relative size-[156px] shrink-0">
                    <svg viewBox="0 0 156 156" class="size-full -rotate-90" aria-hidden="true">
                        <circle cx="78" cy="78" r="69" fill="none" stroke="#e2e8f0" stroke-width="18" />
                        @foreach ($donut['segments'] as $segment)
                            @if ($segment['count'] > 0)
                                <circle cx="78" cy="78" r="69" fill="none"
                                        stroke="{{ $segment['status']->color() }}" stroke-width="18"
                                        stroke-dasharray="{{ $segment['dash'] }} {{ $donut['circumference'] }}"
                                        stroke-dashoffset="{{ $segment['offset'] }}" />
                            @endif
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="text-[28px] font-semibold leading-[1.45]">{{ number_format($total) }}</p>
                        <p class="text-[10px] text-muted">Total reports</p>
                    </div>
                </div>

                <ul class="flex min-w-[140px] flex-1 flex-col gap-[11px]">
                    @foreach ($donut['segments'] as $segment)
                        <li class="flex items-center justify-between text-[11px] leading-[1.45]">
                            <span class="flex items-center gap-[7px] text-muted">
                                <span class="size-[7px] rounded-full" style="background: {{ $segment['status']->color() }}"></span>
                                {{ $segment['status']->label() }}
                            </span>
                            <span class="font-semibold">{{ number_format($segment['count']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>

    {{-- Recent reports + activity --}}
    <div class="grid grid-cols-1 gap-[18px] xl:grid-cols-[844fr_298fr]">
        <section class="min-w-0 rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
            <div class="flex items-start justify-between px-[18px] pb-3.5 pt-4">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Recent incident reports</h2>
                <a href="{{ $reportsUrl }}" class="text-[11px] font-medium text-brand hover:underline">View all reports →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                            <th class="pl-4 pr-2 font-semibold">Report ID</th>
                            <th class="px-2 font-semibold">Incident title</th>
                            <th class="px-2 font-semibold">Building / Room</th>
                            <th class="px-2 font-semibold">Category</th>
                            <th class="px-2 font-semibold">Reported</th>
                            <th class="px-2 font-semibold">Priority</th>
                            <th class="px-2 font-semibold">Status</th>
                            <th class="pl-2 pr-4 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px]">
                        @forelse ($recent as $report)
                            <tr class="h-12 border-b border-line last:border-b-0">
                                <td class="pl-4 pr-2 font-medium text-brand">{{ $report->reference_no }}</td>
                                <td class="px-2">{{ $report->title }}</td>
                                <td class="px-2">{{ $report->placeLabel() }}</td>
                                <td class="px-2">{{ $report->category?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-2">{{ $report->created_at->format('j M Y') }}</td>
                                <td class="px-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[10px] font-semibold {{ $report->priority->badge() }}">{{ $report->priority->label() }}</span>
                                </td>
                                <td class="px-2">
                                    <span class="inline-flex whitespace-nowrap rounded-full px-2 py-1 text-[10px] font-semibold {{ $report->status->badge() }}">{{ $report->status->label() }}</span>
                                </td>
                                <td class="whitespace-nowrap pl-2 pr-4 font-medium text-brand">
                                    <a href="{{ Route::has('admin.reports.show') ? route('admin.reports.show', $report) : '#' }}" class="hover:underline">View Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-xs text-muted">No incident reports have been submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="flex min-w-0 flex-col gap-3.5 rounded-[10px] border border-line bg-white p-[18px] shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
            <h2 class="text-[15px] font-semibold leading-[1.45]">Maintenance activity</h2>

            <ul class="flex flex-col gap-[19px]">
                @forelse ($activity as $event)
                    <li class="flex items-start gap-2.5">
                        <x-icon :name="$event->icon()" :size="16" class="text-teal" />
                        <div class="flex min-w-0 flex-1 flex-col gap-1 leading-[1.45]">
                            <p class="text-[11px] font-medium">{{ $event->headline() }}</p>
                            <p class="text-[10px] text-muted">{{ $event->report?->reference_no }} · {{ $event->when() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-[11px] text-muted">No activity yet.</li>
                @endforelse
            </ul>

            <a href="{{ $logUrl }}" class="text-[11px] font-medium text-brand hover:underline">View activity log →</a>
        </section>
    </div>
@endsection
