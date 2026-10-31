@extends('layouts.admin')

@section('title', 'Settings')
@section('breadcrumb', 'Settings')

@section('content')
    @php
        $card  = 'scroll-mt-6 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]';
        $input = 'h-10 w-full rounded-md border border-line bg-white px-3.5 text-xs text-ink focus:outline-none focus:ring-2 focus:ring-brand/30';
        $label = 'mb-[7px] block text-xs font-medium';
        $nav   = [
            ['Administrator profile', '#profile'],
            ['Account management',    '#roles'],
            ['Roles & permissions',   '#roles'],
            ['Notifications',         '#notifications'],
            ['Campus information',    '#campus'],
            ['System configuration',  '#system'],
        ];
        $permissions = [
            ['Verify / reject reports',               'Allowed', 'Not allowed'],
            ['Create / reassign work orders',         'Allowed', 'Not allowed'],
            ['Update repair progress & evidence',     'Allowed', 'Assigned jobs only'],
            ['Manage personnel & configuration',      'Allowed', 'Not allowed'],
            ['View original evidence',                'All reports', 'Assigned jobs only'],
        ];
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col gap-[5px]">
            <h1 class="text-[26px] font-semibold leading-[1.45]">Settings</h1>
            <p class="text-xs text-muted">Manage your profile, access policies, and campus preferences.</p>
        </div>
        <button type="submit" form="settings-form" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Save Changes</button>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[148px_minmax(0,1fr)]">
        {{-- Section navigation --}}
        <aside class="flex flex-col gap-3 self-start lg:sticky lg:top-6">
            <nav class="flex flex-row flex-wrap gap-1 lg:flex-col">
                @foreach ($nav as $i => [$text, $href])
                    <a href="{{ $href }}" data-settings-link
                       class="flex h-9 items-center rounded-md px-3 text-xs {{ $i === 0 ? 'bg-[#eff6ff] font-medium text-brand' : 'text-slate-600 hover:bg-white' }}">{{ $text }}</a>
                @endforeach
            </nav>
            <p class="hidden px-1 text-[10px] leading-[1.45] text-muted lg:block">Administrator-only configuration. Technicians manage their own profile and task notifications.</p>
        </aside>

        <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}" class="min-w-0">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Administrator profile --}}
                <section id="profile" class="flex flex-col gap-3.5 {{ $card }}">
                    <h2 class="text-[15px] font-semibold leading-[1.45]">Administrator profile</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <x-input name="first_name" label="First name" :value="$firstName" required maxlength="60" />
                        <x-input name="last_name" label="Last name" :value="$lastName" maxlength="60" />
                    </div>
                    <div>
                        <label class="{{ $label }}">Institutional email</label>
                        <input type="email" value="{{ $user->email }}" disabled class="{{ $input }} cursor-not-allowed bg-canvas text-muted">
                    </div>
                    <a href="https://mysignins.microsoft.com/security-info" target="_blank" rel="noopener noreferrer" class="w-fit text-[11px] font-medium text-brand hover:underline">Change password →</a>
                </section>

                {{-- Campus information --}}
                <section id="campus" class="flex flex-col gap-3.5 {{ $card }}">
                    <h2 class="text-[15px] font-semibold leading-[1.45]">Campus information</h2>
                    <x-input name="institution" label="Institution" :value="$campus['institution']" required maxlength="120" />
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $label }}">Timezone</label>
                            <div class="relative">
                                <select disabled class="{{ $input }} cursor-not-allowed appearance-none bg-canvas pr-9 text-muted"><option>{{ $campus['timezone'] }}</option></select>
                                <x-icon name="chevron-down" :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted" />
                            </div>
                        </div>
                        <x-input name="helpdesk_extension" label="Helpdesk extension" :value="$campus['extension']" maxlength="20" />
                    </div>
                    <p class="text-[10px] leading-[1.45] text-muted">Campus details appear in exports and system notices. The timezone follows APP_TIMEZONE in the server configuration.</p>
                </section>

                {{-- Roles & permissions --}}
                <section id="roles" class="flex flex-col gap-3.5 md:col-span-2 {{ $card }}">
                    <div class="leading-[1.45]">
                        <h2 class="text-[15px] font-semibold">Roles &amp; permissions</h2>
                        <p class="text-[10px] text-muted">Access is assigned by authorized administrators.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] text-left">
                            <thead>
                                <tr class="h-[42px] bg-canvas text-[10px] font-semibold text-muted">
                                    <th class="w-[44%] pl-4 font-semibold">Permission</th>
                                    <th class="px-2 font-semibold">Administrator</th>
                                    <th class="px-2 font-semibold">Technician</th>
                                </tr>
                            </thead>
                            <tbody class="text-[11px]">
                                @foreach ($permissions as [$permission, $admin, $technician])
                                    <tr class="h-[34px] border-b border-line last:border-b-0">
                                        <td class="pl-4 pr-2">{{ $permission }}</td>
                                        <td class="px-2">{{ $admin }}</td>
                                        <td class="px-2">{{ $technician }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <p class="text-[10px] text-muted">
                            {{ $counts['maintenance'] }} maintenance {{ \Illuminate\Support\Str::plural('account', $counts['maintenance']) }} · {{ $counts['admins'] }} {{ \Illuminate\Support\Str::plural('administrator', $counts['admins']) }}
                        </p>
                        <a href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}"
                           class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Manage Accounts</a>
                    </div>
                </section>

                {{-- Notification preferences --}}
                <section id="notifications" class="flex flex-col gap-3.5 {{ $card }}">
                    <h2 class="text-[15px] font-semibold leading-[1.45]">Notification preferences</h2>
                    <div class="flex flex-col gap-3">
                        @foreach ($notifications as $key => $item)
                            <label class="flex items-center gap-2.5 text-sm">
                                <input type="checkbox" name="notify[{{ $key }}]" value="1" class="size-4 accent-ink" @checked(old('_token') ? old("notify.$key") : $item['on'])>
                                {{ $item['label'] }}
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- System configuration --}}
                <section id="system" class="flex flex-col gap-3.5 {{ $card }}">
                    <h2 class="text-[15px] font-semibold leading-[1.45]">System configuration</h2>
                    <x-select name="target_days" label="Default target completion" :submit="false" width="w-full"
                              :options="$targetOptions" :selected="old('target_days', $system['target_days'])" />
                    <x-input name="evidence_limit" label="Evidence attachment limit" :value="$system['evidence']" maxlength="80" />
                    <p class="text-[10px] leading-[1.45] text-muted">Google Drive links are shown as evidence attachments. No storage credentials are displayed.</p>
                </section>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('[data-settings-link]').forEach((link) => link.addEventListener('click', () => {
            document.querySelectorAll('[data-settings-link]').forEach((l) => l.classList.remove('bg-[#eff6ff]', 'font-medium', 'text-brand'));
            link.classList.add('bg-[#eff6ff]', 'font-medium', 'text-brand');
        }));
    </script>
@endsection
