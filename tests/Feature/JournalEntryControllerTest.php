<?php

use App\Enums\UserType;
use App\Models\Department;
use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function journalEntryPayloadFor(LedgerAccount $debitAccount, LedgerAccount $creditAccount, ?Department $department = null): array
{
    return [
        'entry_date' => '2026-09-11',
        'originating_department_id' => $department?->id,
        'description' => 'サービス売上',
        'notes' => '振込入金',
        'organization_id' => Organization::factory()->create()->id,
        'lines' => [
            [
                'side' => 'debit',
                'ledger_account_id' => $debitAccount->id,
                'department_id' => null,
                'amount' => '120000.00',
                'description' => '普通預金',
            ],
            [
                'side' => 'credit',
                'ledger_account_id' => $creditAccount->id,
                'department_id' => $department?->id,
                'amount' => '100000.00',
                'description' => '月額利用料',
            ],
            [
                'side' => 'credit',
                'ledger_account_id' => $creditAccount->id,
                'department_id' => $department?->id,
                'amount' => '20000.00',
                'description' => '導入支援料',
            ],
        ],
    ];
}

test('journal entry form separates debit and credit input panels', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create([
        'code' => 'D120',
        'name' => '人事部',
    ]);
    $user->update(['department_id' => $department->id]);
    LedgerAccount::factory()->for($user->organization)->create([
        'code' => '6000',
        'name' => '給与手当',
    ]);

    $this->actingAs($user)->get(route('journal-entries.create'))
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="documents[]"', false)
        ->assertSeeInOrder(['証憑', '仕訳情報'])
        ->assertSeeText('仕訳入力')
        ->assertSeeText('借方明細')
        ->assertSeeText('貸方明細')
        ->assertSeeText('借方明細を追加')
        ->assertSeeText('貸方明細を追加')
        ->assertSeeText('起票部門')
        ->assertSee('value="'.$department->id.'" selected', false)
        ->assertSeeText('この仕訳を起票する部門です。新しく追加する明細の個別部門にも使用します。')
        ->assertSee('department_id: this.originatingDepartmentId', false)
        ->assertSee('@change="item.line.ledger_account_id = $event.target.value; applyAccountDefaults(item.line)"', false)
        ->assertSee('`${dateParts[1]}年${Number(dateParts[2])}月の${accountName}`', false)
        ->assertDontSee('applyDefaultDepartment', false)
        ->assertSeeText('給与手当')
        ->assertSeeText('明細の詳細設定を開閉')
        ->assertSeeText('この明細を削除')
        ->assertSee('class="flex items-start gap-2"', false)
        ->assertSee('class="flex shrink-0 items-center gap-1 pt-5"', false)
        ->assertDontSeeText('個別設定あり')
        ->assertSeeText('個別部門')
        ->assertSeeText('明細摘要');
});

test('journal entry index uses the custom deletion confirmation modal', function () {
    $user = User::factory()->create();
    JournalEntry::factory()->for($user->organization)->create();

    $this->actingAs($user)->get(route('journal-entries.index'))
        ->assertSee('aria-label="操作メニューを開く"', false)
        ->assertSee('x-teleport="body"', false)
        ->assertSee('class="fixed inset-0 z-50"', false)
        ->assertSee('menuPositioned: false', false)
        ->assertSee("'visibility: hidden;'", false)
        ->assertSeeText('詳細')
        ->assertSeeText('編集')
        ->assertSeeText('削除')
        ->assertDontSee('📎')
        ->assertSeeText('仕訳の削除')
        ->assertSeeText('キャンセル')
        ->assertSeeText('削除する')
        ->assertSee('id="confirm-journal-entry-deletion"', false)
        ->assertDontSee('return confirm(', false);
});

test('journal entry index supports the selected page size', function (int $perPage) {
    $user = User::factory()->create();
    JournalEntry::factory()
        ->count($perPage + 1)
        ->for($user->organization)
        ->create();

    $response = $this->actingAs($user)->get(route('journal-entries.index', [
        'per_page' => $perPage,
    ]));

    $response
        ->assertViewHas('journalEntries', fn ($journalEntries): bool => $journalEntries->perPage() === $perPage
            && $journalEntries->count() === $perPage
            && $journalEntries->total() === $perPage + 1)
        ->assertViewHas('perPageOptions', [50, 100, 150, 200])
        ->assertSee('value="'.$perPage.'" selected', false);
})->with([
    '50件' => 50,
    '100件' => 100,
    '150件' => 150,
    '200件' => 200,
]);

