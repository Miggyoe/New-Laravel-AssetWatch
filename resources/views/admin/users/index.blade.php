@extends('layouts.admin')

@section('title', 'User Management')
@section('breadcrumb', 'User Management')

@section('content')
    @php
        $heads = ['User', 'Email', 'Department', 'Role', 'Status', 'Last sign-in', 'Reports', 'Actions'];
        $pill  = 'inline-flex h-[23px] items-center whitespace-nowrap rounded-full px-2 text-[10px] font-semibold';
        $roleBadge   = fn (string $role) => $role === 'admin' ? 'bg-[#f3eeff] text-[#7c3aed]' : 'bg-[#f5f7fb] text-[#64748b]';
        $statusBadge = fn (bool $active) => $active ? 'bg-[#eaf7ee] text-[#15803d]' : 'bg-[#f5f7fb] text-[#64748b]';
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">User Management</h1>
            <p class="text-xs text-muted">Control who can sign in, what role they have, and which accounts are active.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ number_format($stats['total']) }} {{ \Illuminate\Support\Str::plural('user', $stats['total']) }}</p>
            <button type="button" data-modal-open="add-user" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Add User</button>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-stat-card label="Total users"    :value="$stats['total']"  note="All accounts"          icon="users"        icon-class="text-brand" />
        <x-stat-card label="Administrators" :value="$stats['admins']" note="Full portal access"    icon="shield-check" icon-class="text-[#7c3aed]" />
        <x-stat-card label="Active"         :value="$stats['active']" note="Can sign in"           icon="circle-check" icon-class="text-teal" />
        <x-stat-card label="Signed in"      :value="$stats['recent']" note="Last 30 days"          icon="history"      icon-class="text-brand" />
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        <div class="flex flex-wrap items-center gap-3 p-[18px]">
            <div class="relative w-full sm:w-[280px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search name, email, or department"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>
            <x-select name="role"   :options="$options['role']"   :selected="$filters['role']"   width="w-full sm:w-[160px]" />
            <x-select name="status" :options="$options['status']" :selected="$filters['status']" width="w-full sm:w-[160px]" />
            <a href="{{ route('admin.users.index') }}" class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Reset</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] table-fixed text-left">
                <colgroup>
                    <col class="w-[200px]"><col><col class="w-[140px]"><col class="w-[120px]"><col class="w-[84px]">
                    <col class="w-[110px]"><col class="w-[72px]"><col class="w-[76px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>
                @if ($users->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($users as $person)
                            @php $active = $person->is_active ?? true; $isSelf = $person->is(auth()->user()); @endphp
                            <tr class="h-[52px] border-b border-line last:border-b-0 hover:bg-canvas/60">
                                <td class="pl-4 pr-2">
                                    <div class="flex items-center gap-2.5">
                                        @if ($person->avatar)
                                            <img src="{{ $person->avatar }}" alt="" class="size-8 shrink-0 rounded-full object-cover">
                                        @else
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#e8f0fb] text-[11px] font-semibold text-navy">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                                        @endif
                                        <span class="min-w-0 truncate font-medium">{{ $person->name }}@if ($isSelf) <span class="font-normal text-muted">(you)</span>@endif</span>
                                    </div>
                                </td>
                                <td class="truncate px-2">{{ $person->email }}</td>
                                <td class="truncate px-2">{{ $person->department ?: '—' }}</td>
                                <td class="px-2"><span class="{{ $pill }} {{ $roleBadge($person->role) }}">{{ $options['roles'][$person->role] ?? ucfirst($person->role) }}</span></td>
                                <td class="px-2"><span class="{{ $pill }} {{ $statusBadge($active) }}">{{ $active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap px-2 text-muted">{{ $person->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-2">{{ $person->reports_count }}</td>
                                <td class="whitespace-nowrap px-2">
                                    <details data-menu class="relative">
                                        <summary class="flex w-fit cursor-pointer list-none items-center rounded p-0.5 text-brand hover:bg-canvas [&::-webkit-details-marker]:hidden" aria-label="Actions for {{ $person->name }}">
                                            <x-icon name="ellipsis" :size="16" />
                                        </summary>
                                        <div data-menu-panel class="absolute right-0 top-6 z-20 w-48 rounded-md border border-line bg-white py-1 text-[11px] font-normal text-ink shadow-lg">
                                            <button type="button" data-modal-open="edit-user-{{ $person->id }}" class="block w-full px-3 py-2 text-left hover:bg-canvas"
                                                    onclick="this.closest('details').removeAttribute('open')">Edit role &amp; department</button>
                                            @unless ($isSelf)
                                                <button type="submit" form="toggle-user-{{ $person->id }}"
                                                        class="block w-full px-3 py-2 text-left hover:bg-canvas {{ $active ? 'text-[#b91c1c]' : '' }}">{{ $active ? 'Deactivate account' : 'Activate account' }}</button>
                                            @endunless
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>

        @if ($users->isEmpty())
            <div class="flex min-h-[260px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">No users match your filters</p>
                <p class="text-xs text-muted">Try another keyword or reset your filters.</p>
                @if ($hasFilters)
                    <a href="{{ route('admin.users.index') }}" class="mt-2 rounded-md bg-brand px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-blue-700">Reset filters</a>
                @endif
            </div>
        @else
            @include('admin.partials.pagination', ['paginator' => $users])
        @endif
    </form>

    <p class="flex items-center gap-2 text-[11px] text-muted"><x-icon name="shield-check" :size="14" />Accounts listed in MCC_ADMIN_EMAILS become administrators each time they sign in. Role and status changes are recorded in the activity log.</p>

    @foreach ($users as $person)
        <form id="toggle-user-{{ $person->id }}" method="POST" action="{{ route('admin.users.toggle', $person) }}" class="hidden">@csrf</form>
    @endforeach

    @include('admin.users._form', ['person' => null])
    @foreach ($users as $person)
        @include('admin.users._form', ['person' => $person])
    @endforeach
@endsection
