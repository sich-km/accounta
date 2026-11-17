<?php

use App\Enums\JournalSide;
use App\Mcp\Servers\AccountaServer;
use App\Mcp\Tools\GetJournalEntriesTool;
use App\Models\Company;
use App\Models\Department;
use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('所属Organizationの会計年度内の仕訳だけを新しい順で返す', function () {
    [$user, $organization] = createAccountaMcpUser();
    $olderEntry = JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2026-04-01',
    ]);
    $newerEntry = JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2027-03-31',
    ]);
    JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2026-03-31',
    ]);
    JournalEntry::factory()->create([
        'entry_date' => '2027-03-31',
    ]);

    AccountaServer::actingAs($user)
        ->tool(GetJournalEntriesTool::class, ['year' => 2026])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('period.fiscal_year', 2026)
            ->where('period.month', null)
            ->where('period.start_date', '2026-04-01')
            ->where('period.end_date', '2027-03-31')
            ->where('count', 2)
            ->where('journal_entries.0.id', $newerEntry->id)
            ->where('journal_entries.1.id', $olderEntry->id)
            ->etc());
});

test('一覧は最大200件で打ち切り返した件数を返す', function () {
    [$user, $organization] = createAccountaMcpUser();
    JournalEntry::factory()->count(201)->for($organization)->create([
        'entry_date' => '2026-06-01',
    ]);

    AccountaServer::actingAs($user)
        ->tool(GetJournalEntriesTool::class, ['year' => 2026, 'limit' => 200])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('count', 200)
            ->count('journal_entries', 200)
            ->etc());
});

test('仕訳IDで明細と安全な証憑メタデータを取得できる', function () {
    [$user, $organization] = createAccountaMcpUser();
    $department = Department::factory()->create([
        'organization_id' => $organization->id,
        'code' => 'D001',
    ]);
    $account = LedgerAccount::factory()->create([
        'organization_id' => $organization->id,
        'code' => '1110',
        'name' => '現金',
    ]);
    $entry = JournalEntry::factory()->for($organization)->create([
        'originating_department_id' => $department->id,
        'entry_date' => '2026-05-10',
        'description' => '現金売上',
        'notes' => '確認済み',
    ]);
    JournalEntryLine::factory()->for($entry)->for($account, 'ledgerAccount')->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'line_number' => 1,
        'side' => JournalSide::Debit,
        'amount' => '1000.00',
        'description' => '現金',
    ]);
    JournalEntryLine::factory()->for($entry)->for($account, 'ledgerAccount')->create([
        'organization_id' => $organization->id,
        'department_id' => null,
        'line_number' => 2,
        'side' => JournalSide::Credit,
        'amount' => '1000.00',
        'description' => '売上',
    ]);
    $document = JournalDocument::factory()->for($entry)->create([
        'organization_id' => $organization->id,
        'disk' => 'local',
        'path' => 'journal-documents/secret.pdf',
        'original_name' => 'receipt.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1234,
    ]);

    AccountaServer::actingAs($user)
        ->tool(GetJournalEntriesTool::class, ['journal_entry_id' => $entry->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('count', 1)
            ->where('journal_entries.0.id', $entry->id)
            ->where('journal_entries.0.originating_department_code', 'D001')
            ->where('journal_entries.0.debit_total', '1000.00')
            ->where('journal_entries.0.credit_total', '1000.00')
            ->where('journal_entries.0.lines.0.ledger_account_code', '1110')
            ->where('journal_entries.0.lines.0.ledger_account_name', '現金')
            ->where('journal_entries.0.documents.0.id', $document->id)
            ->where('journal_entries.0.documents.0.journal_entry_id', $entry->id)
            ->where('journal_entries.0.documents.0.original_filename', 'receipt.pdf')
            ->where('journal_entries.0.documents.0.mime_type', 'application/pdf')
            ->where('journal_entries.0.documents.0.file_size', 1234)
            ->has('journal_entries.0.documents.0.registered_at')
            ->missing('journal_entries.0.documents.0.path')
            ->missing('journal_entries.0.documents.0.disk')
            ->missing('journal_entries.0.documents.0.content')
            ->etc());
});

