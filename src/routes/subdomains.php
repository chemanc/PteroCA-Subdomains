<?php

use App\Http\Controllers\Admin\SubdomainController as AdminSubdomainController;
use App\Http\Controllers\Client\SubdomainController as ClientSubdomainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PteroCA Subdomains - Routes
|--------------------------------------------------------------------------
|
| Admin and client routes for the Subdomains plugin.
|
*/

// ==========================================================================
// Admin Routes
// ==========================================================================
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/subdomains')->group(function () {

    // Dashboard
    Route::get('/', [AdminSubdomainController::class, 'index'])
        ->name('admin.subdomains.index');

    // Settings
    Route::get('/settings', [AdminSubdomainController::class, 'settings'])
        ->name('admin.subdomains.settings');
    Route::post('/settings', [AdminSubdomainController::class, 'updateSettings'])
        ->name('admin.subdomains.settings.update');

    // Test Cloudflare connection (AJAX)
    Route::post('/test', [AdminSubdomainController::class, 'testConnection'])
        ->name('admin.subdomains.test');

    // Blacklist management
    Route::get('/blacklist', [AdminSubdomainController::class, 'blacklist'])
        ->name('admin.subdomains.blacklist');
    Route::post('/blacklist', [AdminSubdomainController::class, 'addToBlacklist'])
        ->name('admin.subdomains.blacklist.add');
    Route::delete('/blacklist/{id}', [AdminSubdomainController::class, 'removeFromBlacklist'])
        ->name('admin.subdomains.blacklist.remove');
    Route::post('/blacklist/import', [AdminSubdomainController::class, 'importBlacklist'])
        ->name('admin.subdomains.blacklist.import');
    Route::get('/blacklist/export', [AdminSubdomainController::class, 'exportBlacklist'])
        ->name('admin.subdomains.blacklist.export');
    Route::post('/blacklist/default', [AdminSubdomainController::class, 'loadDefaultBlacklist'])
        ->name('admin.subdomains.blacklist.default');

    // Activity logs
    Route::get('/logs', [AdminSubdomainController::class, 'logs'])
        ->name('admin.subdomains.logs');
    Route::post('/logs/clear', [AdminSubdomainController::class, 'clearLogs'])
        ->name('admin.subdomains.logs.clear');

    // Bulk operations
    Route::post('/sync', [AdminSubdomainController::class, 'syncDns'])
        ->name('admin.subdomains.sync');
    Route::get('/export', [AdminSubdomainController::class, 'exportSubdomains'])
        ->name('admin.subdomains.export');

    // Domain management
    Route::post('/domains', [AdminSubdomainController::class, 'addDomain'])
        ->name('admin.subdomains.domains.add');
    Route::put('/domains/{id}', [AdminSubdomainController::class, 'updateDomain'])
        ->name('admin.subdomains.domains.update');
    Route::delete('/domains/{id}', [AdminSubdomainController::class, 'deleteDomain'])
        ->name('admin.subdomains.domains.delete');
});

// ==========================================================================
// Client Routes
// ==========================================================================
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/servers/{server}/subdomain', [ClientSubdomainController::class, 'show'])
        ->name('client.subdomain.show');
    Route::post('/servers/{server}/subdomain', [ClientSubdomainController::class, 'store'])
        ->name('client.subdomain.store');
    Route::put('/servers/{server}/subdomain', [ClientSubdomainController::class, 'update'])
        ->name('client.subdomain.update');
    Route::delete('/servers/{server}/subdomain', [ClientSubdomainController::class, 'destroy'])
        ->name('client.subdomain.destroy');
});

// ==========================================================================
// API Routes (rate limited)
// ==========================================================================
Route::middleware(['web', 'auth', 'subdomain.ratelimit'])->group(function () {
    Route::post('/api/subdomains/check', [ClientSubdomainController::class, 'checkAvailability'])
        ->name('api.subdomain.check');
});
