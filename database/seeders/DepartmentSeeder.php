<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string}>
     */
    private const array DEPARTMENTS = [
        ['code' => 'D100', 'name' => '経営企画部'],
        ['code' => 'D110', 'name' => '経理部'],
        ['code' => 'D120', 'name' => '人事部'],
        ['code' => 'D130', 'name' => '情報システム部'],
        ['code' => 'D200', 'name' => '情報機器事業部門'],
        ['code' => 'D210', 'name' => 'CS統括部'],
        ['code' => 'D220', 'name' => 'CSセンター'],
        ['code' => 'D230', 'name' => 'CS管理部'],
        ['code' => 'D240', 'name' => 'CS管理部 管理G'],
        ['code' => 'D300', 'name' => '開発部門'],
        ['code' => 'D310', 'name' => 'ソフトウェア開発部'],
        ['code' => 'D320', 'name' => '品質保証部'],
        ['code' => 'D400', 'name' => '営業部門'],
        ['code' => 'D410', 'name' => '国内営業部'],
        ['code' => 'D500', 'name' => '生産部門'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Organization::query()->doesntExist()) {
            $this->command?->warn('Organizationがありません。先に画面からユーザー登録してください。');

            return;
        }

        Organization::query()
            ->select('id')
            ->eachById(function (Organization $organization): void {
                $now = now();
                $departments = array_map(
                    fn (array $department): array => [
                        'organization_id' => $organization->id,
                        ...$department,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    self::DEPARTMENTS
                );

                Department::query()->upsert(
                    $departments,
                    ['organization_id', 'code'],
                    ['name', 'is_active', 'updated_at']
                );
            });
    }
}
