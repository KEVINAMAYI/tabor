<?php

use App\Http\Controllers\MpesaApi;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Livewire\Volt\Volt;


Volt::route('/change-password', 'admin.change-password')
        ->name('password.change');
/* Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard'); */

/* Route::post('finance/confirmation', [MpesaApi::class, 'c2bConfirmation'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('finance/validation', [MpesaApi::class, 'c2bValidation'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('finance/stk_response', [MpesaApi::class, 'stkCallbackAction'])->withoutMiddleware([VerifyCsrfToken::class]);*/
Route::post('finance/simulate-c2b', [MpesaApi::class, 'c2b'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('finance/register-url', [MpesaApi::class, 'registerUrl'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('finance/access-token', [MpesaApi::class, 'generateToken'])->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware(['auth'])->group(function () {

    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');

});


Route::get('/clear-cache', function () {

    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');

    return 'Caches cleared!';
});
Route::get('/create-storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage links created!';
});
Route::get('/refresh-db', function () {
    Artisan::call('migrate:fresh --seed');
    return 'Database refreshed!';
});
Route::get('/seed', function () {
    Artisan::call('db:seed');
    return 'Database seeded!';
});
Route::get('/migrate', function () {
    Artisan::call('migrate');
    return 'Database migrated!';
});

require __DIR__ . '/auth.php';
require __DIR__ . '/dashboard/admin.php';
require __DIR__ . '/dashboard/student.php';
require __DIR__ . '/front-end/index.php';
// require __DIR__ . '/api.php';
