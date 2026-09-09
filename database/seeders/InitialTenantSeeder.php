<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitialTenantSeeder extends Seeder
{
    public function run(): void
    {
        $createdPasswords = [];

        DB::transaction(function () use (&$createdPasswords): void {
            $company = Company::query()->firstOrCreate(
                ['code' => Company::DEFAULT_CODE],
                [
                    'name' => 'KM',
                    'fiscal_year_start_month' => 1,
                ],
            );

            $organization = $company->organizations()->firstOrCreate(
                ['name' => 'KM'],
                ['type' => 'company'],
            );

            foreach ([
                'admin' => 'admin',
                'company_admin' => 'company_admin',
                'user1' => 'user',
            ] as $loginId => $userType) {
                $plainPassword = $this->ensureUser(
                    $company,
                    $organization,
                    $loginId,
                    $userType,
                );

                if ($plainPassword !== null) {
                    $createdPasswords[$loginId] = $plainPassword;
                }
            }
        });

        foreach ($createdPasswords as $loginId => $plainPassword) {
            $this->command?->warn("初期ユーザー {$loginId} のパスワード: {$plainPassword}");
        }

        if ($createdPasswords !== []) {
            $this->command?->warn('このパスワードは再表示されません。安全な場所に保管してください。');
        }
    }

    private function ensureUser(
        Company $company,
        Organization $organization,
        string $loginId,
        string $userType,
    ): ?string {
        $user = User::query()->where('login_id', $loginId)->first();

        if ($user !== null) {
            $user->company()->associate($company);
            $user->organization()->associate($organization);
            $user->fill([
                'user_type' => $userType,
                'name' => $loginId,
            ])->save();

            return null;
        }

        $plainPassword = Str::random(24);

        $organization->users()->create([
            'company_id' => $company->id,
            'user_type' => $userType,
            'login_id' => $loginId,
            'name' => $loginId,
            'email' => null,
            'password' => Hash::make($plainPassword),
        ]);

        return $plainPassword;
    }
}
