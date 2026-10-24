@php
    $error  = session('error');
    $denied = ($unauthorized ?? false) || $error;
    $domain = config('mcc.allowed_domains.0', 'mcc.edu.ph');

    // Campus photo: save the Figma export (node 85:1513) as public/images/campus.jpg
    $campus = collect(['jpg', 'jpeg', 'png', 'webp'])->first(fn ($ext) => file_exists(public_path("images/campus.$ext")));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | MCC AssetWatch</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v=3">
</head>
<body style="margin:0">
<div class="auth">

    {{-- Brand bar (phones only) --}}
    <div class="auth-topbar">
        <div class="auth-brand">
            <div class="auth-brand-tile"><img src="{{ asset('images/logo.png') }}" alt="MCC AssetWatch logo"></div>
            <div>
                <p class="auth-brand-name">MCC AssetWatch</p>
                <p class="auth-brand-sub">CAMPUS FACILITIES PORTAL</p>
            </div>
        </div>
    </div>

    {{-- Left: brand panel --}}
    <aside class="auth-aside">
        <div class="auth-brand">
            <div class="auth-brand-tile"><img src="{{ asset('images/logo.png') }}" alt="MCC AssetWatch logo"></div>
            <div>
                <p class="auth-brand-name">MCC AssetWatch</p>
                <p class="auth-brand-sub">CAMPUS FACILITIES PORTAL</p>
            </div>
        </div>

        <div class="auth-intro">
            <p class="auth-eyebrow">MCC FACILITIES &amp; MAINTENANCE</p>
            <h2 class="auth-headline">A better campus starts<br>with better care.</h2>
            <p class="auth-lead">One workspace to report, coordinate, and resolve campus facility issues—with accountability at every step.</p>
        </div>

        <div class="auth-photo">
            @if ($campus)
                <img src="{{ asset("images/campus.$campus") }}" alt="MCC campus">
            @endif
        </div>

        <div class="auth-assure">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
            <div>
                <strong>Secure, role-based access</strong>
                <span>For authorized administrators and maintenance personnel.</span>
            </div>
        </div>

        <p class="auth-copy">© {{ now()->year }} MCC AssetWatch · Main Campus</p>
    </aside>

    {{-- Right: sign in --}}
    <main class="auth-main">
        <div class="auth-form">
            <div class="auth-heading">
                <p class="auth-kicker">Campus Administration</p>
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-sub">Sign in to your MCC AssetWatch account.</p>
            </div>

            @if ($denied)
                <div class="auth-alert" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <div>
                        <strong>Unauthorized Access</strong>
                        <span>{{ $error ?: 'Your account is not authorized to access MCC AssetWatch.' }}</span>
                    </div>
                </div>
            @endif

            <div class="auth-signin">
                <a href="{{ route('microsoft.redirect') }}" class="auth-btn"
                   onclick="this.classList.add('is-loading'); this.querySelector('[data-label]').textContent = 'Redirecting to Microsoft…';">
                    <span class="auth-btn-logo">
                        <svg width="12" height="12" viewBox="0 0 21 21" aria-hidden="true">
                            <rect width="10" height="10" fill="#f25022"/><rect x="11" width="10" height="10" fill="#7fba00"/>
                            <rect y="11" width="10" height="10" fill="#00a4ef"/><rect x="11" y="11" width="10" height="10" fill="#ffb900"/>
                        </svg>
                    </span>
                    <span data-label>Sign in with Microsoft</span>
                </a>
                <p class="auth-hint">Use your official <strong>{{ '@' . $domain }}</strong> Microsoft account.</p>
            </div>

            <div class="auth-guide">
                <p>Need access? Contact your campus administrator.</p>
                <p>Accounts are issued by MCC. Public registration is not available.</p>
            </div>

            <div class="auth-links">
                <a href="#">IT Helpdesk</a>
                <a href="#">Privacy notice</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
