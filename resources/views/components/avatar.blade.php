{{-- Profile photo, or the first letter of the name when there is none. --}}
@props(['user', 'size' => 32])
@php
    $url     = $user?->avatarUrl();
    $initial = mb_strtoupper(mb_substr((string) ($user?->name ?? '?'), 0, 1));
@endphp

@if ($url)
    <img src="{{ $url }}" alt="{{ $user->name }}" width="{{ $size }}" height="{{ $size }}" loading="lazy"
         style="width: {{ $size }}px; height: {{ $size }}px"
         {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <span style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ max(10, round($size * 0.34)) }}px"
          {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-[#e8f0fb] font-semibold text-navy']) }}>{{ $initial }}</span>
@endif
