@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    @php
        $error  = session('error');
        $denied = ($unauthorized ?? false) || $error;
        $domain = config('mcc.allowed_domains.0', 'mcc.edu.ph');
    @endphp

    <div class="w-full max-w-[420px]">
        {{-- Sign in heading --}}
        <div class="flex flex-col gap-2.5 leading-[1.45]">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-teal">Campus Administration</p>
            <h1 class="text-[30px] font-semibold text-ink">Welcome back</h1>
            <p class="text-sm text-muted">Sign in to your MCC AssetWatch account.</p>
        </div>

        {{-- Unauthorized / failed sign-in --}}
        @if ($denied)
            <div role="alert" class="mt-5 flex items-start gap-2.5 rounded-md bg-[#fef2f2] p-3 text-[#b91c1c]">
                <x-icon name="circle-alert" :size="16" class="mt-px" />
                <div class="leading-normal">
                    <p class="text-xs font-semibold">Unauthorized Access</p>
                    <p class="text-[11px]">{{ $error ?: 'Your account is not authorized to access MCC AssetWatch.' }}</p>
                </div>
            </div>
        @endif

        {{-- Microsoft sign-in --}}
        <div class="mt-7 flex flex-col gap-3.5">
            <a href="{{ route('microsoft.redirect') }}"
               onclick="this.classList.add('pointer-events-none','opacity-80'); this.querySelector('[data-label]').textContent = 'Redirecting to Microsoft…';"
               class="flex h-11 items-center justify-center gap-2.5 rounded-md bg-brand px-4 text-xs font-medium text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:ring-offset-2">
                <span class="flex size-5 items-center justify-center rounded-sm bg-white">
                    <svg width="12" height="12" viewBox="0 0 21 21" aria-hidden="true">
                        <rect width="10" height="10" fill="#f25022"/><rect x="11" width="10" height="10" fill="#7fba00"/>
                        <rect y="11" width="10" height="10" fill="#00a4ef"/><rect x="11" y="11" width="10" height="10" fill="#ffb900"/>
                    </svg>
                </span>
                <span data-label>Sign in with Microsoft</span>
            </a>

            <p class="text-center text-[11px] text-muted">
                Use your official <span class="font-medium text-ink">{{ '@' . $domain }}</span> Microsoft account.
            </p>
        </div>

        {{-- Account guidance --}}
        <div class="mt-5 flex flex-col items-center gap-2.5 text-center leading-[1.45] text-muted">
            <p class="text-xs">Need access? Contact your campus administrator.</p>
            <p class="text-[11px]">Accounts are issued by MCC. Public registration is not available.</p>
        </div>

        {{-- Login support --}}
        <div class="mt-8 flex gap-[22px] justify-center text-[11px] leading-[1.45] text-muted sm:justify-start">
            <a href="#" class="hover:text-ink">IT Helpdesk</a>
            <a href="#" class="hover:text-ink">Privacy notice</a>
        </div>
    </div>
@endsection