test('journal entry index can filter entries by year within the current organization', function () {
    $user = User::factory()->create();
    JournalEntry::factory()->for($user->organization)->create([
        'entry_date' => '2025-01-01',
        'description' => '2025年1月の自組織仕訳',
    ]);
    JournalEntry::factory()->for($user->organization)->create([
        'entry_date' => '2025-12-31',
        'description' => '2025年12月の自組織仕訳',
    ]);
    JournalEntry::factory()->for($user->organization)->create([
        'entry_date' => '2026-01-01',
        'description' => '2026年の自組織仕訳',
    ]);
    JournalEntry::factory()->create([
        'entry_date' => '2027-01-01',
        'description' => '2027年の他組織仕訳',
    ]);

    $this->actingAs($user)->get(route('journal-entries.index', [
        'year' => 2025,
    ]))
        ->assertViewHas('availableYears', [2026, 2025])
        ->assertViewHas('selectedYear', 2025)
        ->assertViewHas('selectedMonth', null)
        ->assertSeeText('2025年1月の自組織仕訳')
        ->assertSeeText('2025年12月の自組織仕訳')
        ->assertDontSeeText('2026年の自組織仕訳')
        ->assertDontSeeText('2027年の他組織仕訳');
});

test('journal entry index can filter entries by year and month within the current organization', function () {
    $user = User::factory()->create();
    JournalEntry::factory()->for($user->organization)->create([
        'entry_date' => '2026-01-31',
        'description' => '1月の自組織仕訳',
    ]);
    JournalEntry::factory()->for($user->organization)->create([
        'entry_date' => '2026-02-01',
        'description' => '2月の自組織仕訳',
    ]);
    JournalEntry::factory()->create([
        'entry_date' => '2026-01-15',
        'description' => '1月の他組織仕訳',
    ]);

    $this->actingAs($user)->get(route('journal-entries.index', [
        'year' => 2026,
        'month' => 1,
        'per_page' => 100,
    ]))
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', 1)
        ->assertViewHas('selectedPerPage', 100)
        ->assertSeeText('1月の自組織仕訳')
        ->assertDontSeeText('2月の自組織仕訳')
        ->assertDontSeeText('1月の他組織仕訳')
        ->assertSee('value="2026" selected', false)
        ->assertSee('value="1" selected', false);
});

test('journal entry index falls back to safe defaults for invalid filters', function () {
    $user = User::factory()->create();
    JournalEntry::factory()->for($user->organization)->create([
        'entry_date' => '2026-01-01',
        'description' => '表示対象の仕訳',
    ]);

    $this->actingAs($user)->get(route('journal-entries.index', [
        'year' => 2025,
        'month' => 13,
        'per_page' => 1000,
    ]))
        ->assertViewHas('selectedYear', null)
        ->assertViewHas('selectedMonth', null)
        ->assertViewHas('selectedPerPage', 50)
        ->assertSeeText('表示対象の仕訳');
});

test('journal entry details display the originating department', function () {
    $user = User::factory()->create();
    $originatingDepartment = Department::factory()->for($user->organization)->create([
        'code' => 'D120',
        'name' => '人事部',
    ]);
    $journalEntry = JournalEntry::factory()->for($user->organization)->create([
        'originating_department_id' => $originatingDepartment->id,
    ]);

    $this->actingAs($user)->get(route('journal-entries.show', $journalEntry))
        ->assertSeeText('起票部門')
        ->assertSeeText('D120 人事部');
});

test('edit form preserves the originating department when journal lines use different departments', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $userDepartment = Department::factory()->for($organization)->create();
    $originatingDepartment = Department::factory()->for($organization)->create();
    $otherDepartment = Department::factory()->for($organization)->create();
    $user->update(['department_id' => $userDepartment->id]);
    $account = LedgerAccount::factory()->for($organization)->create();
    $journalEntry = JournalEntry::factory()->for($organization)->create([
        'originating_department_id' => $originatingDepartment->id,
    ]);
    $journalEntry->lines()->createMany([
        ['organization_id' => $organization->id, 'line_number' => 1, 'ledger_account_id' => $account->id, 'department_id' => $userDepartment->id, 'side' => 'debit', 'amount' => '100.00'],
        ['organization_id' => $organization->id, 'line_number' => 2, 'ledger_account_id' => $account->id, 'department_id' => $otherDepartment->id, 'side' => 'credit', 'amount' => '100.00'],
    ]);

    $this->actingAs($user)->get(route('journal-entries.edit', $journalEntry))
        ->assertSee('value="'.$originatingDepartment->id.'" selected', false);
});

