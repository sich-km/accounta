<?php

namespace Database\Factories;

use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalDocument> */
class JournalDocumentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'journal_entry_id' => function (array $attributes): int {
                return JournalEntry::factory()->create(['organization_id' => $attributes['organization_id']])->id;
            },
            'disk' => 'local',
            'path' => 'journal-documents/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100, 1000000),
        ];
    }
}
