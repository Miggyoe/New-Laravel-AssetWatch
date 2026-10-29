@extends('layouts.admin')

@section('title', 'Campus Locations')
@section('breadcrumb', 'Campus Locations')

@section('content')
    @php
        $heads = ['Building name', 'Building code', 'Rooms', 'Reported incidents', 'Status', 'Actions'];
        $pill  = 'inline-flex h-[23px] items-center whitespace-nowrap rounded-full px-2 text-[10px] font-semibold';
        $link  = 'font-medium text-brand hover:underline';
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Campus Locations</h1>
            <p class="text-xs text-muted">Organize buildings and rooms for accurate facility reporting.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">
                {{ number_format($totals['buildings']) }} {{ \Illuminate\Support\Str::plural('building', $totals['buildings']) }} · {{ number_format($totals['rooms']) }} {{ \Illuminate\Support\Str::plural('room', $totals['rooms']) }}
            </p>
            <button type="button" data-modal-open="add-location" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Add Location</button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.locations.index') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3 p-[18px]">
            <div class="relative w-full sm:w-[285px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search building name or code"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>
            <x-select name="status" :options="$options['status']" :selected="$filters['status']" width="w-full sm:w-[180px]" />
            <a href="{{ route('admin.locations.index') }}" class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset filters</a>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] table-fixed text-left">
                <colgroup>
                    <col><col class="w-[150px]"><col class="w-[110px]"><col class="w-[160px]"><col class="w-[110px]"><col class="w-[160px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>
                @if ($buildings->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($buildings as $building)
                            @php $active = $building->is_active ?? true; @endphp
                            <tr class="h-[67px] border-b border-line last:border-b-0 hover:bg-canvas/60">
                                <td class="pl-4 pr-2">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-6 shrink-0 items-center justify-center rounded bg-[#e8f0fb] text-navy"><x-icon name="building-2" :size="14" /></span>
                                        <span class="min-w-0 truncate text-xs font-medium">{{ $building->name }}</span>
                                    </div>
                                </td>
                                <td class="px-2">{{ $building->code }}</td>
                                <td class="px-2">{{ number_format($building->locations_count) }}</td>
                                <td class="px-2">{{ number_format($building->reports_count) }}</td>
                                <td class="px-2">
                                    <span class="{{ $pill }} {{ $active ? 'bg-[#eaf7ee] text-[#15803d]' : 'bg-[#f5f7fb] text-[#64748b]' }}">{{ $active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-2">
                                    <button type="button" data-modal-open="edit-location-{{ $building->id }}" class="{{ $link }}">Edit</button>
                                    <span class="text-brand"> · </span>
                                    <button type="submit" form="toggle-location-{{ $building->id }}" class="{{ $link }}">{{ $active ? 'Deactivate' : 'Reactivate' }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>

        @if ($buildings->isEmpty())
            <div class="flex min-h-[240px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">{{ $hasFilters ? 'No locations match your filters' : 'No locations yet' }}</p>
                <p class="text-xs text-muted">{{ $hasFilters ? 'Try another keyword or reset your filters.' : 'Add a building and its rooms so reports can point to them.' }}</p>
                @if ($hasFilters)
                    <a href="{{ route('admin.locations.index') }}" class="mt-2 rounded-md bg-brand px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-blue-700">Reset filters</a>
                @endif
            </div>
        @else
            @include('admin.partials.pagination', ['paginator' => $buildings])
        @endif
    </form>

    <x-alert tone="blue" icon="info" title="Deactivation preserves historical reports"
             body="Inactive locations are hidden from new report forms. Existing reports and work orders retain their original location." />

    @foreach ($buildings as $building)
        <form id="toggle-location-{{ $building->id }}" method="POST" action="{{ route('admin.locations.toggle', $building) }}" class="hidden">@csrf</form>
    @endforeach

    @include('admin.locations._form', ['building' => null])
    @foreach ($buildings as $building)
        @include('admin.locations._form', ['building' => $building])
    @endforeach
@endsection
