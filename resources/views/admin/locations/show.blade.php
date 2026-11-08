@extends('layouts.admin')

@section('title', $building->name)
@section('breadcrumb', 'Campus Locations')

@section('content')
    @php
        $heads = ['Floor', 'Room code', 'Room name', 'Type', 'Reported incidents', 'Notes', 'Actions'];
        $pill  = 'inline-flex h-[23px] items-center whitespace-nowrap rounded-full px-2 text-[10px] font-semibold';
        $link  = 'font-medium text-brand hover:underline';
        $active = $building->is_active ?? true;
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px] leading-[1.45]">
            <h1 class="text-[26px] font-semibold">{{ $building->name }}</h1>
            <p class="text-xs text-muted">
                Rooms and details · {{ number_format($totals['rooms']) }} {{ \Illuminate\Support\Str::plural('room', $totals['rooms']) }} on {{ $totals['floors'] ?: 'no' }} {{ \Illuminate\Support\Str::plural('floor', $totals['floors']) }}
                · {{ number_format($totals['reports']) }} reported {{ \Illuminate\Support\Str::plural('incident', $totals['reports']) }}
                @unless ($active) · <span class="font-medium">Inactive</span> @endunless
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.locations.index') }}" class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Back to Campus Locations</a>
            <button type="button" data-modal-open="add-room" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Add Room</button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.locations.show', $building) }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        <div class="flex flex-wrap items-center gap-3 p-[18px]">
            <div class="relative w-full sm:w-[260px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search room code or name"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>
            <x-select name="type"  :options="$options['type']"  :selected="$filters['type']"  width="w-full sm:w-[160px]" />
            <x-select name="floor" :options="$options['floor']" :selected="$filters['floor']" width="w-full sm:w-[190px]" />
            <a href="{{ route('admin.locations.show', $building) }}" class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset filters</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] table-fixed text-left">
                <colgroup>
                    <col class="w-[150px]"><col class="w-[130px]"><col><col class="w-[120px]"><col class="w-[130px]"><col class="w-[210px]"><col class="w-[120px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>
                @if ($rooms->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($rooms as $room)
                            <tr class="h-12 border-b border-line last:border-b-0 hover:bg-canvas/60">
                                <td class="pl-4 pr-2 leading-[1.4]">{{ $room->floor ?: '—' }}</td>
                                <td class="px-2 font-medium">{{ $room->code ?: '—' }}</td>
                                <td class="px-2 py-1.5 leading-[1.4]">{{ $room->name }}</td>
                                <td class="px-2"><span class="{{ $pill }} {{ $room->typeBadge() }}">{{ $room->type ?: 'Other' }}</span></td>
                                <td class="px-2">{{ number_format($room->reports_count) }}</td>
                                <td class="px-2 leading-[1.4] text-muted">{{ $room->notes ?: '—' }}</td>
                                <td class="whitespace-nowrap px-2">
                                    <button type="button" data-modal-open="edit-room-{{ $room->id }}" class="{{ $link }}">Edit</button>
                                    <span class="text-brand"> · </span>
                                    <button type="submit" form="delete-room-{{ $room->id }}" class="font-medium text-[#b91c1c] hover:underline">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>

        @if ($rooms->isEmpty())
            <div class="flex min-h-[220px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">{{ $hasFilters ? 'No rooms match your filters' : 'No rooms yet' }}</p>
                <p class="text-xs text-muted">{{ $hasFilters ? 'Try another keyword or reset your filters.' : 'Add the rooms of this building so reports can point to them.' }}</p>
            </div>
        @else
            <p class="px-4 py-3.5 text-[11px] text-muted">Showing {{ number_format($rooms->count()) }} of {{ number_format($totals['rooms']) }} {{ \Illuminate\Support\Str::plural('room', $totals['rooms']) }}</p>
        @endif
    </form>

    <x-alert tone="blue" icon="info" title="Rooms with reports cannot be deleted"
             body="Existing reports and work orders keep their original room. To stop new reports for a whole building, deactivate the building instead." />

    {{-- Row forms live outside the filter form --}}
    @foreach ($rooms as $room)
        <form id="delete-room-{{ $room->id }}" method="POST" action="{{ route('admin.locations.rooms.destroy', [$building, $room]) }}" class="hidden"
              onsubmit="return confirm('Delete {{ addslashes($room->fullLabel()) }}? This cannot be undone.')">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    @include('admin.locations._room-form', ['room' => null])
    @foreach ($rooms as $room)
        @include('admin.locations._room-form', ['room' => $room])
    @endforeach
@endsection
