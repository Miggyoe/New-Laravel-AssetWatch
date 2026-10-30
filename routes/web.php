<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PersonnelController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReportDetailController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkOrderController;
use App\Http\Controllers\MicrosoftController;
use Illuminate\Support\Facades\Route;

/* ---------- Public ---------- */
Route::get('/', fn () => redirect()->route(auth()->check() ? 'admin.dashboard' : 'login'));

Route::view('/login', 'auth.login')->name('login')->middleware('guest');

// Same page as the login, with the red "Unauthorized Access" alert (HTTP 403)
Route::get('/unauthorized', fn () => response()->view('auth.login', ['unauthorized' => true], 403))->name('unauthorized');

Route::get('/auth/microsoft', [MicrosoftController::class, 'redirect'])->name('microsoft.redirect');
Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'callback'])->name('microsoft.callback');
// Old URLs kept in case the Azure app registration still points at them
Route::get('/auth/azure', [MicrosoftController::class, 'redirect']);
Route::get('/auth/azure/callback', [MicrosoftController::class, 'callback']);

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

/* ---------- Administration portal: signed in + MCC account + admin role ---------- */
Route::middleware(['auth', 'mcc.org', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Incident reports
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports',        [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}',            [ReportDetailController::class, 'show'])->name('reports.show');
    Route::patch('reports/{report}',          [ReportDetailController::class, 'update'])->name('reports.update');
    Route::post('reports/{report}/verify',    [ReportDetailController::class, 'verify'])->name('reports.verify');
    Route::post('reports/{report}/reject',    [ReportDetailController::class, 'reject'])->name('reports.reject');
    Route::patch('reports/{report}/priority', [ReportDetailController::class, 'priority'])->name('reports.priority');
    Route::patch('reports/{report}/status',   [ReportDetailController::class, 'status'])->name('reports.status');
    Route::post('reports/{report}/notes',     [ReportDetailController::class, 'storeNote'])->name('reports.notes.store');

    // Work orders
    Route::get('work-orders',                        [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::post('work-orders',                       [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('work-orders/{workOrder}',            [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::patch('work-orders/{workOrder}/reassign', [WorkOrderController::class, 'reassign'])->name('work-orders.reassign');
    Route::post('work-orders/{workOrder}/notes',     [WorkOrderController::class, 'storeNote'])->name('work-orders.notes.store');

    // Maintenance personnel
    Route::get('personnel',                                [PersonnelController::class, 'index'])->name('personnel.index');
    Route::post('personnel',                               [PersonnelController::class, 'store'])->name('personnel.store');
    Route::get('personnel/{technician}',                   [PersonnelController::class, 'show'])->name('personnel.show');
    Route::patch('personnel/{technician}',                 [PersonnelController::class, 'update'])->name('personnel.update');
    Route::post('personnel/{technician}/toggle/{field}',   [PersonnelController::class, 'toggle'])->name('personnel.toggle');

    // User management
    Route::get('users',                [UserController::class, 'index'])->name('users.index');
    Route::post('users',               [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}',       [UserController::class, 'update'])->name('users.update');
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

    // Locations
    Route::get('locations',                  [LocationController::class, 'index'])->name('locations.index');
    Route::post('locations',                 [LocationController::class, 'store'])->name('locations.store');
    Route::patch('locations/{building}',     [LocationController::class, 'update'])->name('locations.update');
    Route::post('locations/{building}/toggle', [LocationController::class, 'toggle'])->name('locations.toggle');

    // Categories
    Route::get('categories',                    [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories',                   [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}',       [CategoryController::class, 'update'])->name('categories.update');
    Route::post('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');

    // Analytics
    Route::get('analytics',        [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Activity logs
    Route::get('activity-logs',        [ActivityLogController::class, 'index'])->name('activity-logs');
    Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
});
