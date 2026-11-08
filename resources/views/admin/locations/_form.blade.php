@php
    /** @var \App\Models\Building|null $building */
    $id     = $building ? 'edit-location-' . $building->id : 'add-location';
    $reopen = ($openModal ?? null) === $id;
    $name   = $reopen ? old('name') : $building?->name;
    $active = $reopen ? (bool) old('is_active') : ($building?->is_active ?? true);
    $error  = $reopen ? $errors->first('name') : null;
@endphp

<x-modal :id="$id" :title="$building ? 'Edit location' : 'Add location'"
         :subtitle="$building ? $building->name : 'Create the building first, then add its rooms.'"
         :width="520" :open="$reopen">
    <form method="POST" action="{{ $building ? route('admin.locations.update', $building) : route('admin.locations.store') }}" class="flex flex-col gap-4">
        @csrf
        @if ($building) @method('PATCH') @endif
        <input type="hidden" name="_modal" value="{{ $id }}">

        <div>
            <label for="{{ $id }}-name" class="mb-[7px] block text-xs font-medium">Building name *</label>
            <input id="{{ $id }}-name" name="name" value="{{ $name }}" required maxlength="120" placeholder="e.g. Building 1"
                   class="h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30 {{ $error ? 'border-red-400' : 'border-line' }}">
            @if ($error) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $error }}</p> @endif
        </div>

        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" class="size-3.5 accent-brand" @checked($active)> Active (shown in new report forms)</label>

        <div class="flex justify-end gap-2.5">
            <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
            <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">{{ $building ? 'Save Changes' : 'Add Location' }}</button>
        </div>
    </form>
</x-modal>