test('edit form can select additional documents and displays the remaining limit', function () {
    $user = User::factory()->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();
    JournalDocument::factory()->count(2)->for($journalEntry)->create([
        'organization_id' => $user->organization_id,
    ]);

    $this->actingAs($user)->get(route('journal-entries.edit', $journalEntry))
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="documents[]"', false)
        ->assertSeeText('現在 2 件添付済みです。')
        ->assertSeeText('あと8件追加できます。');
});

test('every user type can create a balanced compound journal entry', function (UserType $userType) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['user_type' => $userType]);
    $cash = LedgerAccount::factory()->for($organization)->create(['code' => '1000']);
    $sales = LedgerAccount::factory()->for($organization)->create(['code' => '4100']);
    $department = Department::factory()->for($organization)->create();

    $response = $this->actingAs($user)->post(
        route('journal-entries.store'),
        journalEntryPayloadFor($cash, $sales, $department),
    );

    $journalEntry = JournalEntry::query()->where('description', 'サービス売上')->firstOrFail();
    $response->assertRedirect(route('journal-entries.show', $journalEntry));
    expect($journalEntry->organization_id)->toBe($organization->id)
        ->and($journalEntry->originating_department_id)->toBe($department->id)
        ->and($journalEntry->lines()->count())->toBe(3)
        ->and($journalEntry->debitTotal())->toBe('120000.00')
        ->and($journalEntry->creditTotal())->toBe('120000.00');
})->with(UserType::cases());

test('blank line descriptions use the entry period and ledger account name when creating a journal entry', function () {
    $user = User::factory()->create();
    $loan = LedgerAccount::factory()->for($user->organization)->create(['name' => '貸付金']);
    $sales = LedgerAccount::factory()->for($user->organization)->create(['name' => '売上']);
    $payload = journalEntryPayloadFor($loan, $sales);
    $payload['entry_date'] = '2026-09-30';
    $payload['lines'][0]['description'] = '';
    $payload['lines'][1]['description'] = '   ';
    $payload['lines'][2]['description'] = '手入力した摘要';

    $response = $this->actingAs($user)->post(route('journal-entries.store'), $payload);

    $journalEntry = JournalEntry::query()->where('description', 'サービス売上')->firstOrFail();
    $response->assertRedirect(route('journal-entries.show', $journalEntry));
    expect($journalEntry->lines()->orderBy('line_number')->pluck('description')->all())->toBe([
        '2026年9月の貸付金',
        '2026年9月の売上',
        '手入力した摘要',
    ]);
});

test('a journal entry can be created with multiple documents', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['documents'] = [
        UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
        UploadedFile::fake()->create('invoice.jpg', 64, 'image/jpeg'),
    ];

    $response = $this->actingAs($user)->post(route('journal-entries.store'), $payload);

    $journalEntry = JournalEntry::query()->where('description', 'サービス売上')->firstOrFail();
    $response->assertRedirect(route('journal-entries.show', $journalEntry));
    expect($journalEntry->documents()->count())->toBe(2);
    $journalEntry->documents()->get()->each(
        fn (JournalDocument $document) => Storage::disk('local')->assertExists($document->path),
    );
});

test('unsupported documents cannot be uploaded while creating a journal entry', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['documents'] = [
        UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
    ];

    $this->actingAs($user)->post(route('journal-entries.store'), $payload)
        ->assertSessionHasErrors('documents.0');

    $this->assertDatabaseMissing('journal_entries', [
        'organization_id' => $user->organization_id,
        'description' => 'サービス売上',
    ]);
});

