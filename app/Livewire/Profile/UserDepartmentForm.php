<?php

namespace App\Livewire\Profile;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UserDepartmentForm extends Component
{
    public ?string $departmentId = null;

    public function mount(): void
    {
        $user = $this->user();

        $this->departmentId = $user->department_id === null
            ? null
            : (string) $user->department_id;
    }

    public function updateDepartment(): void
    {
        $user = $this->user();
        $currentDepartmentId = $user->department_id;

        $validated = $this->validate([
            'departmentId' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn (QueryBuilder $query): QueryBuilder => $query
                        ->where('organization_id', $user->organization_id)
                        ->where(fn (QueryBuilder $departmentQuery): QueryBuilder => $departmentQuery
                            ->where('is_active', true)
                            ->when($currentDepartmentId !== null, fn (QueryBuilder $activeQuery): QueryBuilder => $activeQuery->orWhere('id', $currentDepartmentId))),
                ),
            ],
        ], [
            'departmentId.exists' => '自組織の有効な部門を選択してください。',
        ], [
            'departmentId' => '所属部門',
        ]);

        $user->update([
            'department_id' => filled($validated['departmentId'])
                ? (int) $validated['departmentId']
                : null,
        ]);

        $this->dispatch('department-saved');
    }

    public function render(): View
    {
        $user = $this->user();
        $departments = Department::query()
            ->forOrganization($user->organization_id)
            ->where(fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->when($user->department_id !== null, fn (Builder $activeQuery): Builder => $activeQuery->orWhereKey($user->department_id)))
            ->orderBy('code')
            ->get();

        return view('livewire.profile.user-department-form', compact('departments'));
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
