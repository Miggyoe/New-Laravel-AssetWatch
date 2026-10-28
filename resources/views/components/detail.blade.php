{{-- Label above a value (Figma: 10px muted label, 12px medium value) --}}
@props(['label', 'value' => null, 'grow' => true])

<div {{ $attributes->merge(['class' => 'min-w-0 leading-[1.6]' . ($grow ? ' flex-1' : '')]) }}>
    <p class="text-[10px] text-muted">{{ $label }}</p>
    <p class="break-words text-xs font-medium">{{ $value ?? $slot }}</p>
</div>
