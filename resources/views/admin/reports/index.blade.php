@extends('layouts.admin')

@section('title', 'Incident Reports')
@section('breadcrumb', 'Incident Reports')

@section('content')
    @php
        $auditNote = 'Verified and in-progress reports retain their original description, location, and evidence.';
        $heads = ['Report ID', 'Incident title', 'Location', 'Category', 'Reporter', 'Submitted', 'Priority', 'Status', 'Actions'];
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Incident Reports</h1>
            <p class="text-xs text-muted">Review, verify, and manage reported campus facility issues.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ number_format($totalAll) }} total reports</p>
            <a href="{{ route('admin.reports.export', request()->query()) }}"
               class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Export Reports</a>
        </div>
    </div>

    {{-- In the empty state the Figma design shows the audit note above the card --}}
    @if ($reports->isEmpty())
        <p class="flex items-center gap-2 text-[11px] text-muted"><x-icon name="shield-check" :size="14" />{{ $auditNote }}</p>
    @endif

    <form method="GET" action="{{ route('admin.reports.index') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        {{-- Filters --}}
        <div class="flex flex-col gap-3.5 p-[18px]">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-[370px]">
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search by report ID or incident title"
                           class="h-[38px] w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
                </div>

                <x-select name="status"      :options="$options['status']"   :selected="$filters['status']" />
                <x-select name="building_id" :options="$options['building']" :selected="$filters['building_id']" width="w-full sm:w-[186px]" />
                <x-select name="category_id" :options="$options['category']" :selected="$filters['category_id']" width="w-full sm:w-[190px]" />

                <p class="whitespace-nowrap text-[11px] text-muted">{{ number_format($reports->total()) }} {{ \Illuminate\Support\Str::plural('report', $reports->total()) }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-select name="priority" :options="$options['priority']" :selected="$filters['priority']" width="w-full sm:w-[160px]" />
                <x-select name="period"   :options="$options['period']"   :selected="$filters['period']"   width="w-full sm:w-[210px]" />

                <a href="{{ route('admin.reports.index') }}"
                   class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset filters</a>
                <p class="text-[11px] text-muted">Filters apply to the current report list.</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1060px] table-fixed text-left">
                <colgroup>
                    <col class="w-[44px]"><col class="w-[112px]"><col><col class="w-[120px]"><col class="w-[136px]">
                    <col class="w-[116px]"><col class="w-[100px]"><col class="w-[84px]"><col class="w-[108px]"><col class="w-[96px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        <th class="pl-4">
                            <input type="checkbox" aria-label="Select all reports" class="size-3.5 accent-brand"
                                   onclick="document.querySelectorAll('[data-row-check]').forEach(c => c.checked = this.checked)">
                        </th>
                        @foreach ($heads as $head)
                            <th class="px-2 font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>

                @if ($reports->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($reports as $report)
                            @php $showUrl = Route::has('admin.reports.show') ? route('admin.reports.show', $report) : '#'; @endphp
                            <tr class="h-10 border-b border-line last:border-b-0 hover:bg-canvas/60">
                                <td class="pl-4"><input type="checkbox" data-row-check aria-label="Select {{ $report->reference_no }}" class="size-3.5 accent-brand"></td>
                                <td class="px-2 font-medium text-brand"><a href="{{ $showUrl }}" class="hover:underline">{{ $report->reference_no }}</a></td>
                                <td class="px-2 py-1.5">{{ $report->title }}</td>
                                <td class="px-2">{{ $report->locationShort() }}</td>
                                <td class="px-2 py-1.5 leading-[1.4]">{{ $report->category?->name ?? '—' }}</td>
                                <td class="px-2">{{ $report->reporter?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-2">{{ $report->created_at->format('j M Y') }}</td>
                                <td class="px-2"><x-priority-badge :priority="$report->priority" /></td>
                                <td class="px-2"><x-status-badge :status="$report->status" /></td>
                                <td class="whitespace-nowrap px-2 text-[11px] font-medium text-brand">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $showUrl }}" class="hover:underline">View</a>
                                        <details data-menu class="relative">
                                            <summary class="flex cursor-pointer list-none items-center rounded p-0.5 hover:bg-canvas [&::-webkit-details-marker]:hidden" aria-label="More actions">
                                                <x-icon name="ellipsis" :size="16" />
                                            </summary>
                                            <div data-menu-panel class="absolute right-0 top-6 z-20 w-40 rounded-md border border-line bg-white py-1 text-[11px] font-normal text-ink shadow-lg">
                                                <a href="{{ $showUrl }}" class="block px-3 py-2 hover:bg-canvas">View details</a>
                                                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-canvas"
                                                        onclick="navigator.clipboard?.writeText('{{ $report->reference_no }}'); this.closest('details').removeAttribute('open')">Copy report ID</button>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>

        {{-- Filtered empty state --}}
        @if ($reports->isEmpty())
            <div class="flex min-h-[320px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">
                    {{ $hasFilters ? 'No reports match your filters' : 'No incident reports yet' }}
                </p>
                <p class="text-xs text-muted">
                    {{ $hasFilters ? 'Try another keyword or reset your filters to see campus reports.' : 'Reports submitted by the campus community will appear here.' }}
                </p>
                @if ($hasFilters)
                    <a href="{{ route('admin.reports.index') }}" class="mt-2 rounded-md bg-brand px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-blue-700">Reset filters</a>
                @endif
            </div>
        @else
            @include('admin.reports._pagination', ['paginator' => $reports])
        @endif
    </form>

    @if ($reports->isNotEmpty())
        <p class="flex items-center gap-2 text-[11px] text-muted"><x-icon name="shield-check" :size="14" />{{ $auditNote }}</p>
    @endif
@endsection
