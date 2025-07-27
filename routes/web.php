<?php

use App\Http\Controllers\MpesaApi;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    Route::post('finance/confirmation',[MpesaApi::class, 'c2bConfirmation']);
    Route::post('finance/validation',[MpesaApi::class, 'c2bValidation']);
    Route::post('finance/stk_response',[MpesaApi::class, 'stkCallbackAction']);


Route::middleware(['auth'])->group(function () {

    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');

});

require __DIR__ . '/auth.php';
require __DIR__ . '/dashboard/admin.php';
require __DIR__ . '/front-end/index.php';
