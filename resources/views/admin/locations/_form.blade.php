@php
    /** @var \App\Models\Building|null $building */
    $id     = $building ? 'edit-location-' . $building->id : 'add-location';
    $reopen = ($openModal ?? null) === $id;
    $input  = 'h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30';
    $err    = fn (string $key) => $reopen ? $errors->first($key) : null;

    $value = function (string $key) use ($reopen, $building) {
        if ($reopen) {
            return old($key);
        }

        return match ($key) {
            'rooms' => $building ? $building->locations->pluck('name')->sort(SORT_NATURAL | SORT_FLAG_CASE)->implode("\n") : '',
            default => $building?->{$key},
        };
    };
    $active = $reopen ? (bool) old('is_active') : ($building?->is_active ?? true);
@endphp

<x-modal :id="$id" :title="$building ? 'Edit location' : 'Add location'"
         :subtitle="$building ? $building->name . ' · ' . $building->code : 'A building and the rooms people can report issues in.'"
         :width="560" :open="$reopen">
    <form method="POST" action="{{ $building ? route('admin.locations.update', $building) : route('admin.locations.store') }}" class="flex flex-col gap-4">
        @csrf
        @if ($building) @method('PATCH') @endif
        <input type="hidden" name="_modal" value="{{ $id }}">

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <label for="{{ $id }}-name" class="mb-[7px] block text-xs font-medium">Building name *</label>
                <input id="{{ $id }}-name" name="name" value="{{ $value('name') }}" required maxlength="120" placeholder="e.g. Academic Building"
                       class="{{ $input }} {{ $err('name') ? 'border-red-400' : 'border-line' }}">
                @if ($err('name')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('name') }}</p> @endif
            </div>
            <div class="sm:w-[130px]">
                <label for="{{ $id }}-code" class="mb-[7px] block text-xs font-medium">Building code *</label>
                <input id="{{ $id }}-code" name="code" value="{{ $value('code') }}" required maxlength="12" placeholder="ACAD"
                       class="{{ $input }} uppercase {{ $err('code') ? 'border-red-400' : 'border-line' }}">
                @if ($err('code')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('code') }}</p> @endif
            </div>
        </div>

        <div>
            <label for="{{ $id }}-rooms" class="mb-[7px] block text-xs font-medium">Rooms <span class="font-normal text-muted">(one per line)</span></label>
            <textarea id="{{ $id }}-rooms" name="rooms" rows="6"
                      placeholder="101&#10;102&#10;Computer Lab 1"
                      class="w-full resize-y rounded-md border bg-white p-3 text-xs leading-[1.5] text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30 {{ $err('rooms') ? 'border-red-400' : 'border-line' }}">{{ $value('rooms') }}</textarea>
            @if ($err('rooms')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('rooms') }}</p> @endif
            @if ($building)
                <p class="mt-1 text-[10px] text-muted">Removing a room from this list deletes it, unless reports already refer to it.</p>
            @endif
        </div>

        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" class="size-3.5 accent-brand" @checked($active)> Active (shown in new report forms)</label>

        <div class="flex justify-end gap-2.5">
            <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
            <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">{{ $building ? 'Save Changes' : 'Add Location' }}</button>
        </div>
    </form>
</x-modal>
