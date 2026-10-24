{{-- MCC AssetWatch logo + app name. Sizes are inline on purpose so the logo can never blow up
     if the Tailwind build is stale. The logo is dark navy, so it always sits on a white tile. --}}
@props(['size' => 38])
<div {{ $attributes->merge(['style' => 'display:flex;align-items:center;gap:11px;min-width:0;']) }}>
    <div style="display:flex;flex:0 0 auto;align-items:center;justify-content:center;width:{{ $size }}px;height:{{ $size }}px;padding:3px;background:#fff;border-radius:8px;">
        <img src="{{ asset('images/logo.png') }}" alt="MCC AssetWatch logo" width="{{ $size }}" height="{{ $size }}"
             style="display:block;width:100%;height:100%;object-fit:contain;">
    </div>
    <div style="min-width:0;line-height:1.3;white-space:nowrap;">
        <p style="margin:0;font-size:16px;font-weight:700;color:#fff;">MCC AssetWatch</p>
        <p style="margin:0;font-size:9px;font-weight:400;color:#c4d2e3;">CAMPUS FACILITIES PORTAL</p>
    </div>
</div>
