@php
    /** @var \App\Models\Technician|null $technician */
    $id     = $technician ? 'edit-personnel-' . $technician->id : 'add-personnel';
    $action = $technician ? route('admin.personnel.update', $technician) : route('admin.personnel.store');
    $reopen = ($openModal ?? null) === $id;   // only this dialog re-uses the submitted values after a validation error
    $val    = fn (string $key, $default = null) => $reopen ? old($key) : ($technician?->{$key} ?? $default);
    $input  = 'h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30';
    $err    = fn (string $key) => $reopen ? $errors->first($key) : null;
@endphp

<x-modal :id="$id" :title="$technician ? 'Edit personnel' : 'Add personnel'"
         :subtitle="$technician ? $technician->staff_id . ' · ' . $technician->name : 'A staff ID is assigned automatically.'"
         :width="560" :open="$reopen">
    <form method="POST" action="{{ $action }}" class="flex flex-col gap-4">
        @csrf
        @if ($technician) @method('PATCH') @endif
        <input type="hidden" name="_modal" value="{{ $id }}">

        @foreach ([['name', 'Full name', 'text', true], ['email', 'Institutional email', 'email', true]] as [$key, $label, $type, $required])
            <div>
                <label for="{{ $id }}-{{ $key }}" class="mb-[7px] block text-xs font-medium">{{ $label }} *</label>
                <input id="{{ $id }}-{{ $key }}" type="{{ $type }}" name="{{ $key }}" value="{{ $val($key) }}" required
                       class="{{ $input }} {{ $err($key) ? 'border-red-400' : 'border-line' }}">
                @if ($err($key)) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err($key) }}</p> @endif
            </div>
        @endforeach

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <label for="{{ $id }}-phone" class="mb-[7px] block text-xs font-medium">Phone</label>
                <input id="{{ $id }}-phone" name="phone" value="{{ $val('phone') }}" maxlength="30" class="{{ $input }} border-line">
            </div>
            <div class="sm:w-[120px]">
                <label for="{{ $id }}-extension" class="mb-[7px] block text-xs font-medium">Extension</label>
                <input id="{{ $id }}-extension" name="extension" value="{{ $val('extension') }}" maxlength="20" class="{{ $input }} border-line">
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="sm:w-[170px]">
                <label for="{{ $id }}-specialty" class="mb-[7px] block text-xs font-medium">Specialty *</label>
                <input id="{{ $id }}-specialty" name="specialty" value="{{ $val('specialty') }}" required maxlength="60" placeholder="e.g. Carpentry"
                       class="{{ $input }} {{ $err('specialty') ? 'border-red-400' : 'border-line' }}">
                @if ($err('specialty')) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err('specialty') }}</p> @endif
            </div>
            <div class="flex-1">
                <label for="{{ $id }}-specialization" class="mb-[7px] block text-xs font-medium">Specialization</label>
                <input id="{{ $id }}-specialization" name="specialization" value="{{ $val('specialization') }}" maxlength="150" placeholder="e.g. Carpentry &amp; furniture repair"
                       class="{{ $input }} border-line">
            </div>
        </div>

        <div class="flex flex-wrap gap-6 text-xs">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" class="size-3.5 accent-brand"
                   @checked($reopen ? old('is_active') : ($technician?->is_active ?? true))> Account active</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_available" value="1" class="size-3.5 accent-brand"
                   @checked($reopen ? old('is_available') : ($technician?->is_available ?? true))> Available for assignments</label>
        </div>

        <div class="flex justify-end gap-2.5">
            <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
            <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">{{ $technician ? 'Save Changes' : 'Add Personnel' }}</button>
        </div>
    </form>
</x-modal>