test('Organizationに所属しない利用者を拒否する', function () {
    $user = User::factory()->make([
        'organization_id' => null,
        'company_id' => null,
    ]);

    AccountaServer::actingAs($user)
        ->tool(GetJournalEntriesTool::class)
        ->assertHasErrors(['所属Organizationを確認できません']);
});

test('他Organizationの仕訳IDは存在を開示せず見つからないとして扱う', function () {
    [$user] = createAccountaMcpUser();
    $otherEntry = JournalEntry::factory()->create();

    AccountaServer::actingAs($user)
        ->tool(GetJournalEntriesTool::class, ['journal_entry_id' => $otherEntry->id])
        ->assertHasErrors(['仕訳が見つかりません'])
        ->assertDontSee((string) $otherEntry->organization_id);
});

test('未認証のWeb MCP呼び出しをWWW-Authenticateヘッダー付きで拒否する', function () {
    $this->postJson('/mcp/accounta', mcpToolsListRequest())
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

test('OAuth発見メタデータを返す', function () {
    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonPath('scopes_supported.0', 'mcp:use');

    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('registration_endpoint', url('/oauth/register'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');
});

test('動的クライアント登録はループバックRedirect URIを許可する', function () {
    $this->postJson('/oauth/register', oauthClientRegistration('http://127.0.0.1:49152/callback'))
        ->assertCreated()
        ->assertJsonPath('redirect_uris.0', 'http://127.0.0.1:49152/callback')
        ->assertJsonPath('scope', 'mcp:use');
});

test('動的クライアント登録は外部Redirect URIを拒否する', function () {
    $this->postJson('/oauth/register', oauthClientRegistration('https://example.com/callback'))
        ->assertStatus(400)
        ->assertJsonPath('error', 'invalid_redirect_uri');
});

test('動的クライアント登録は同一IPで1分10回に制限する', function () {
    foreach (range(1, 10) as $portOffset) {
        $this->postJson(
            '/oauth/register',
            oauthClientRegistration('http://127.0.0.1:'.(50000 + $portOffset).'/callback'),
        )->assertCreated();
    }

    $this->postJson('/oauth/register', oauthClientRegistration('http://127.0.0.1:50011/callback'))
        ->assertTooManyRequests();
});

test('OAuth発見GETは動的クライアント登録の上限に含めない', function () {
    foreach (range(1, 12) as $attempt) {
        $this->getJson('/.well-known/oauth-protected-resource')->assertOk();
        $this->getJson('/.well-known/oauth-authorization-server')->assertOk();
    }
});

test('認証済みWeb MCP呼び出しは1分60回に制限する', function () {
    [$user] = createAccountaMcpUser();
    Passport::actingAs($user, ['mcp:use']);

    foreach (range(1, 60) as $attempt) {
        $this->postJson('/mcp/accounta', mcpToolsListRequest($attempt))->assertOk();
    }

    $this->postJson('/mcp/accounta', mcpToolsListRequest(61))
        ->assertTooManyRequests();
});

test('Serverが公開する業務Toolは参照専用の一つだけである', function () {
    [$user] = createAccountaMcpUser();
    Passport::actingAs($user, ['mcp:use']);

    $this->postJson('/mcp/accounta', mcpToolsListRequest())
        ->assertOk()
        ->assertJsonCount(1, 'result.tools')
        ->assertJsonPath('result.tools.0.name', 'get-journal-entries')
        ->assertJsonPath('result.tools.0.annotations.readOnlyHint', true)
        ->assertJsonPath('result.tools.0.annotations.idempotentHint', true);
});

/**
 * @return array{User, Organization}
 */
function createAccountaMcpUser(): array
{
    $company = Company::factory()->create(['fiscal_year_start_month' => 4]);
    $organization = Organization::factory()->for($company)->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'organization_id' => $organization->id,
    ]);

    return [$user, $organization];
}

/**
 * @return array<string, mixed>
 */
function mcpToolsListRequest(int $id = 1): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => 'tools/list',
        'params' => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function oauthClientRegistration(string $redirectUri): array
{
    return [
        'client_name' => 'Codex Test Client',
        'redirect_uris' => [$redirectUri],
    ];
}
