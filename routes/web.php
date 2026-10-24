<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\MicrosoftController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/unauthorized', 'auth.login', ['unauthorized' => true])->name('unauthorized');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::post('/logout', function () {
        auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

Route::get('/auth/microsoft', [MicrosoftController::class, 'redirect'])->name('microsoft.redirect');
Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'callback'])->name('microsoft.callback');
Route::get('/auth/azure', [MicrosoftController::class, 'redirect']);
Route::get('/auth/azure/callback', [MicrosoftController::class, 'callback']);