test('no more than ten documents can be uploaded while creating a journal entry', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['documents'] = collect(range(1, 11))
        ->map(fn (int $number): UploadedFile => UploadedFile::fake()->create("receipt-{$number}.pdf", 10, 'application/pdf'))
        ->all();

    $this->actingAs($user)->post(route('journal-entries.store'), $payload)
        ->assertSessionHasErrors('documents');

    $this->assertDatabaseMissing('journal_entries', [
        'organization_id' => $user->organization_id,
        'description' => 'サービス売上',
    ]);
});

test('journal entries accept line descriptions longer than 200 characters', function () {
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $payload = journalEntryPayloadFor($cash, $sales);
    $longDescription = str_repeat('長', 500);
    $payload['lines'][0]['description'] = $longDescription;

    $response = $this->actingAs($user)->post(route('journal-entries.store'), $payload);

    $journalEntry = JournalEntry::query()->where('description', 'サービス売上')->firstOrFail();
    $response->assertRedirect(route('journal-entries.show', $journalEntry));
    expect($journalEntry->lines()->where('line_number', 1)->value('description'))->toBe($longDescription);
});

test('an journal entry does not persist when debit and credit totals differ', function () {
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['lines'][2]['amount'] = '19999.99';

    $this->actingAs($user)->post(route('journal-entries.store'), $payload)
        ->assertSessionHasErrors('lines');

    $this->assertDatabaseMissing('journal_entries', ['organization_id' => $user->organization_id, 'description' => 'サービス売上']);
});

test('journal entries require both sides and positive amounts', function () {
    $user = User::factory()->create();
    $account = LedgerAccount::factory()->for($user->organization)->create();
    $payload = journalEntryPayloadFor($account, $account);
    $payload['lines'] = [
        ['side' => 'debit', 'ledger_account_id' => $account->id, 'department_id' => null, 'amount' => '0', 'description' => null],
        ['side' => 'debit', 'ledger_account_id' => $account->id, 'department_id' => null, 'amount' => '100', 'description' => null],
    ];

    $this->actingAs($user)->post(route('journal-entries.store'), $payload)
        ->assertSessionHasErrors('lines');
});

test('another organization accounts and departments cannot be used in a journal entry', function () {
    $user = User::factory()->create();
    $ownAccount = LedgerAccount::factory()->for($user->organization)->create();
    $otherAccount = LedgerAccount::factory()->create();
    $otherDepartment = Department::factory()->for($otherAccount->organization)->create();
    $payload = journalEntryPayloadFor($ownAccount, $otherAccount, $otherDepartment);

    $this->actingAs($user)->post(route('journal-entries.store'), $payload)
        ->assertSessionHasErrors(['originating_department_id', 'lines.1.ledger_account_id', 'lines.1.department_id']);
});

test('only current organization journal entries are visible and reachable', function () {
    $user = User::factory()->create();
    $ownEntry = JournalEntry::factory()->for($user->organization)->create(['description' => '自組織仕訳']);
    $otherEntry = JournalEntry::factory()->create(['description' => '他組織仕訳']);

    $this->actingAs($user)->get(route('journal-entries.index'))
        ->assertOk()
        ->assertSeeText('自組織仕訳')
        ->assertDontSeeText('他組織仕訳');
    $this->actingAs($user)->get(route('journal-entries.show', $otherEntry))->assertNotFound();
    $this->actingAs($user)->get(route('journal-entries.edit', $otherEntry))->assertNotFound();
    $this->actingAs($user)->delete(route('journal-entries.destroy', $otherEntry))->assertNotFound();
    $this->assertModelExists($ownEntry);
    $this->assertModelExists($otherEntry);
});

test('updating a journal entry replaces all lines and preserves a currently used inactive account', function () {
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->inactive()->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $department = Department::factory()->for($user->organization)->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();
    $journalEntry->lines()->createMany([
        ['organization_id' => $user->organization_id, 'line_number' => 1, 'ledger_account_id' => $cash->id, 'department_id' => null, 'side' => 'debit', 'amount' => '500.00'],
        ['organization_id' => $user->organization_id, 'line_number' => 2, 'ledger_account_id' => $sales->id, 'department_id' => null, 'side' => 'credit', 'amount' => '500.00'],
    ]);
    $payload = journalEntryPayloadFor($cash, $sales, $department);

    $this->actingAs($user)->put(route('journal-entries.update', $journalEntry), $payload)
        ->assertRedirect(route('journal-entries.show', $journalEntry));

    expect($journalEntry->fresh()->lines()->count())->toBe(3)
        ->and($journalEntry->fresh()->originating_department_id)->toBe($department->id)
        ->and($journalEntry->fresh()->description)->toBe('サービス売上');
});

