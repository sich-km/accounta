<?php

use App\Enums\UserType;
use App\Models\Department;
use App\Models\FixedAsset;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;

/** @return array<string, mixed> */
function fixedAssetPayload(Department $department, array $overrides = []): array
{
    return [
        'department_id' => $department->id,
        'asset_code' => 'FA-001',
        'asset_name' => '製造設備',
        'asset_category' => 'machinery_equipment',
        'asset_category_detail' => null,
        'acquisition_date' => '2026-04-01',
        'service_start_date' => '2026-04-15',
        'acquisition_cost' => '120000.00',
        'useful_life_years' => '10',
        'depreciation_method' => 'straight_line',
        'residual_value' => '10000.00',
        'current_period_depreciation_expense' => '11000.00',
        'accumulated_depreciation' => '22000.00',
        'status' => 'held',
        'notes' => '生産部門の設備',
        ...$overrides,
    ];
}

test('users can create and view a fixed asset with normalized input', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $otherOrganization = Organization::factory()->create();

    $this->actingAs($user)
        ->get(route('fixed-assets.create'))
        ->assertOk()
        ->assertSee('固定資産を登録');

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, [
            'asset_code' => ' fa-001 ',
            'asset_name' => ' 製造設備 ',
            'asset_category' => ' 機械装置 ',
            'notes' => ' 生産部門の設備 ',
            'organization_id' => $otherOrganization->id,
        ]))
        ->assertRedirect(route('fixed-assets.index'));

    $fixedAsset = FixedAsset::query()->sole();

    expect($fixedAsset->organization_id)->toBe($user->organization_id);
    expect($fixedAsset->asset_code)->toBe('FA-001');
    expect($fixedAsset->asset_name)->toBe('製造設備');
    expect($fixedAsset->asset_category)->toBe('machinery_equipment');
    expect($fixedAsset->acquisition_cost)->toBe('120000.00');
    expect($fixedAsset->bookValue())->toBe('98000.00');

    $this->actingAs($user)
        ->get(route('fixed-assets.show', $fixedAsset))
        ->assertOk()
        ->assertSeeText('98,000.00')
        ->assertSeeText('定額法')
        ->assertSeeText('保有中')
        ->assertSee('href="'.route('fixed-assets.edit', $fixedAsset).'"', false)
        ->assertSeeText('編集')
        ->assertSeeText('削除')
        ->assertSee('id="confirm-fixed-asset-deletion"', false)
        ->assertSee('action="'.route('fixed-assets.destroy', $fixedAsset).'"', false)
        ->assertSeeText('キャンセル')
        ->assertSeeText('削除する')
        ->assertSee('class="text-sm font-semibold text-gray-700"', false)
        ->assertSee('class="mt-1 text-base font-medium text-gray-900"', false)
        ->assertDontSee('return confirm(', false);
});

test('all user types can manage fixed assets in their organization', function (UserType $userType) {
    $user = User::factory()->create(['user_type' => $userType]);
    $department = Department::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department))
        ->assertRedirect(route('fixed-assets.index'));

    $fixedAsset = FixedAsset::query()->sole();

    $this->actingAs($user)
        ->put(route('fixed-assets.update', $fixedAsset), fixedAssetPayload($department, [
            'asset_name' => '更新後設備',
            'status' => 'sold',
        ]))
        ->assertRedirect(route('fixed-assets.show', $fixedAsset));

    $this->actingAs($user)
        ->delete(route('fixed-assets.destroy', $fixedAsset))
        ->assertRedirect(route('fixed-assets.index'));

    $this->assertModelMissing($fixedAsset);
})->with([
    'admin' => UserType::Admin,
    'company administrator' => UserType::CompanyAdmin,
    'regular user' => UserType::User,
]);

test('fixed asset values must preserve depreciation consistency', function (array $overrides, string $errorKey) {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, $overrides))
        ->assertSessionHasErrors($errorKey);

    $this->assertDatabaseCount('fixed_assets', 0);
})->with([
    'positive acquisition cost' => [['acquisition_cost' => '0'], 'acquisition_cost'],
    'service date after acquisition' => [['service_start_date' => '2026-03-31'], 'service_start_date'],
    'residual not above acquisition cost' => [['residual_value' => '120000.01'], 'residual_value'],
    'accumulated not above depreciable amount' => [['accumulated_depreciation' => '110000.01'], 'accumulated_depreciation'],
    'current expense included in accumulated amount' => [['current_period_depreciation_expense' => '22000.01'], 'current_period_depreciation_expense'],
]);

