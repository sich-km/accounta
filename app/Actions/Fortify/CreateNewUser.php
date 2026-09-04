<?php

namespace App\Actions\Fortify;

use App\Models\Organization;
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
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['login_id'] = Str::of($input['login_id'] ?? '')->trim()->lower()->toString();
        $input['name'] = Str::of($input['name'] ?? '')->trim()->toString();
        $input['organization_name'] = Str::of($input['organization_name'] ?? '')->trim()->toString();

        Validator::make($input, [
            'login_id' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9][a-z0-9._-]*$/', 'unique:users,login_id'],
            'name' => ['required', 'string', 'max:100'],
            'organization_name' => ['required', 'string', 'max:100'],
            'password' => $this->passwordRules(),
        ], [
            'login_id.regex' => 'ユーザーIDは英小文字・数字・ピリオド・アンダースコア・ハイフンで入力してください。',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $organization = Organization::create([
                'name' => $input['organization_name'],
                'type' => 'company',
                'fiscal_year_start_month' => 1,
            ]);

            return $organization->users()->create([
                'login_id' => $input['login_id'],
                'name' => $input['name'],
                'email' => null,
                'password' => Hash::make($input['password']),
            ]);
        });
    }
}
