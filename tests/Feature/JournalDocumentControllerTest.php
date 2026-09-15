<?php

use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a PDF can be attached to an owned journal entry on private storage', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();
    $file = UploadedFile::fake()->create('領収書.pdf', 128, 'application/pdf');

    $this->actingAs($user)->post(route('journal-entries.documents.store', $journalEntry), [
        'document' => $file,
    ])->assertRedirect();

    $document = $journalEntry->documents()->firstOrFail();
    expect($document->original_name)->toBe('領収書.pdf')
        ->and($document->path)->not->toContain('領収書');
    Storage::disk('local')->assertExists($document->path);
});

test('an attached document can be viewed and downloaded by its organization', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();
    $document = JournalDocument::factory()->for($journalEntry)->create([
        'organization_id' => $user->organization_id,
        'path' => 'journal-documents/test.pdf',
    ]);
    Storage::disk('local')->put($document->path, 'PDF content');

    $this->actingAs($user)->get(route('journal-entries.documents.show', [$journalEntry, $document]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    $this->actingAs($user)->get(route('journal-entries.documents.show', [$journalEntry, $document, 'download' => 1]))
        ->assertDownload($document->original_name);
});

test('another organization cannot attach view or remove documents', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $otherEntry = JournalEntry::factory()->create();
    $otherDocument = JournalDocument::factory()->for($otherEntry)->create([
        'organization_id' => $otherEntry->organization_id,
    ]);

    $this->actingAs($user)->post(route('journal-entries.documents.store', $otherEntry), [
        'document' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
    ])->assertNotFound();
    $this->actingAs($user)->get(route('journal-entries.documents.show', [$otherEntry, $otherDocument]))->assertNotFound();
    $this->actingAs($user)->delete(route('journal-entries.documents.destroy', [$otherEntry, $otherDocument]))->assertNotFound();
});

test('unsupported documents and an eleventh attachment are rejected', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();

    $this->actingAs($user)->post(route('journal-entries.documents.store', $journalEntry), [
        'document' => UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
    ])->assertSessionHasErrors('document');
    $this->actingAs($user)->post(route('journal-entries.documents.store', $journalEntry), [
        'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
    ])->assertSessionHasErrors('document');

    JournalDocument::factory()->count(10)->for($journalEntry)->create([
        'organization_id' => $user->organization_id,
    ]);

    $this->actingAs($user)->post(route('journal-entries.documents.store', $journalEntry), [
        'document' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('document');
});

test('removing a document deletes its database record and stored file', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $journalEntry = JournalEntry::factory()->for($user->organization)->create();
    $document = JournalDocument::factory()->for($journalEntry)->create([
        'organization_id' => $user->organization_id,
        'path' => 'journal-documents/remove.pdf',
    ]);
    Storage::disk('local')->put($document->path, 'PDF content');

    $this->actingAs($user)->delete(route('journal-entries.documents.destroy', [$journalEntry, $document]))
        ->assertRedirect();

    $this->assertModelMissing($document);
    Storage::disk('local')->assertMissing($document->path);
});
