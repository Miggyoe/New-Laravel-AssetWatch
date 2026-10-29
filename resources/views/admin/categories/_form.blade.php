@php
    /** @var \App\Models\Category|null $category */
    $id     = $category ? 'edit-category-' . $category->id : 'add-category';
    $reopen = ($openModal ?? null) === $id;
    $val    = fn (string $key) => $reopen ? old($key) : $category?->{$key};
    $input  = 'h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30';
    $err    = fn (string $key) => $reopen ? $errors->first($key) : null;
    $active = $reopen ? (bool) old('is_active') : ($category?->is_active ?? true);
@endphp

<x-modal :id="$id" :title="$category ? 'Edit category' : 'Add category'"
         :subtitle="$category ? $category->name : 'Group assets and incidents so reports can be analysed by type.'"
         :width="520" :open="$reopen">
    <form method="POST" action="{{ $category ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="flex flex-col gap-4">
        @csrf
        @if ($category) @method('PATCH') @endif
        <input type="hidden" name="_modal" value="{{ $id }}">

        <div>
            <label for="{{ $id }}-name" class="mb-[7px] block text-xs font-medium">Category name *</label>
            <input id="{{ $id }}-name" name="name" value="{{ $val('name') }}" required maxlength="80" placeholder="e.g. Furniture"
                   class="{{ $input }} {{ $err('name') ? 'border-red-400' : 'border-line' }}">
            @if ($err('name')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('name') }}</p> @endif
        </div>

        <div>
            <label for="{{ $id }}-description" class="mb-[7px] block text-xs font-medium">Description</label>
            <input id="{{ $id }}-description" name="description" value="{{ $val('description') }}" maxlength="200" placeholder="Chairs, desks, cabinets and similar items"
                   class="{{ $input }} border-line">
        </div>

        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" class="size-3.5 accent-brand" @checked($active)> Active (shown in new report forms)</label>

        <div class="flex justify-end gap-2.5">
            <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
            <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">{{ $category ? 'Save Changes' : 'Add Category' }}</button>
        </div>
    </form>
</x-modal>
