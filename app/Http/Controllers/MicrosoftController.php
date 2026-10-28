<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')
            ->scopes(['User.Read', 'openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->deny('Microsoft sign-in was cancelled or rejected.');
        }

        try {
            $azureUser = Socialite::driver('azure')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Microsoft sign-in failed. Please try again.');
        }

        $raw   = $azureUser->user ?? [];
        $email = strtolower((string) ($raw['mail'] ?? $raw['userPrincipalName'] ?? $azureUser->getEmail()));
        $tid   = $this->tenantIdFromToken($azureUser->accessTokenResponseBody['id_token'] ?? null);

        // 1. Tenant check: the "mail" attribute of an account in somebody else's tenant can be set to anything,
        //    so the email alone is not proof of membership.
        // Only reject when Microsoft actually gave us a tenant id and it does not match MCC.
        if (config('mcc.tenant_id') && $tid !== null && $tid !== config('mcc.tenant_id')) {
            return $this->deny('Your Microsoft account does not belong to Mabalacat City College.');
        }

        // 2. Domain check
        if ($email === '' || ! in_array(Str::after($email, '@'), config('mcc.allowed_domains'), true)) {
            return $this->deny('Only official MCC accounts (@' . implode(', @', config('mcc.allowed_domains')) . ') may sign in.');
        }

        // 3. Create or update the local user
        $user = User::query()->where('email', $email)->first() ?? new User();

        if (! $user->exists) {
            $user->email    = $email;
            $user->password = bcrypt(Str::random(32)); // never used: sign-in is Microsoft only
            $user->role     = 'user';
        }

        $user->name = $azureUser->getName() ?: ($user->name ?: Str::before($email, '@'));

        $isAdminEmail = in_array($email, config('mcc.admin_emails'), true);
        $existingAdmin = $user->exists && $user->role === 'admin';

        // Preserve an existing admin role that was granted manually in the database.
        // If the email is in MCC_ADMIN_EMAILS, we still promote them as admin.
        $user->role = ($isAdminEmail || $existingAdmin) ? 'admin' : 'user';

        // Optional columns (added by the add_microsoft_columns_to_users migration)
        $optional = [
            'microsoft_id'  => $azureUser->getId(),
            'tenant_id'     => $tid,
            'avatar'        => $azureUser->getAvatar(),
            'last_login_at' => now(),
        ];
        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $user->{$column} = $value;
            }
        }

        if (Schema::hasColumn('users', 'is_active') && $user->exists && $user->is_active === false) {
            return $this->deny('Your account has been deactivated. Contact the administrator.');
        }

        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function deny(string $message): RedirectResponse
    {
        return redirect()->route('unauthorized')->with('error', $message);
    }

    /** Read the "tid" claim. The token came straight from Microsoft's token endpoint over TLS. */
    private function tenantIdFromToken(?string $idToken): ?string
    {
        if (! $idToken || substr_count($idToken, '.') !== 2) {
            return null;
        }

        $parts = explode('.', $idToken);
        $payload = $parts[1];
        $remainder = strlen($payload) % 4;

        if ($remainder !== 0) {
            $payload .= str_repeat('=', 4 - $remainder);
        }

        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/'), true), true);

        return is_array($decoded) ? ($decoded['tid'] ?? null) : null;
    }
}
