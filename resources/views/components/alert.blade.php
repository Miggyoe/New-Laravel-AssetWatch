{{-- Inline notice (Figma "Alert"): blue = info, amber = warning/lock, teal = success, red = error --}}
@props(['tone' => 'blue', 'icon' => 'info', 'title', 'body' => null])
@php
    $classes = [
        'blue'  => 'bg-[#eff6ff] text-[#2563eb]',
        'amber' => 'bg-[#fff7e0] text-[#a16207]',
        'teal'  => 'bg-[#e8f7f3] text-[#0f9d8a]',
        'red'   => 'bg-[#fef2f2] text-[#b91c1c]',
    ][$tone] ?? 'bg-[#eff6ff] text-[#2563eb]';
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-2.5 rounded-md p-3 $classes"]) }}>
    <x-icon :name="$icon" :size="17" class="mt-px" />
    <div class="min-w-0 flex-1 leading-[1.5]">
        <p class="text-xs font-semibold">{{ $title }}</p>
        @if ($body)
            <p class="text-[11px] font-normal">{{ $body }}</p>
        @endif
    </div>
</div>
