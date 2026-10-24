{{-- Filter dropdown (Figma "Filter" select field). Submits the surrounding GET form on change. --}}
@props(['name', 'options', 'selected' => null, 'width' => 'w-full sm:w-[174px]', 'submit' => true])

<div class="relative {{ $width }}">
    <select name="{{ $name }}"
            @if ($submit) onchange="this.form.submit()" @endif
            class="h-10 w-full appearance-none rounded-md border border-line bg-white pl-3.5 pr-9 text-xs text-ink focus:outline-none focus:ring-2 focus:ring-brand/30">
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink" />
</div>
