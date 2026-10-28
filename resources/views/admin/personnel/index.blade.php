@extends('layouts.admin')

@section('title', 'Maintenance Personnel')
@section('breadcrumb', 'Maintenance Personnel')

@section('content')
    @php
        $heads = ['Staff ID', 'Personnel', 'Email', 'Specialization', 'Active orders', 'Availability', 'Account', 'Actions'];
        $accountBadge = fn (bool $active) => $active ? 'bg-[#eaf7ee] text-[#15803d]' : 'bg-[#f5f7fb] text-[#64748b]';
        $pill = 'inline-flex h-[23px] items-center whitespace-nowrap rounded-full px-2 text-[10px] font-semibold';
        $editIds = $people->pluck('id');
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Maintenance Personnel</h1>
            <p class="text-xs text-muted">Manage technician accounts, specializations, availability, and assignments.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ number_format($total) }} personnel {{ \Illuminate\Support\Str::plural('record', $total) }}</p>
            <button type="button" data-modal-open="add-personnel" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Add Personnel</button>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Active personnel" :value="$stats['active']" :note="'Across ' . $stats['specialties'] . ' specializations'" icon="users" icon-class="text-brand" />
        <x-stat-card label="Available for assignment" :value="$stats['available']" :note="'Under ' . $stats['busy_threshold'] . ' open orders'" icon="user-check" icon-class="text-teal" />
        <x-stat-card label="Active work orders" :value="$stats['active_orders']" note="Assigned, accepted, in progress" icon="wrench" icon-class="text-brand" />
    </div>

    <form method="GET" action="{{ route('admin.personnel.index') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3 p-[18px]">
            <div class="relative w-full sm:w-[260px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search name, staff ID, or email"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>
            <x-select name="specialty"    :options="$options['specialty']"    :selected="$filters['specialty']"    width="w-full sm:w-[190px]" />
            <x-select name="availability" :options="$options['availability']" :selected="$filters['availability']" width="w-full sm:w-[160px]" />
            <x-select name="account"      :options="$options['account']"      :selected="$filters['account']"      width="w-full sm:w-[150px]" />
            <a href="{{ route('admin.personnel.index') }}" class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset</a>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] table-fixed text-left">
                <colgroup>
                    <col class="w-[96px]"><col class="w-[190px]"><col><col class="w-[190px]"><col class="w-[100px]">
                    <col class="w-[112px]"><col class="w-[92px]"><col class="w-[96px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>
                @if ($people->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($people as $person)
                            @php $availability = $person->availability(); @endphp
                            <tr class="h-[57px] border-b border-line last:border-b-0 hover:bg-canvas/60">
                                <td class="pl-4 pr-2 font-medium text-brand">{{ $person->staff_id }}</td>
                                <td class="px-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#e8f0fb] text-[11px] font-semibold text-navy">{{ $person->initial() }}</span>
                                        <span class="min-w-0 truncate font-medium">{{ $person->name }}</span>
                                    </div>
                                </td>
                                <td class="truncate px-2">{{ $person->email ?: '—' }}</td>
                                <td class="px-2 leading-[1.4]">{{ $person->specialization ?: $person->specialty }}</td>
                                <td class="px-2">{{ $person->active_orders_count }}</td>
                                <td class="px-2"><span class="{{ $pill }} {{ $availability->badge() }}">{{ $availability->label() }}</span></td>
                                <td class="px-2"><span class="{{ $pill }} {{ $accountBadge($person->is_active) }}">{{ $person->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap px-2 font-medium text-brand">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.personnel.index', array_merge(request()->query(), ['profile' => $person->id])) }}" class="hover:underline">Profile</a>
                                        <details data-menu class="relative">
                                            <summary class="flex cursor-pointer list-none items-center rounded p-0.5 hover:bg-canvas [&::-webkit-details-marker]:hidden" aria-label="More actions">
                                                <x-icon name="ellipsis" :size="16" />
                                            </summary>
                                            <div data-menu-panel class="absolute right-0 top-6 z-20 w-48 rounded-md border border-line bg-white py-1 text-[11px] font-normal text-ink shadow-lg">
                                                <button type="button" data-modal-open="edit-personnel-{{ $person->id }}" class="block w-full px-3 py-2 text-left hover:bg-canvas"
                                                        onclick="this.closest('details').removeAttribute('open')">Edit personnel</button>
                                                <button type="submit" form="toggle-available-{{ $person->id }}" class="block w-full px-3 py-2 text-left hover:bg-canvas">
                                                    {{ $person->is_available ? 'Mark as unavailable' : 'Mark as available' }}</button>
                                                <button type="submit" form="toggle-active-{{ $person->id }}" class="block w-full px-3 py-2 text-left hover:bg-canvas {{ $person->is_active ? 'text-[#b91c1c]' : '' }}">
                                                    {{ $person->is_active ? 'Deactivate account' : 'Activate account' }}</button>
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

        @if ($people->isEmpty())
            <div class="flex min-h-[260px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">{{ $hasFilters ? 'No personnel match your filters' : 'No maintenance personnel yet' }}</p>
                <p class="text-xs text-muted">{{ $hasFilters ? 'Try another keyword or reset your filters.' : 'Add a technician to start assigning work orders.' }}</p>
                @if ($hasFilters)
                    <a href="{{ route('admin.personnel.index') }}" class="mt-2 rounded-md bg-brand px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-blue-700">Reset filters</a>
                @endif
            </div>
        @else
            @include('admin.partials.pagination', ['paginator' => $people])
        @endif
    </form>

    {{-- Row switches (kept outside the filter form so the menu buttons can submit them) --}}
    @foreach ($people as $person)
        <form id="toggle-active-{{ $person->id }}" method="POST" action="{{ route('admin.personnel.toggle', [$person, 'active']) }}" class="hidden">@csrf</form>
        <form id="toggle-available-{{ $person->id }}" method="POST" action="{{ route('admin.personnel.toggle', [$person, 'available']) }}" class="hidden">@csrf</form>
    @endforeach

    {{-- Dialogs --}}
    @include('admin.personnel._form', ['technician' => null])
    @foreach ($people as $person)
        @include('admin.personnel._form', ['technician' => $person])
    @endforeach
    @if ($profile && ! $editIds->contains($profile->id))
        @include('admin.personnel._form', ['technician' => $profile])
    @endif

    {{-- Personnel profile (Figma: "Personnel profile modal") --}}
    @if ($profile)
        @php $pa = $profile->availability(); @endphp
        <x-modal id="personnel-profile" title="Personnel profile" subtitle="Staff contact, assignments, and repair history" :width="680" :open="true">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3.5">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-[#e8f0fb] text-xl font-semibold text-navy">{{ $profile->initial() }}</span>
                    <div class="leading-[1.45]">
                        <p class="text-base font-semibold">{{ $profile->name }}</p>
                        <p class="text-[11px] text-muted">{{ $profile->staff_id }} · {{ $profile->specialization ?: $profile->specialty }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <span class="{{ $pill }} {{ $accountBadge($profile->is_active) }}">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span>
                    <span class="{{ $pill }} {{ $pa->badge() }}">{{ $pa->label() }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:gap-6">
                <x-detail label="Institutional email" :value="$profile->email ?: '—'" />
                <x-detail label="Phone / Extension" :value="trim(($profile->phone ?: '—') . ($profile->extension ? ' · Ext. ' . $profile->extension : ''))" />
            </div>

            <div class="grid grid-cols-3 gap-3">
                @foreach ([['Active work orders', $profileData['active']], ['Completed repairs', $profileData['completed']], ['On-time completion', $profileData['on_time'] === null ? '—' : $profileData['on_time'] . '%']] as [$label, $number])
                    <div class="rounded-lg border border-line p-3.5">
                        <p class="text-[10px] text-muted">{{ $label }}</p>
                        <p class="text-[22px] font-semibold leading-[1.45]">{{ $number }}</p>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2.5">
                <p class="text-xs font-semibold">Assigned work orders &amp; recent repairs</p>
                @forelse ($profileData['orders'] as $order)
                    <div class="flex items-center justify-between gap-3 border-b border-line pb-2.5 text-[11px] last:border-b-0">
                        <div class="min-w-0">
                            <a href="{{ route('admin.work-orders.show', $order) }}" class="font-medium text-brand hover:underline">{{ $order->reference_no }}</a>
                            <span class="text-muted"> · {{ $order->title }} · {{ trim(($order->report?->location?->building?->short_name ?? '') . ' ' . ($order->report?->location?->name ?? '')) }}</span>
                        </div>
                        <x-status-badge :status="$order->status" />
                    </div>
                @empty
                    <p class="text-[11px] text-muted">No work orders yet.</p>
                @endforelse
            </div>

            <div class="flex flex-col gap-2">
                <p class="text-xs font-semibold">Recent activity</p>
                @forelse ($profileData['activity'] as $entry)
                    <p class="text-[11px] leading-[1.45] text-muted">{{ $entry->created_at->format('j M, g:i A') }} · {{ $entry->workOrder?->reference_no }} · {{ $entry->note }}</p>
                @empty
                    <p class="text-[11px] text-muted">No activity yet.</p>
                @endforelse
            </div>

            <div class="flex justify-end gap-2.5">
                <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Close</button>
                <button type="button" data-modal-open="edit-personnel-{{ $profile->id }}" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Edit Personnel</button>
            </div>
        </x-modal>
    @endif
@endsection
