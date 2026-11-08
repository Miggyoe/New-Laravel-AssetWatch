@php
    /** @var \App\Models\Location|null $room */
    $id     = $room ? 'edit-room-' . $room->id : 'add-room';
    $reopen = ($openModal ?? null) === $id;
    $val    = fn (string $key) => $reopen ? old($key) : $room?->{$key};
    $input  = 'h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30';
    $err    = fn (string $key) => $reopen ? $errors->first($key) : null;
    $listId = 'floors-' . $id;
@endphp

<x-modal :id="$id" :title="$room ? 'Edit room' : 'Add room'" :subtitle="$room ? $room->fullLabel() : 'Room details for ' . $building->name" :width="560" :open="$reopen">
    <form method="POST"
          action="{{ $room ? route('admin.locations.rooms.update', [$building, $room]) : route('admin.locations.rooms.store', $building) }}"
          class="flex flex-col gap-4">
        @csrf
        @if ($room) @method('PATCH') @endif
        <input type="hidden" name="_modal" value="{{ $id }}">

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="sm:w-[170px]">
                <label for="{{ $id }}-code" class="mb-[7px] block text-xs font-medium">Room code</label>
                <input id="{{ $id }}-code" name="code" value="{{ $val('code') }}" maxlength="40" placeholder="e.g. MB101"
                       class="{{ $input }} {{ $err('code') ? 'border-red-400' : 'border-line' }}">
                @if ($err('code')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('code') }}</p> @endif
            </div>
            <div class="flex-1">
                <label for="{{ $id }}-name" class="mb-[7px] block text-xs font-medium">Room name *</label>
                <input id="{{ $id }}-name" name="name" value="{{ $val('name') }}" required maxlength="120" placeholder="e.g. MIS Office"
                       class="{{ $input }} {{ $err('name') ? 'border-red-400' : 'border-line' }}">
                @if ($err('name')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('name') }}</p> @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="sm:w-[200px]">
                <label for="{{ $id }}-type" class="mb-[7px] block text-xs font-medium">Type *</label>
                <div class="relative">
                    <select id="{{ $id }}-type" name="type" class="{{ $input }} appearance-none border-line pr-9">
                        @foreach ($options['types'] as $type)
                            <option value="{{ $type }}" @selected(($val('type') ?? 'Classroom') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2" />
                </div>
            </div>
            <div class="flex-1">
                <label for="{{ $id }}-floor" class="mb-[7px] block text-xs font-medium">Floor</label>
                <input id="{{ $id }}-floor" name="floor" value="{{ $val('floor') }}" list="{{ $listId }}" maxlength="80" placeholder="e.g. 1st Floor" class="{{ $input }} border-line">
                <datalist id="{{ $listId }}">
                    @foreach ($floors->merge(['1st Floor', '2nd Floor', '3rd Floor'])->unique() as $floor)
                        <option value="{{ $floor }}"></option>
                    @endforeach
                </datalist>
            </div>
        </div>

        <div>
            <label for="{{ $id }}-notes" class="mb-[7px] block text-xs font-medium">Notes</label>
            <input id="{{ $id }}-notes" name="notes" value="{{ $val('notes') }}" maxlength="200" placeholder="Optional detail, e.g. Service counter" class="{{ $input }} border-line">
        </div>

        <div class="flex justify-end gap-2.5">
            <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
            <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">{{ $room ? 'Save Changes' : 'Add Room' }}</button>
        </div>
    </form>
</x-modal>
