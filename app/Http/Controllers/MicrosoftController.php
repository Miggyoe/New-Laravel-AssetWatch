<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Microsoft sign-in failed. Please try again.');
        }

        $email = strtolower((string) $azureUser->getEmail());

        if ($email === '' || ! str_ends_with($email, '@mcc.edu.ph')) {
            return redirect()->route('unauthorized')->with('error', 'Only MCC accounts can access this portal.');
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $azureUser->getName() ?: Str::before($email, '@'),
                'password' => bcrypt(Str::random(32)),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended('/admin');
    }
}
