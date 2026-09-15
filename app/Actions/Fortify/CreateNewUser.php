<?php

namespace App\Actions\Fortify;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $input['login_id'] = Str::of($input['login_id'] ?? '')->trim()->lower()->toString();
        $input['name'] = Str::of($input['name'] ?? '')->trim()->toString();
        $input['organization_name'] = Str::of($input['organization_name'] ?? '')->trim()->toString();
        $input['department_code'] = Str::of($input['department_code'] ?? '')->trim()->upper()->toString();
        $input['department_name'] = Str::of($input['department_name'] ?? '')->trim()->toString();

        Validator::make($input, [
            'login_id' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9][a-z0-9._-]*$/', 'unique:users,login_id'],
            'name' => ['required', 'string', 'max:100'],
            'organization_name' => ['required', 'string', 'max:100'],
            'department_code' => ['nullable', 'required_with:department_name', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/'],
            'department_name' => ['nullable', 'required_with:department_code', 'string', 'max:100'],
            'password' => $this->passwordRules(),
        ], [
            'login_id.regex' => 'ユーザーIDは英小文字・数字・ピリオド・アンダースコア・ハイフンで入力してください。',
            'department_code.regex' => '所属部門コードは英大文字・数字・アンダースコア・ハイフンで入力してください。',
            'department_code.required_with' => '所属部門名を入力する場合は、所属部門コードも入力してください。',
            'department_name.required_with' => '所属部門コードを入力する場合は、所属部門名も入力してください。',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $company = Company::query()
                ->where('code', Company::DEFAULT_CODE)
                ->firstOrFail();

            $organization = $company->organizations()->create([
                'name' => $input['organization_name'],
                'type' => 'company',
            ]);

            $department = filled($input['department_code'])
                ? $organization->departments()->create([
                    'code' => $input['department_code'],
                    'name' => $input['department_name'],
                ])
                : null;

            return $organization->users()->create([
                'company_id' => $company->id,
                'department_id' => $department?->id,
                'user_type' => UserType::User,
                'login_id' => $input['login_id'],
                'name' => $input['name'],
                'email' => null,
                'password' => Hash::make($input['password']),
            ]);
        });
    }
}
