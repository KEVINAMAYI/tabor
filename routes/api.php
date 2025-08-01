<?php

use App\Http\Controllers\MpesaApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::post('/finance/confirmation', [MpesaApi::class, 'c2bConfirmation'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/finance/validation', [MpesaApi::class, 'c2bValidation'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/finance/stk_response', [MpesaApi::class, 'stkCallbackAction'])->withoutMiddleware([VerifyCsrfToken::class]);