test('updating a journal entry can append multiple documents without removing existing documents', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create([
        'description' => '更新前',
    ]);
    $existingDocument = JournalDocument::factory()->for($journalEntry)->create([
        'organization_id' => $user->organization_id,
        'path' => 'journal-documents/existing.pdf',
        'original_name' => 'existing.pdf',
    ]);
    Storage::disk('local')->put($existingDocument->path, 'existing document');
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['documents'] = [
        UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
        UploadedFile::fake()->create('invoice.png', 64, 'image/png'),
    ];

    $response = $this->actingAs($user)->put(route('journal-entries.update', $journalEntry), $payload);

    $response->assertRedirect(route('journal-entries.show', $journalEntry));
    $this->assertModelExists($existingDocument);
    expect($journalEntry->fresh()->description)->toBe('サービス売上')
        ->and($journalEntry->documents()->count())->toBe(3);
    $journalEntry->documents()
        ->whereKeyNot($existingDocument->id)
        ->get()
        ->each(fn (JournalDocument $document) => Storage::disk('local')->assertExists($document->path));
});

test('unsupported documents cannot be uploaded while updating a journal entry', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create([
        'description' => '更新前',
    ]);
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['documents'] = [
        UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
    ];

    $this->actingAs($user)->put(route('journal-entries.update', $journalEntry), $payload)
        ->assertSessionHasErrors('documents.0');

    expect($journalEntry->fresh()->description)->toBe('更新前')
        ->and($journalEntry->documents()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('an update cannot exceed ten documents including existing documents', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cash = LedgerAccount::factory()->for($user->organization)->create();
    $sales = LedgerAccount::factory()->for($user->organization)->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create([
        'description' => '更新前',
    ]);
    JournalDocument::factory()->count(10)->for($journalEntry)->create([
        'organization_id' => $user->organization_id,
    ]);
    $payload = journalEntryPayloadFor($cash, $sales);
    $payload['documents'] = [
        UploadedFile::fake()->create('eleventh.pdf', 10, 'application/pdf'),
    ];

    $this->actingAs($user)->put(route('journal-entries.update', $journalEntry), $payload)
        ->assertSessionHasErrors('documents');

    expect($journalEntry->fresh()->description)->toBe('更新前')
        ->and($journalEntry->documents()->count())->toBe(10)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('deleting a journal entry cascades its lines', function () {
    $user = User::factory()->create();
    $account = LedgerAccount::factory()->for($user->organization)->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();
    $line = $journalEntry->lines()->create([
        'organization_id' => $user->organization_id,
        'line_number' => 1,
        'ledger_account_id' => $account->id,
        'side' => 'debit',
        'amount' => '100.00',
    ]);

    $this->actingAs($user)->delete(route('journal-entries.destroy', $journalEntry))
        ->assertRedirect(route('journal-entries.index'));

    $this->assertModelMissing($journalEntry);
    $this->assertModelMissing($line);
});

test('database constraints reject a cross organization journal account reference', function () {
    $organization = Organization::factory()->create();
    $journalEntry = JournalEntry::factory()->for($organization)->create();
    $otherAccount = LedgerAccount::factory()->create();

    expect(fn () => $journalEntry->lines()->create([
        'organization_id' => $organization->id,
        'line_number' => 1,
        'ledger_account_id' => $otherAccount->id,
        'side' => 'debit',
        'amount' => '100.00',
    ]))->toThrow(QueryException::class);
});

test('database constraints reject a cross organization originating department reference', function () {
    $organization = Organization::factory()->create();
    $otherDepartment = Department::factory()->create();

    expect(fn () => JournalEntry::factory()->for($organization)->create([
        'originating_department_id' => $otherDepartment->id,
    ]))->toThrow(QueryException::class);
});

test('guests cannot access journal entry routes', function () {
    $journalEntry = JournalEntry::factory()->create();

    $this->get(route('journal-entries.index'))->assertRedirect(route('login'));
    $this->get(route('journal-entries.show', $journalEntry))->assertRedirect(route('login'));
    $this->post(route('journal-entries.store'), [])->assertRedirect(route('login'));
});
