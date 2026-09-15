<?php

namespace App\Services;

use App\Models\JournalDocument;
use App\Models\JournalEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class JournalDocumentService
{
    public function store(JournalEntry $journalEntry, UploadedFile $file): JournalDocument
    {
        $disk = 'local';
        $path = $file->store("journal-documents/{$journalEntry->organization_id}/{$journalEntry->id}", $disk);

        if ($path === false) {
            throw new RuntimeException('証憑ファイルを保存できませんでした。');
        }

        try {
            return $journalEntry->documents()->create([
                'organization_id' => $journalEntry->organization_id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'file_size' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    public function delete(JournalDocument $document): bool
    {
        $disk = $document->disk;
        $path = $document->path;
        $document->delete();

        if (Storage::disk($disk)->exists($path) && ! Storage::disk($disk)->delete($path)) {
            $exception = new RuntimeException("証憑ファイルの削除に失敗しました: {$path}");
            report($exception);

            return false;
        }

        return true;
    }
}
