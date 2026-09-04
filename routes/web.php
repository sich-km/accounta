<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MonthlyAmountController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware([
    'auth',
    config('jetstream.auth_session'),
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'organization' => request()->user()->organization,
        ]);
    })->name('dashboard');

    Route::resource('departments', DepartmentController::class)
        ->except(['show', 'destroy']);
    Route::patch('departments/{department}/status', [DepartmentController::class, 'toggleStatus'])
        ->name('departments.status');

    Route::resource('accounts', AccountController::class)
        ->except(['show', 'destroy']);
    Route::patch('accounts/{account}/status', [AccountController::class, 'toggleStatus'])
        ->name('accounts.status');

    Route::resource('amounts', MonthlyAmountController::class)
        ->except('show');
});
