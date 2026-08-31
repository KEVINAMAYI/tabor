<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'active', 'password_changed', 'role:lecturer'])
    ->prefix('lecturer')
    ->group(function () {

        Volt::route('dashboard', 'lecturer.dashboard')
            ->name('lecturer.dashboard');

        Volt::route('modules/{intakeModule}', 'lecturer.module-view')
            ->name('lecturer.module.view');

    });
