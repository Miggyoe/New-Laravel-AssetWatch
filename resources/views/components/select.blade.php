{{-- Dropdown (Figma "Select field"). In a filter bar it submits the GET form on change;
     in a modal/form pass :submit="false" and a label. --}}
@props(['name', 'options', 'selected' => null, 'width' => 'w-full sm:w-[174px]', 'submit' => true, 'label' => null, 'required' => false, 'id' => null])
@php
    $fieldId = $id ?? 'f-' . $name . '-' . \Illuminate\Support\Str::random(4);
    $error   = $submit ? null : $errors->first($name); // filter dropdowns share names with form fields
@endphp

<div class="{{ $width }}">
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-[7px] block text-xs font-medium text-ink">{{ $label }}@if ($required) *@endif</label>
    @endif

    <div class="relative">
        <select id="{{ $fieldId }}" name="{{ $name }}"
                @if ($submit) onchange="this.form.submit()" @endif
                @if ($required) required @endif
                class="h-10 w-full appearance-none rounded-md border bg-white pl-3.5 pr-9 text-xs text-ink focus:outline-none focus:ring-2 focus:ring-brand/30 {{ $error ? 'border-red-400' : 'border-line' }}">
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
            @endforeach
        </select>
        <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink" />
    </div>

    @if ($error)
        <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $error }}</p>
    @endif
</div>
