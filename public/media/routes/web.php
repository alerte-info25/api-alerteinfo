<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\ResetsPasswords;
use App\Http\Controllers\PasswordResetWebController;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

Route::get('password/reset/{token}', [PasswordResetWebController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('password/reset', [PasswordResetWebController::class, 'reset'])
    ->name('password.update');
