{{-- MCC AssetWatch logo + wordmark. The logo is dark navy, so it always sits on a white tile. --}}
@props(['tile' => 'size-10'])
@php
    $brandLogo = collect(['svg', 'png', 'webp', 'jpg', 'jpeg'])
        ->map(fn ($ext) => ['images/logo-transparent.' . $ext, 'images/logotransparent.' . $ext, 'images/logo.' . $ext])
        ->flatten()
        ->first(fn ($path) => file_exists(public_path($path)));
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-[11px]']) }}>
    <div class="{{ $tile }} flex shrink-0 items-center justify-center rounded-lg bg-white p-1">
        <img src="{{ asset($brandLogo ?? 'images/logo-transparent.svg') }}" alt="MCC AssetWatch logo" class="size-full object-contain">
    </div>
    <div class="leading-[1.3] text-white">
        <p class="text-base font-bold">MCC AssetWatch</p>
        <p class="text-[9px] font-normal text-navy-text">CAMPUS FACILITIES PORTAL</p>
    </div>
</div>
