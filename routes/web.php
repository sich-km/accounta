<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExcelExportController;
use App\Http\Controllers\MonthlyAmountController;
use Illuminate\Database\Eloquent\Builder;
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
        $organization = request()->user()
            ->organization()
            ->with('company:id,name')
            ->withCount([
                'monthlyAmounts as budget_records_count' => fn (Builder $query): Builder => $query->where('type', 'budget'),
                'monthlyAmounts as actual_records_count' => fn (Builder $query): Builder => $query->where('type', 'actual'),
                'departments as active_departments_count' => fn (Builder $query): Builder => $query->where('is_active', true),
                'accounts as active_accounts_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            ])
            ->firstOrFail();

        return view('dashboard', [
            'organization' => $organization,
        ]);
    })->name('dashboard');

    Route::get('/export/excel', ExcelExportController::class)
        ->name('export.excel');

    Route::resource('companies', CompanyController::class)
        ->except('show');

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
