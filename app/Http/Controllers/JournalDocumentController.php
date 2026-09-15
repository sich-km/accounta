<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalDocumentRequest;
use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Services\JournalDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JournalDocumentController extends Controller
{
    public function store(StoreJournalDocumentRequest $request, int $journalEntry, JournalDocumentService $service): RedirectResponse
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        /** @var UploadedFile $file */
        $file = $request->file('document');
        $service->store($journalEntry, $file);

        return back()->with('status', '証憑を添付しました。');
    }

    public function show(Request $request, int $journalEntry, int $journalDocument): StreamedResponse
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        $document = $this->ownedDocument($request, $journalEntry, $journalDocument);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $headers = ['Content-Type' => $document->mime_type];

        return $request->boolean('download')
            ? Storage::disk($document->disk)->download($document->path, $document->original_name, $headers)
            : Storage::disk($document->disk)->response($document->path, $document->original_name, $headers);
    }

    public function destroy(Request $request, int $journalEntry, int $journalDocument, JournalDocumentService $service): RedirectResponse
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        $document = $this->ownedDocument($request, $journalEntry, $journalDocument);
        $fileDeleted = $service->delete($document);

        return back()->with(
            $fileDeleted ? 'status' : 'warning',
            $fileDeleted ? '証憑の紐付けを解除しました。' : '証憑の紐付けは解除しましたが、ファイルを削除できませんでした。',
        );
    }

    private function ownedJournalEntry(Request $request, int $journalEntryId): JournalEntry
    {
        return JournalEntry::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($journalEntryId);
    }

    private function ownedDocument(Request $request, JournalEntry $journalEntry, int $journalDocumentId): JournalDocument
    {
        return JournalDocument::query()
            ->where('organization_id', $request->user()->organization_id)
            ->whereBelongsTo($journalEntry)
            ->findOrFail($journalDocumentId);
    }
}
