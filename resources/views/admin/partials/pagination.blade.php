@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $perPageOptions = collect($perPageOptions ?? [10, 25, 50])->mapWithKeys(fn ($n) => [$n => "$n / page"])->all();

    // 1 2 3 … current-1 current current+1 … last-1 last
    $pages = collect([1, 2, 3, $last - 1, $last, $current - 1, $current, $current + 1])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 px-4 py-[13px]">
    <div class="flex items-center gap-3.5">
        <p class="text-[11px] text-muted">Showing {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }}</p>
        <x-select name="per_page" :options="$perPageOptions" :selected="$perPage" width="w-[110px]" />
    </div>

    <nav class="flex items-center gap-2" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-300"><x-icon name="arrow-left" :size="14" /> Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="flex items-center gap-2 px-3 py-2 text-[13px] text-muted hover:text-ink"><x-icon name="arrow-left" :size="14" /> Previous</a>
        @endif

        @foreach ($pages as $i => $page)
            @if ($i > 0 && $page - $pages[$i - 1] > 1)
                <span class="px-2 text-[13px] text-muted">…</span>
            @endif

            @if ($page === $current)
                <span aria-current="page" class="flex size-8 items-center justify-center rounded-md bg-brand text-[13px] font-semibold text-white">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="flex size-8 items-center justify-center rounded-md text-[13px] text-ink hover:bg-canvas">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="flex items-center gap-2 px-3 py-2 text-[13px] text-ink hover:text-brand">Next <x-icon name="arrow-right" :size="14" /></a>
        @else
            <span class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-300">Next <x-icon name="arrow-right" :size="14" /></span>
        @endif
    </nav>
</div>
