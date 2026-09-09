<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Company::class);

        $companies = Company::query()
            ->withCount(['organizations', 'users'])
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(50);

        return view('companies.index', compact('companies'));
    }

    public function create(): View
    {
        Gate::authorize('create', Company::class);

        return view('companies.create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        Company::query()->create($request->validated());

        return redirect()
            ->route('companies.index')
            ->with('status', '会社情報を登録しました。');
    }

    public function edit(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect()
            ->route('companies.index')
            ->with('status', '会社情報を更新しました。');
    }

    public function destroy(Company $company): RedirectResponse
    {
        Gate::authorize('delete', $company);

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('status', '会社情報を削除しました。');
    }
}
