<?php

use App\Http\Controllers\Admin\WorkOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\MicrosoftController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/unauthorized', 'auth.login', ['unauthorized' => true])->name('unauthorized');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('reports/export', [ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('reports',        [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('work-orders',                       [WorkOrderController::class, 'index'])->name('admin.work-orders.index');
    Route::post('work-orders',                      [WorkOrderController::class, 'store'])->name('admin.work-orders.store');
    Route::get('work-orders/{workOrder}',           [WorkOrderController::class, 'show'])->name('admin.work-orders.show');
    Route::patch('work-orders/{workOrder}/reassign', [WorkOrderController::class, 'reassign'])->name('admin.work-orders.reassign');
    Route::post('work-orders/{workOrder}/notes',    [WorkOrderController::class, 'storeNote'])->name('admin.work-orders.notes.store');

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
