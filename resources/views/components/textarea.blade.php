@props(['name', 'label' => null, 'value' => null, 'required' => false, 'placeholder' => null, 'rows' => 3, 'maxlength' => null])
@php $error = $errors->first($name); @endphp

<div class="w-full">
    @if ($label)
        <label for="f-{{ $name }}" class="mb-[7px] block text-xs font-medium text-ink">{{ $label }}@if ($required) *@endif</label>
    @endif
    <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
              @if ($maxlength) maxlength="{{ $maxlength }}" @endif
              @if ($required) required @endif
              class="w-full resize-y rounded-md border bg-white p-3 text-xs leading-[1.45] text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30 {{ $error ? 'border-red-400' : 'border-line' }}">{{ old($name, $value) }}</textarea>
    @if ($error)
        <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $error }}</p>
    @endif
</div>