test('other asset category requires and stores its detail', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, [
            'asset_category' => 'other',
            'asset_category_detail' => '',
        ]))
        ->assertSessionHasErrors('asset_category_detail');

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, [
            'asset_category' => 'other',
            'asset_category_detail' => ' 美術品 ',
        ]))
        ->assertRedirect(route('fixed-assets.index'));

    $this->assertDatabaseHas('fixed_assets', [
        'organization_id' => $user->organization_id,
        'asset_category' => 'other',
        'asset_category_detail' => '美術品',
    ]);

    $fixedAsset = FixedAsset::query()->sole();

    $this->actingAs($user)
        ->put(route('fixed-assets.update', $fixedAsset), fixedAssetPayload($department, [
            'asset_category' => 'building',
            'asset_category_detail' => '送信されても保存しない値',
        ]))
        ->assertRedirect(route('fixed-assets.show', $fixedAsset));

    expect($fixedAsset->refresh()->asset_category_detail)->toBeNull();
});

test('land and construction in progress must be non depreciable', function (string $assetCategory) {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, [
            'asset_category' => $assetCategory,
            'depreciation_method' => 'straight_line',
        ]))
        ->assertSessionHasErrors('depreciation_method');

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, [
            'asset_category' => $assetCategory,
            'depreciation_method' => 'not_applicable',
            'current_period_depreciation_expense' => '0',
            'accumulated_depreciation' => '0',
        ]))
        ->assertRedirect(route('fixed-assets.index'));

    $this->assertDatabaseHas('fixed_assets', [
        'organization_id' => $user->organization_id,
        'asset_category' => $assetCategory,
        'depreciation_method' => 'not_applicable',
        'accumulated_depreciation' => '0.00',
    ]);
})->with([
    'land' => 'land',
    'construction in progress' => 'construction_in_progress',
]);

test('non depreciable assets cannot contain depreciation amounts', function (string $field) {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department, [
            'depreciation_method' => 'not_applicable',
            $field => '1',
        ]))
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('fixed_assets', 0);
})->with([
    'current depreciation expense' => 'current_period_depreciation_expense',
    'accumulated depreciation' => 'accumulated_depreciation',
]);

test('asset codes are unique only within an organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $department = Department::factory()->for($organization)->create();
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_code' => 'FA-001',
    ]);

    $this->actingAs($user)
        ->post(route('fixed-assets.store'), fixedAssetPayload($department))
        ->assertSessionHasErrors('asset_code');

    $otherUser = User::factory()->create();
    $otherDepartment = Department::factory()->for($otherUser->organization)->create();

    $this->actingAs($otherUser)
        ->post(route('fixed-assets.store'), fixedAssetPayload($otherDepartment))
        ->assertRedirect(route('fixed-assets.index'));

    $this->assertDatabaseCount('fixed_assets', 2);
});

test('users cannot assign an inactive or foreign department when creating a fixed asset', function () {
    $user = User::factory()->create();
    $inactiveDepartment = Department::factory()->for($user->organization)->inactive()->create();
    $foreignDepartment = Department::factory()->create();

    foreach ([$inactiveDepartment, $foreignDepartment] as $department) {
        $this->actingAs($user)
            ->post(route('fixed-assets.store'), fixedAssetPayload($department))
            ->assertSessionHasErrors('department_id');
    }

    $this->assertDatabaseCount('fixed_assets', 0);
});

test('existing fixed assets can retain an inactive department when updated', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $fixedAsset = FixedAsset::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $department->id,
        'asset_code' => 'FA-001',
    ]);
    $department->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('fixed-assets.edit', $fixedAsset))
        ->assertOk()
        ->assertSee('（無効）');

    $this->actingAs($user)
        ->put(route('fixed-assets.update', $fixedAsset), fixedAssetPayload($department, [
            'asset_name' => '更新後設備',
            'status' => 'retired',
        ]))
        ->assertRedirect(route('fixed-assets.show', $fixedAsset));

    $this->assertDatabaseHas('fixed_assets', [
        'id' => $fixedAsset->id,
        'department_id' => $department->id,
        'asset_name' => '更新後設備',
        'status' => 'retired',
    ]);
});

test('fixed assets can be filtered by acquisition year category department and status', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $targetDepartment = Department::factory()->for($organization)->create([
        'code' => 'D100',
        'name' => '対象部門',
    ]);
    $otherDepartment = Department::factory()->for($organization)->create([
        'code' => 'D200',
        'name' => '別部門',
    ]);
    $matchingAsset = FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $targetDepartment->id,
        'asset_name' => '条件一致資産',
        'acquisition_date' => '2025-06-15',
        'asset_category' => 'software',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $targetDepartment->id,
        'asset_name' => '取得年度不一致資産',
        'acquisition_date' => '2024-12-31',
        'asset_category' => 'software',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $targetDepartment->id,
        'asset_name' => '資産区分不一致資産',
        'acquisition_date' => '2025-06-15',
        'asset_category' => 'furniture_fixture',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $otherDepartment->id,
        'asset_name' => '部門不一致資産',
        'acquisition_date' => '2025-06-15',
        'asset_category' => 'software',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $targetDepartment->id,
        'asset_name' => '状態不一致資産',
        'acquisition_date' => '2025-06-15',
        'asset_category' => 'software',
        'status' => 'sold',
    ]);

    $this->actingAs($user)
        ->get(route('fixed-assets.index', [
            'acquisition_year' => 2025,
            'asset_category' => 'software',
            'department_id' => $targetDepartment->id,
            'status' => 'held',
        ]))
        ->assertViewHas('selectedYear', 2025)
        ->assertViewHas('selectedAssetCategory', 'software')
        ->assertViewHas('selectedDepartmentId', $targetDepartment->id)
        ->assertViewHas('selectedStatus', 'held')
        ->assertSeeText($matchingAsset->asset_name)
        ->assertDontSeeText('取得年度不一致資産')
        ->assertDontSeeText('資産区分不一致資産')
        ->assertDontSeeText('部門不一致資産')
        ->assertDontSeeText('状態不一致資産')
        ->assertSee('name="acquisition_year"', false)
        ->assertSee('name="asset_category"', false)
        ->assertSee('name="department_id"', false)
        ->assertSee('name="status"', false)
        ->assertSeeText('クリア');
});

