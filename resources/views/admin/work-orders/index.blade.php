@extends('layouts.admin')

@section('title', 'Work Orders')
@section('breadcrumb', 'Work Orders')

@section('content')
    @php
        $heads = ['Work order', 'Report ID', 'Task title', 'Building / Room', 'Technician', 'Assigned', 'Target date', 'Priority', 'Status', 'Actions'];
        $reportUrl = fn ($report) => Route::has('admin.reports.show') ? route('admin.reports.show', $report) : '#';
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Work Orders</h1>
            <p class="text-xs text-muted">Assign repair tasks, coordinate technicians, and track completion.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ number_format($periodTotal) }} {{ $periodText }}</p>
            <button type="button" data-modal-open="create-work-order"
                    class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Create Work Order</button>
        </div>
    </div>

    {{-- Status summary --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach (\App\Enums\WorkOrderStatus::cases() as $status)
            <div class="flex items-center justify-between rounded-[10px] border border-line bg-white p-[15px] shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <x-status-badge :status="$status" />
                <p class="text-[23px] font-semibold leading-[1.45]">{{ number_format($counts[$status->value] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.work-orders.index') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3 p-[18px]">
            <div class="relative w-full sm:w-[240px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search order ID, report, or task"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>

            <x-select name="status"        :options="$options['status']"     :selected="$filters['status']" width="w-full sm:w-[150px]" />
            <x-select name="technician_id" :options="$options['technician']" :selected="$filters['technician_id']" width="w-full sm:w-[170px]" />
            <x-select name="priority"      :options="$options['priority']"   :selected="$filters['priority']" width="w-full sm:w-[150px]" />
            <x-select name="period"        :options="$options['period']"     :selected="$filters['period']" width="w-full sm:w-[150px]" />

            <a href="{{ route('admin.work-orders.index') }}"
               class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset</a>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] table-fixed text-left">
                <colgroup>
                    <col class="w-[116px]"><col class="w-[112px]"><col><col class="w-[124px]"><col class="w-[132px]">
                    <col class="w-[100px]"><col class="w-[100px]"><col class="w-[84px]"><col class="w-[108px]"><col class="w-[92px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>

                @if ($orders->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($orders as $order)
                            @php $showUrl = route('admin.work-orders.show', $order); @endphp
                            <tr class="h-12 border-b border-line last:border-b-0 hover:bg-canvas/60">
                                <td class="pl-4 pr-2 font-medium text-brand"><a href="{{ $showUrl }}" class="hover:underline">{{ $order->reference_no }}</a></td>
                                <td class="px-2 font-medium text-brand"><a href="{{ $reportUrl($order->report) }}" class="hover:underline">{{ $order->report?->reference_no }}</a></td>
                                <td class="px-2 py-1.5">{{ $order->title }}</td>
                                <td class="px-2">{{ $order->report?->locationShort() }}</td>
                                <td class="px-2">{{ $order->technician?->name }}</td>
                                <td class="whitespace-nowrap px-2">{{ $order->created_at->format('j M Y') }}</td>
                                <td class="whitespace-nowrap px-2">{{ $order->target_at->format('j M Y') }}</td>
                                <td class="px-2"><x-priority-badge :priority="$order->priority" /></td>
                                <td class="px-2"><x-status-badge :status="$order->status" /></td>
                                <td class="whitespace-nowrap px-2 text-[11px] font-medium text-brand">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $showUrl }}" class="hover:underline">View</a>
                                        <details data-menu class="relative">
                                            <summary class="flex cursor-pointer list-none items-center rounded p-0.5 hover:bg-canvas [&::-webkit-details-marker]:hidden" aria-label="More actions">
                                                <x-icon name="ellipsis" :size="16" />
                                            </summary>
                                            <div data-menu-panel class="absolute right-0 top-6 z-20 w-44 rounded-md border border-line bg-white py-1 text-[11px] font-normal text-ink shadow-lg">
                                                <a href="{{ $showUrl }}" class="block px-3 py-2 hover:bg-canvas">View details</a>
                                                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-canvas"
                                                        onclick="navigator.clipboard?.writeText('{{ $order->reference_no }}'); this.closest('details').removeAttribute('open')">Copy work order ID</button>
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

        @if ($orders->isEmpty())
            <div class="flex min-h-[300px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">{{ $hasFilters ? 'No work orders match your filters' : 'No work orders yet' }}</p>
                <p class="text-xs text-muted">
                    {{ $hasFilters ? 'Try another keyword or reset your filters.' : 'Create a work order from a verified incident report to assign it to a technician.' }}
                </p>
                @if ($hasFilters)
                    <a href="{{ route('admin.work-orders.index') }}" class="mt-2 rounded-md bg-brand px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-blue-700">Reset filters</a>
                @endif
            </div>
        @else
            @include('admin.partials.pagination', ['paginator' => $orders])
        @endif
    </form>

    {{-- Create Work Order modal (Figma: "Work Orders / Create work order modal") --}}
    <dialog id="create-work-order" data-modal @if ($openCreate) data-autoopen @endif
            class="m-auto max-h-[calc(100vh-2rem)] w-[650px] max-w-[calc(100vw-2rem)] overflow-y-auto rounded-xl bg-white p-0 text-ink shadow-[0_18px_60px_rgba(11,27,51,0.15)] backdrop:bg-[rgba(11,27,51,0.6)]">
        <form method="POST" action="{{ route('admin.work-orders.store') }}" class="flex flex-col gap-5 p-7">
            @csrf
            <input type="hidden" name="_form" value="create">

            <div class="flex items-start justify-between gap-4">
                <div class="flex flex-col gap-[5px] leading-[1.45]">
                    <h2 class="text-xl font-semibold">Create Work Order</h2>
                    <p class="text-xs text-muted">Assign a verified incident to the right maintenance technician.</p>
                </div>
                <button type="button" data-modal-close class="text-muted hover:text-ink" aria-label="Close"><x-icon name="x" :size="20" /></button>
            </div>

            <div>
                <x-select name="report_id" label="Related incident report" required :submit="false" width="w-full"
                          :options="$options['assignable']" :selected="old('report_id', $preselectedReport)" />
                @unless ($hasAssignable)
                    <p class="mt-1 text-[10px] text-muted">No verified incident reports are waiting for a work order.</p>
                @endunless
            </div>

            <x-input name="title" label="Work order title" required maxlength="150" placeholder="e.g. Repair classroom chair" />
            <x-textarea name="instructions" label="Repair instructions" required rows="3" maxlength="2000"
                        placeholder="Describe what the technician should do and any safety checks." />

            <div class="flex flex-col gap-4 sm:flex-row">
                <x-select name="technician_id" label="Assigned technician" required :submit="false" width="w-full sm:flex-1"
                          :options="$options['assignee']" :selected="old('technician_id')" />
                <x-select name="priority" label="Priority" required :submit="false" width="w-full sm:w-[174px]"
                          :options="$options['newPriority']" :selected="old('priority', 'medium')" />
            </div>

            <div class="flex flex-col gap-4 sm:flex-row">
                <x-input name="target_date" type="date" label="Target completion date" required :min="now()->toDateString()" :value="$defaultTarget" width="w-full sm:w-[206px]" />
                <x-input name="notes" label="Additional notes" maxlength="500" placeholder="Optional" width="w-full sm:flex-1" />
            </div>

            <div class="flex items-start gap-2.5 rounded-md bg-[#eff6ff] p-3 text-[#2563eb]">
                <x-icon name="info" :size="17" class="mt-px" />
                <div class="leading-normal">
                    <p class="text-xs font-semibold">Verified report · Original evidence remains locked</p>
                    <p class="text-[11px]">Work order creation is recorded in the activity log.</p>
                </div>
            </div>

            <div class="flex justify-end gap-2.5">
                <button type="button" data-modal-close
                        class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
                <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Create Work Order</button>
            </div>
        </form>
    </dialog>
@endsection
