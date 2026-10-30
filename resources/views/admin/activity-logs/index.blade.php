@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('breadcrumb', 'Activity Logs')

@section('content')
    @php
        $heads = ['Date / Time', 'User', 'Role', 'Action performed', 'Related record', 'Previous status', 'New status'];
        $pill  = 'inline-flex h-[23px] items-center whitespace-nowrap rounded-full px-2 text-[10px] font-semibold';
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Activity Logs</h1>
            <p class="text-xs text-muted">A chronological, read-only record of administrative and maintenance actions.</p>
        </div>
        <p class="text-[11px] text-muted">All timestamps · {{ $timezone }}</p>
    </div>

    <x-alert tone="blue" icon="info" title="Audit history is read-only"
             body="Records cannot be edited or deleted. Workflow changes preserve who acted, what changed, and the related report or order." />

    <form method="GET" action="{{ route('admin.activity-logs') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3 p-[18px]">
            <div class="relative w-full sm:w-[320px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search user, action, report or work order"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>
            <x-select name="role"   :options="$options['role']"   :selected="$filters['role']"   width="w-full sm:w-[150px]" />
            <x-select name="action" :options="$options['action']" :selected="$filters['action']" width="w-full sm:w-[160px]" />
            <x-select name="period" :options="$options['period']" :selected="$filters['period']" width="w-full sm:w-[180px]" />
            <a href="{{ route('admin.activity-logs') }}" class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset</a>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] table-fixed text-left">
                <colgroup>
                    <col class="w-[150px]"><col class="w-[170px]"><col class="w-[130px]"><col class="w-[200px]">
                    <col class="w-[140px]"><col class="w-[140px]"><col>
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>
                @if ($logs->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($logs as $log)
                            @php
                                [$recordLabel, $recordUrl] = $target($log);
                                $previous = \App\Models\ActivityLog::statusMeta($log->from_status);
                                $new      = \App\Models\ActivityLog::statusMeta($log->to_status);
                            @endphp
                            <tr class="h-[62px] border-b border-line last:border-b-0 hover:bg-canvas/60" title="{{ $log->description }}">
                                <td class="whitespace-nowrap pl-4 pr-2">{{ $log->created_at->format($log->created_at->isCurrentYear() ? 'j M · g:i A' : 'j M Y · g:i A') }}</td>
                                <td class="truncate px-2">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-2">{{ $log->roleLabel() }}</td>
                                <td class="px-2 leading-[1.4]">{{ $log->label() }}</td>
                                <td class="truncate px-2">
                                    @if ($recordUrl)
                                        <a href="{{ $recordUrl }}" class="font-medium text-brand hover:underline">{{ $recordLabel }}</a>
                                    @else
                                        <span class="{{ $recordLabel === '—' ? '' : 'text-muted' }}">{{ $recordLabel }}</span>
                                    @endif
                                </td>
                                <td class="px-2">
                                    @if ($previous) <span class="{{ $pill }} {{ $previous['badge'] }}">{{ $previous['label'] }}</span> @else — @endif
                                </td>
                                <td class="px-2">
                                    @if ($new) <span class="{{ $pill }} {{ $new['badge'] }}">{{ $new['label'] }}</span> @else — @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>

        @if ($logs->isEmpty())
            <div class="flex min-h-[260px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">{{ $hasFilters ? 'No activity matches your filters' : 'No activity recorded yet' }}</p>
                <p class="text-xs text-muted">{{ $hasFilters ? 'Try another keyword or widen the date range.' : 'Actions taken in the portal will appear here.' }}</p>
                @if ($hasFilters)
                    <a href="{{ route('admin.activity-logs') }}" class="mt-2 rounded-md bg-brand px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-blue-700">Reset filters</a>
                @endif
            </div>
        @else
            @include('admin.partials.pagination', ['paginator' => $logs])
        @endif
    </form>

    @if ($reassignment)
        <p class="text-[10px] text-muted">Reassignment detail · {{ $reassignment }}</p>
    @endif
@endsection