test('fixed asset filters ignore unsupported values and departments from another organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $department = Department::factory()->for($organization)->create([
        'name' => '自組織部門',
    ]);
    $fixedAsset = FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_name' => '自組織資産',
        'acquisition_date' => '2025-06-15',
    ]);
    $foreignDepartment = Department::factory()->create([
        'name' => '他組織部門',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $foreignDepartment->organization_id,
        'department_id' => $foreignDepartment->id,
        'asset_name' => '他組織資産',
        'acquisition_date' => '2030-01-01',
    ]);

    $this->actingAs($user)
        ->get(route('fixed-assets.index', [
            'acquisition_year' => 'invalid',
            'asset_category' => 'invalid',
            'department_id' => $foreignDepartment->id,
            'status' => 'invalid',
        ]))
        ->assertViewHas('selectedYear', null)
        ->assertViewHas('selectedAssetCategory', null)
        ->assertViewHas('selectedDepartmentId', null)
        ->assertViewHas('selectedStatus', null)
        ->assertSeeText($fixedAsset->asset_name)
        ->assertSeeText('自組織部門')
        ->assertDontSeeText('他組織資産')
        ->assertDontSeeText('他組織部門')
        ->assertDontSeeText('2030年');
});

test('fixed asset index explains when no assets match active filters', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    FixedAsset::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $department->id,
        'asset_category' => 'software',
    ]);

    $this->actingAs($user)
        ->get(route('fixed-assets.index', ['asset_category' => 'building']))
        ->assertSeeText('条件に一致する固定資産がありません。')
        ->assertDontSeeText('固定資産が登録されていません。');
});

test('fixed assets from another organization are hidden and return 404', function () {
    $user = User::factory()->create();
    $ownDepartment = Department::factory()->for($user->organization)->create();
    $ownAsset = FixedAsset::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $ownDepartment->id,
        'asset_name' => '自組織資産',
    ]);
    $otherAsset = FixedAsset::factory()->create(['asset_name' => '他組織資産']);

    $this->actingAs($user)
        ->get(route('fixed-assets.index'))
        ->assertOk()
        ->assertSeeText($ownAsset->asset_name)
        ->assertDontSeeText($otherAsset->asset_name)
        ->assertSee('<a href="'.route('fixed-assets.create').'"', false)
        ->assertSee('href="'.route('fixed-assets.show', $ownAsset).'"', false)
        ->assertSee('class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800"', false)
        ->assertSee('class="min-w-[14rem] max-w-xs break-words px-4 py-4 text-base text-gray-900"', false)
        ->assertSee('class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900"', false)
        ->assertDontSeeText('操作')
        ->assertDontSee('aria-label="操作メニューを開く"', false)
        ->assertDontSee('x-teleport="body"', false);

    $this->actingAs($user)->get(route('fixed-assets.show', $otherAsset))->assertNotFound();
    $this->actingAs($user)->get(route('fixed-assets.edit', $otherAsset))->assertNotFound();
    $this->actingAs($user)
        ->put(route('fixed-assets.update', $otherAsset), fixedAssetPayload($ownDepartment))
        ->assertNotFound();
    $this->actingAs($user)->delete(route('fixed-assets.destroy', $otherAsset))->assertNotFound();

    $this->assertModelExists($otherAsset);
});

test('the database rejects a department from another organization', function () {
    $organization = Organization::factory()->create();
    $otherDepartment = Department::factory()->create();

    $this->expectException(QueryException::class);

    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $otherDepartment->id,
    ]);
});

test('guests cannot access fixed asset management', function () {
    $fixedAsset = FixedAsset::factory()->create();

    $this->get(route('fixed-assets.index'))->assertRedirect(route('login'));
    $this->get(route('fixed-assets.show', $fixedAsset))->assertRedirect(route('login'));
    $this->post(route('fixed-assets.store'), [])->assertRedirect(route('login'));
    $this->put(route('fixed-assets.update', $fixedAsset), [])->assertRedirect(route('login'));
    $this->delete(route('fixed-assets.destroy', $fixedAsset))->assertRedirect(route('login'));
});
