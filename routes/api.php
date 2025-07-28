<?php

use App\Http\Controllers\MpesaApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('finance/confirmation', [MpesaApi::class, 'c2bConfirmation']);
Route::post('finance/validation', [MpesaApi::class, 'c2bValidation']);
Route::post('finance/stk_response', [MpesaApi::class, 'stkCallbackAction']);
Route::post('finance/simulate-c2b', [MpesaApi::class, 'c2b']);
Route::post('finance/register-url', [MpesaApi::class, 'registerUrl']);
Route::get('finance/access-token', [MpesaApi::class, 'generateToken']);
