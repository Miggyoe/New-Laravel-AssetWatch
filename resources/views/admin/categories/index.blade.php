@extends('layouts.admin')

@section('title', 'Asset Categories')
@section('breadcrumb', 'Asset Categories')

@section('content')
    @php
        $heads = ['Category name', 'Description', 'Active reports', 'Actions'];
        $link  = 'font-medium text-brand hover:underline';
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Asset Categories</h1>
            <p class="text-xs text-muted">Keep campus incidents organized with clear, consistent asset classifications.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('category', $total) }} · {{ number_format($activeReports) }} active reports</p>
            <button type="button" data-modal-open="add-category" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Add Category</button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.categories.index') }}"
          class="rounded-[10px] border border-line bg-white shadow-[0_2px_3px_rgba(23,54,93,0.02)]">

        <div class="flex flex-wrap items-start gap-3 p-[18px]">
            <div class="relative w-full sm:w-[316px]">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search category name or description"
                       class="h-10 w-full rounded-md border border-line bg-white pl-3.5 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink" aria-label="Search"><x-icon name="search" :size="16" /></button>
            </div>
            <p class="pt-1 text-[11px] text-muted">Active report counts exclude resolved and rejected reports.</p>
            @if ($filters['q'])
                <a href="{{ route('admin.categories.index') }}" class="pt-1 text-[11px] font-medium text-brand hover:underline">Clear search</a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] table-fixed text-left">
                <colgroup>
                    <col class="w-[235px]"><col><col class="w-[150px]"><col class="w-[165px]">
                </colgroup>
                <thead>
                    <tr class="h-[43px] bg-canvas text-[10px] font-semibold text-muted">
                        @foreach ($heads as $i => $head)
                            <th class="{{ $i === 0 ? 'pl-4 pr-2' : 'px-2' }} font-semibold">{{ $head }}</th>
                        @endforeach
                    </tr>
                </thead>
                @if ($categories->isNotEmpty())
                    <tbody class="text-[11px]">
                        @foreach ($categories as $category)
                            @php $active = $category->is_active ?? true; @endphp
                            <tr class="h-[52px] border-b border-line last:border-b-0 hover:bg-canvas/60 {{ $active ? '' : 'bg-canvas/50' }}">
                                <td class="pl-4 pr-2">
                                    <span class="text-[11px] font-medium {{ $active ? '' : 'text-muted' }}">{{ $category->name }}</span>
                                    @unless ($active)
                                        <span class="ml-1.5 inline-flex h-[23px] items-center rounded-full bg-[#f5f7fb] px-2 text-[10px] font-semibold text-[#64748b]">Inactive</span>
                                    @endunless
                                </td>
                                <td class="px-2 align-middle leading-[1.45] text-ink">{{ $category->description ?: '—' }}</td>
                                <td class="px-2 align-middle">
                                    <span class="inline-flex h-[23px] min-w-[28px] items-center justify-center rounded-full bg-[#eff6ff] px-2 text-[10px] font-semibold text-brand">{{ $category->active_reports_count }}</span>
                                </td>
                                <td class="whitespace-nowrap px-2 align-middle">
                                    <button type="button" data-modal-open="edit-category-{{ $category->id }}" class="text-[11px] font-medium text-brand hover:underline">Edit</button>
                                    <span class="text-brand"> · </span>
                                    <button type="submit" form="toggle-category-{{ $category->id }}" class="text-[11px] font-medium text-brand hover:underline">{{ $active ? 'Deactivate' : 'Reactivate' }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>

        @if ($categories->isEmpty())
            <div class="flex min-h-[240px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                <x-icon name="search-x" :size="30" class="text-muted" />
                <p class="text-base font-semibold leading-[1.45]">{{ $hasFilters ? 'No categories match your search' : 'No categories yet' }}</p>
                <p class="text-xs text-muted">{{ $hasFilters ? 'Try another keyword.' : 'Add a category so reports can be grouped by type.' }}</p>
            </div>
        @else
            @include('admin.partials.pagination', ['paginator' => $categories])
        @endif
    </form>

    <x-alert tone="blue" icon="info" title="One category per incident"
             body="Use a clear primary category. Historical category labels remain available for audit and analytics." />

    @foreach ($categories as $category)
        <form id="toggle-category-{{ $category->id }}" method="POST" action="{{ route('admin.categories.toggle', $category) }}" class="hidden">@csrf</form>
    @endforeach

    @include('admin.categories._form', ['category' => null])
    @foreach ($categories as $category)
        @include('admin.categories._form', ['category' => $category])
    @endforeach
@endsection
