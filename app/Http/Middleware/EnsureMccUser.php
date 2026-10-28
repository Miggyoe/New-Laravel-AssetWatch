<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Re-checks on every request that the signed-in user is still an active member of the MCC organisation. */
class EnsureMccUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $domainOk = $user && in_array(
            Str::after(strtolower($user->email), '@'),
            config('mcc.allowed_domains'),
            true
        );

        // is_active / tenant_id exist after the "add_microsoft_columns_to_users" migration
        $active   = $user && ($user->is_active ?? true);
        $tenantId = config('mcc.tenant_id');
        $tenantOk = ! $tenantId || ! $user?->tenant_id || $user->tenant_id === $tenantId;

        if (! $user || ! $domainOk || ! $active || ! $tenantOk) {
            return $this->deny($request, 'Your account is not authorized to access MCC AssetWatch.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('unauthorized')->with('error', $message);
    }
}
