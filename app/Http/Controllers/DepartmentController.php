<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(50);

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $request->user()
            ->organization
            ->departments()
            ->create($request->validated());

        return redirect()
            ->route('departments.index')
            ->with('status', '部門を登録しました。');
    }

    public function edit(Request $request, int $department): View
    {
        $department = $this->ownedDepartment($request, $department);

        return view('departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, int $department): RedirectResponse
    {
        $department = $this->ownedDepartment($request, $department);
        $department->update($request->validated());

        return redirect()
            ->route('departments.index')
            ->with('status', '部門を更新しました。');
    }

    public function toggleStatus(Request $request, int $department): RedirectResponse
    {
        $department = $this->ownedDepartment($request, $department);
        $department->update(['is_active' => ! $department->is_active]);

        return back()->with(
            'status',
            $department->is_active ? '部門を有効にしました。' : '部門を無効にしました。'
        );
    }

    private function ownedDepartment(Request $request, int $departmentId): Department
    {
        return Department::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($departmentId);
    }
}
