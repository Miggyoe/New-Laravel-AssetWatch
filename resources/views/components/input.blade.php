@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'placeholder' => null, 'width' => 'w-full', 'min' => null, 'maxlength' => null])
@php $error = $errors->first($name); @endphp

<div class="{{ $width }}">
    @if ($label)
        <label for="f-{{ $name }}" class="mb-[7px] block text-xs font-medium text-ink">{{ $label }}@if ($required) *@endif</label>
    @endif
    <input id="f-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}"
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @if ($min) min="{{ $min }}" @endif
           @if ($maxlength) maxlength="{{ $maxlength }}" @endif
           @if ($required) required @endif
           class="h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30 {{ $error ? 'border-red-400' : 'border-line' }}">
    @if ($error)
        <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $error }}</p>
    @endif
</div>
