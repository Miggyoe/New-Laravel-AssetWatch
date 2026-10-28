{{-- Dialog (Figma: modal with heading, close button, 28px padding, 12px radius).
     Open with <button data-modal-open="{id}">; close with [data-modal-close]. --}}
@props(['id', 'title', 'subtitle' => null, 'width' => 560, 'open' => false])

<dialog id="{{ $id }}" data-modal @if ($open) data-autoopen @endif style="width: {{ $width }}px"
        class="m-auto max-h-[calc(100vh-2rem)] max-w-[calc(100vw-2rem)] overflow-y-auto rounded-xl bg-white p-0 text-ink shadow-[0_18px_60px_rgba(11,27,51,0.15)] backdrop:bg-[rgba(11,27,51,0.6)]">
    <div class="flex flex-col gap-5 p-7">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 flex-col gap-[5px] leading-[1.45]">
                <h2 class="text-xl font-semibold">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-xs text-muted">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" data-modal-close class="text-muted hover:text-ink" aria-label="Close"><x-icon name="x" :size="20" /></button>
        </div>

        {{ $slot }}
    </div>
</dialog>
