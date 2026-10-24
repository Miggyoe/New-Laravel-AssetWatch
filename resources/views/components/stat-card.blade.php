@props(['label', 'value', 'note', 'icon', 'iconClass' => 'text-brand'])

<div class="relative flex min-w-0 flex-col gap-2 rounded-[10px] border border-line bg-white p-4">
    <p class="text-xs text-muted">{{ $label }}</p>
    <x-icon :name="$icon" :size="20" class="absolute right-[15px] top-3.5 {{ $iconClass }}" />
    <div class="flex items-end justify-between gap-2">
        <p class="text-[30px] font-semibold leading-[1.45]">{{ number_format($value) }}</p>
        <p class="text-[10px] text-muted">{{ $note }}</p>
    </div>
</div>
