@extends('layouts.admin')

@section('title', 'Analytics and Reports')
@section('breadcrumb', 'Analytics and Reports')

@section('content')
    @php
        $card = 'rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]';
        $ghost = 'flex h-9 items-center rounded-md border border-line bg-white px-3.5 text-[11px] font-semibold text-ink hover:bg-canvas';
        $exportUrl = route('admin.analytics.export', $queryString);

        // Monthly stacked bars (viewBox 340 x 190)
        $maxMonth = max(1, collect($months)->max('total'));
        $slot     = 320 / max(1, count($months));
        $barW     = min(36, $slot * 0.55);
        $barH     = 125;
        $base     = 165;

        $topCategory = max(1, $categories->first()['count'] ?? 1);
        $topBuilding = max(1, $buildings->first()['count'] ?? 1);

        $activeOrders = $technicians->sum('active_orders_count');
        $completed    = $technicians->sum('completed_count');
    @endphp

    {{-- Print: only the report itself --}}
    <style>
        @media print {
            #sidebar, header, [data-print-hide] { display: none !important; }
            body { background: #fff !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Analytics and Reports</h1>
            <p class="text-xs text-muted">Understand recurring facility issues and plan campus maintenance with confidence.</p>
        </div>
        <details data-menu data-print-hide class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700 [&::-webkit-details-marker]:hidden">
                Export Report <x-icon name="chevron-down" :size="14" />
            </summary>
            <div data-menu-panel class="absolute right-0 top-11 z-20 w-48 rounded-md border border-line bg-white py-1 text-[11px] text-ink shadow-lg">
                <button type="button" onclick="window.print()" class="block w-full px-3 py-2 text-left hover:bg-canvas">Print or save as PDF</button>
                <a href="{{ $exportUrl }}" class="block px-3 py-2 hover:bg-canvas">Download CSV data</a>
            </div>
        </details>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.analytics') }}" data-print-hide class="flex flex-wrap items-center gap-3">
        <x-select name="period"      :options="$options['period']"   :selected="$filters['period']"      :submit="false" width="w-full sm:w-[190px]" />
        <x-select name="building_id" :options="$options['building']" :selected="$filters['building_id']" :submit="false" width="w-full sm:w-[190px]" />
        <button type="submit" class="{{ $ghost }}">Apply filters</button>
        <p class="text-[11px] text-muted">Current snapshot · {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('report', $total) }}</p>

        <div class="ml-auto flex gap-2.5">
            <button type="button" onclick="window.print()" class="{{ $ghost }}">PDF report</button>
            <a href="{{ $exportUrl }}" class="{{ $ghost }}">CSV data</a>
        </div>
    </form>

    {{-- Headline numbers --}}
    <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
        <div class="relative flex flex-col gap-2 p-4 {{ $card }}">
            <p class="text-[11px] text-muted">Total incidents</p>
            <x-icon name="clipboard-list" :size="18" class="absolute right-4 top-4 text-brand" />
            <div class="flex items-end justify-between gap-2">
                <p class="text-[30px] font-semibold leading-[1.3]">{{ number_format($total) }}</p>
                <p class="text-[10px] text-muted">{{ $periodNote }}</p>
            </div>
        </div>

        <div class="relative flex flex-col gap-2 p-4 {{ $card }}">
            <p class="text-[11px] text-muted">Average repair turnaround</p>
            <x-icon name="timer" :size="18" class="absolute right-4 top-4 text-teal" />
            <div class="flex items-end justify-between gap-2">
                <p class="text-[30px] font-semibold leading-[1.3]">{{ $turnaround === null ? '—' : $turnaround . ' days' }}</p>
                @if ($turnaroundDelta !== null)
                    <p class="text-[10px] text-muted">{{ $turnaroundDelta <= 0 ? '↓' : '↑' }} {{ abs($turnaroundDelta) }} vs prior period</p>
                @endif
            </div>
        </div>

        <div class="relative flex flex-col gap-2 p-4 {{ $card }}">
            <p class="text-[11px] text-muted">Resolved / Unresolved</p>
            <x-icon name="circle-check" :size="18" class="absolute right-4 top-4 text-teal" />
            <div class="flex items-end justify-between gap-2">
                <p class="text-[30px] font-semibold leading-[1.3]">{{ number_format($resolved) }} / {{ number_format($unresolved) }}</p>
                <p class="text-[10px] text-muted">Includes {{ number_format($rejected) }} rejected</p>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-3.5 xl:grid-cols-[360fr_246fr_221fr]">
        {{-- Monthly incidents --}}
        <section class="flex min-w-0 flex-col gap-2.5 self-start p-4 {{ $card }}">
            <div class="leading-[1.45]">
                <h2 class="text-sm font-semibold">Monthly incidents &amp; resolution</h2>
                <p class="text-[10px] text-muted">Count of reports · resolved versus unresolved</p>
            </div>
            <div class="flex gap-4 text-[10px] text-muted">
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-teal"></span>Resolved</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-brand"></span>Unresolved</span>
            </div>

            <svg viewBox="0 0 340 190" class="h-auto w-full" role="img" aria-label="Reports per month, resolved versus unresolved">
                <line x1="0" x2="340" y1="{{ $base }}" y2="{{ $base }}" stroke="#e2e8f0" />
                @foreach ($months as $i => $month)
                    @php
                        $x   = 10 + $i * $slot + ($slot - $barW) / 2;
                        $hR  = $month['resolved'] / $maxMonth * $barH;
                        $hU  = $month['unresolved'] / $maxMonth * $barH;
                        $mid = $x + $barW / 2;
                    @endphp
                    @if ($month['total'] > 0)
                        <rect x="{{ round($x, 1) }}" y="{{ round($base - $hR, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hR, 1) }}" fill="#0f9d8a" />
                        <rect x="{{ round($x, 1) }}" y="{{ round($base - $hR - $hU, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hU, 1) }}" rx="2" fill="#2563eb" />
                        <text x="{{ round($mid, 1) }}" y="{{ round($base - $hR - $hU - 6, 1) }}" text-anchor="middle" font-size="10" fill="#64748b">{{ $month['total'] }}</text>
                    @endif
                    <text x="{{ round($mid, 1) }}" y="182" text-anchor="middle" font-size="10" fill="#64748b">{{ $month['label'] }}</text>
                @endforeach
            </svg>
        </section>

        {{-- Categories --}}
        <section class="flex min-w-0 flex-col gap-3 p-4 {{ $card }}">
            <div class="leading-[1.45]">
                <h2 class="text-sm font-semibold">Most reported asset categories</h2>
                <p class="text-[10px] text-muted">All reports · {{ $categories->count() }} {{ \Illuminate\Support\Str::plural('category', $categories->count()) }}</p>
            </div>
            @forelse ($categories as $row)
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-[11px]"><span>{{ $row['label'] }}</span><span class="font-semibold">{{ number_format($row['count']) }}</span></div>
                    <div class="h-[5px] rounded-full bg-[#e2e8f0]"><div class="h-full rounded-full bg-brand" style="width: {{ max(3, round($row['count'] / $topCategory * 100)) }}%"></div></div>
                </div>
            @empty
                <p class="text-[11px] text-muted">No reports in this period.</p>
            @endforelse
        </section>

        {{-- Buildings --}}
        <section class="flex min-w-0 flex-col gap-3 p-4 {{ $card }}">
            <div class="leading-[1.45]">
                <h2 class="text-sm font-semibold">Building incident frequency</h2>
                <p class="text-[10px] text-muted">All reports · Ranked by building</p>
            </div>
            @forelse ($buildings as $row)
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-[11px]"><span class="truncate pr-2">{{ $row['label'] }}</span><span class="font-semibold">{{ number_format($row['count']) }}</span></div>
                    <div class="h-[5px] rounded-full bg-[#e2e8f0]"><div class="h-full rounded-full bg-brand" style="width: {{ max(3, round($row['count'] / $topBuilding * 100)) }}%"></div></div>
                </div>
            @empty
                <p class="text-[11px] text-muted">No reports in this period.</p>
            @endforelse
        </section>
    </div>

    {{-- Status + technicians --}}
    <div class="grid grid-cols-1 gap-3.5 xl:grid-cols-[318fr_538fr]">
        <section class="flex min-w-0 flex-col gap-3.5 self-start p-4 {{ $card }}">
            <h2 class="text-sm font-semibold leading-[1.45]">Report status distribution</h2>
            <div class="flex flex-wrap items-center gap-5">
                <div class="relative size-[132px] shrink-0">
                    <svg viewBox="0 0 156 156" class="size-full -rotate-90" aria-hidden="true">
                        <circle cx="78" cy="78" r="69" fill="none" stroke="#e2e8f0" stroke-width="18" />
                        @foreach ($donut['segments'] as $segment)
                            @if ($segment['count'] > 0)
                                <circle cx="78" cy="78" r="69" fill="none" stroke="{{ $segment['status']->color() }}" stroke-width="18"
                                        stroke-dasharray="{{ $segment['dash'] }} {{ $donut['circumference'] }}" stroke-dashoffset="{{ $segment['offset'] }}" />
                            @endif
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="text-[24px] font-semibold leading-[1.3]">{{ number_format($total) }}</p>
                        <p class="text-[9px] text-muted">Total reports</p>
                    </div>
                </div>
                <ul class="flex min-w-[120px] flex-1 flex-col gap-2.5">
                    @foreach ($donut['segments'] as $segment)
                        <li class="flex items-center justify-between text-[11px]">
                            <span class="flex items-center gap-[7px] text-muted"><span class="size-[7px] rounded-full" style="background: {{ $segment['status']->color() }}"></span>{{ $segment['status']->label() }}</span>
                            <span class="font-semibold">{{ number_format($segment['count']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="min-w-0 {{ $card }}">
            <div class="p-4 pb-3 leading-[1.45]">
                <h2 class="text-sm font-semibold">Technician workload &amp; completions</h2>
                <p class="text-[10px] text-muted">{{ number_format($activeOrders) }} active orders · {{ number_format($completed) }} completed in selected period</p>
            </div>
            <div class="overflow-x-auto px-4 pb-4">
                <table class="w-full min-w-[460px] text-left">
                    <thead>
                        <tr class="h-[38px] bg-canvas text-[10px] font-semibold text-muted">
                            <th class="pl-3.5 font-semibold">Technician</th>
                            <th class="px-2 font-semibold">Active orders</th>
                            <th class="px-2 font-semibold">Completed</th>
                            <th class="px-2 font-semibold">On time</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px]">
                        @forelse ($technicians as $tech)
                            <tr class="h-[34px] border-b border-line last:border-b-0">
                                <td class="pl-3.5 pr-2">{{ $tech->name }}</td>
                                <td class="px-2">{{ $tech->active_orders_count }}</td>
                                <td class="px-2">{{ $tech->completed_count }}</td>
                                <td class="px-2">{{ $tech->completed_count ? round($tech->on_time_count / $tech->completed_count * 100) . '%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3.5 py-6 text-center text-muted">No active maintenance personnel.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
