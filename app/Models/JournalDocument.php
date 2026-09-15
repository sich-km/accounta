<?php

namespace App\Models;

use Database\Factories\JournalDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalDocument extends Model
{
    /** @use HasFactory<JournalDocumentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['file_size' => 'integer'];
    }
}
