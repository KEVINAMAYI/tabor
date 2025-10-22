<?php

use App\Http\Controllers\MpesaApi;
use Illuminate\Support\Facades\Route;

Route::post('finance/confirmation', [MpesaApi::class, 'c2bConfirmation']);
Route::post('finance/validation', [MpesaApi::class, 'c2bValidation']);
Route::post('finance/callback', [MpesaApi::class, 'stkCallbackAction']);
Route::post('finance/stk', [MpesaApi::class, 'initiateStk']);
Route::post('finance/generate-token', [MpesaApi::class, 'generateToken']);
