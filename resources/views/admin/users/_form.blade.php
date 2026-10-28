@php
    /** @var \App\Models\User|null $person */
    $id     = $person ? 'edit-user-' . $person->id : 'add-user';
    $reopen = ($openModal ?? null) === $id;
    $val    = fn (string $key, $default = null) => $reopen ? old($key) : ($person?->{$key} ?? $default);
    $input  = 'h-10 w-full rounded-md border bg-white px-3.5 text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/30';
    $err    = fn (string $key) => $reopen ? $errors->first($key) : null;
    $isSelf = $person && $person->is(auth()->user());
@endphp

<x-modal :id="$id" :title="$person ? 'Edit user' : 'Add user'"
         :subtitle="$person ? $person->email : 'They sign in with their MCC Microsoft account; no password is created.'"
         :width="520" :open="$reopen">
    <form method="POST" action="{{ $person ? route('admin.users.update', $person) : route('admin.users.store') }}" class="flex flex-col gap-4">
        @csrf
        @if ($person) @method('PATCH') @endif
        <input type="hidden" name="_modal" value="{{ $id }}">

        @unless ($person)
            @foreach ([['name', 'Full name', 'text'], ['email', 'MCC email', 'email']] as [$key, $label, $type])
                <div>
                    <label for="{{ $id }}-{{ $key }}" class="mb-[7px] block text-xs font-medium">{{ $label }} *</label>
                    <input id="{{ $id }}-{{ $key }}" type="{{ $type }}" name="{{ $key }}" value="{{ $val($key) }}" required
                           placeholder="{{ $key === 'email' ? 'name@' . ($domains[0] ?? 'mcc.edu.ph') : '' }}"
                           class="{{ $input }} {{ $err($key) ? 'border-red-400' : 'border-line' }}">
                    @if ($err($key)) <p class="mt-1 text-[10px] text-[#b91c1c]">{{ $err($key) }}</p> @endif
                </div>
            @endforeach
        @endunless

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="sm:w-[200px]">
                <label for="{{ $id }}-role" class="mb-[7px] block text-xs font-medium">Role *</label>
                <div class="relative">
                    <select id="{{ $id }}-role" name="role" @disabled($isSelf) class="{{ $input }} appearance-none border-line pr-9 disabled:bg-canvas disabled:text-muted">
                        @foreach ($options['roles'] as $value => $label)
                            <option value="{{ $value }}" @selected(($val('role', 'user')) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2" />
                </div>
                @if ($isSelf)
                    {{-- a disabled select is not submitted; keep the current role --}}
                    <input type="hidden" name="role" value="{{ $person->role }}">
                    <p class="mt-1 text-[10px] text-muted">You cannot change your own role.</p>
                @endif
            </div>
            <div class="flex-1">
                <label for="{{ $id }}-department" class="mb-[7px] block text-xs font-medium">Department / position</label>
                <input id="{{ $id }}-department" name="department" value="{{ $val('department') }}" maxlength="80" placeholder="e.g. Faculty, Facilities Office" class="{{ $input }} border-line">
            </div>
        </div>

        <div class="flex justify-end gap-2.5">
            <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
            <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">{{ $person ? 'Save Changes' : 'Add User' }}</button>
        </div>
    </form>
</x-modal>
