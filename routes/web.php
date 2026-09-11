<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExcelExportController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\ManagementAccountController;
use App\Http\Controllers\MonthlyAmountController;
use App\Models\FixedAsset;
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
                'managementAccounts as active_management_accounts_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            ])
            ->firstOrFail();

        $summary = FixedAsset::query()
            ->forOrganization($organization->id)
            ->where('status', 'held')
            ->toBase()
            ->selectRaw('COUNT(*) as asset_count')
            ->selectRaw('COALESCE(SUM(acquisition_cost), 0) as acquisition_cost')
            ->selectRaw('COALESCE(SUM(acquisition_cost - accumulated_depreciation), 0) as book_value')
            ->first();

        return view('dashboard', [
            'organization' => $organization,
            'fixedAssetSummary' => [
                'count' => (int) $summary->asset_count,
                'acquisitionCost' => number_format((float) $summary->acquisition_cost, 2, '.', ''),
                'bookValue' => number_format((float) $summary->book_value, 2, '.', ''),
            ],
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

    Route::resource('management-accounts', ManagementAccountController::class)
        ->except(['show', 'destroy']);
    Route::patch('management-accounts/{management_account}/status', [ManagementAccountController::class, 'toggleStatus'])
        ->name('management-accounts.status');

    Route::resource('amounts', MonthlyAmountController::class)
        ->except('show');

    Route::resource('fixed-assets', FixedAssetController::class);
});
