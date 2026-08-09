<?php

namespace App\Services\Import;

use App\Services\BaseService;

/**
 * Collects every ambiguity, conflict and assumption encountered during import.
 *
 * The source spreadsheets are a real, uncleaned collection export. Nothing is
 * silently resolved: anything the importer had to decide, guess at or skip is
 * recorded here and printed for the owner to rule on.
 */
class ImportReport extends BaseService
{
    /** @var array<int, array{category: string, message: string}> */
    private array $entries = [];

    /** @var array<string, int> */
    private array $counts = [];

    public function add(string $category, string $message): void
    {
        $this->entries[] = ['category' => $category, 'message' => $message];
    }

    public function count(string $key, int $by = 1): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + $by;
    }

    public function set(string $key, int $value): void
    {
        $this->counts[$key] = $value;
    }

    /** @return array<int, array{category: string, message: string}> */
    public function entries(): array
    {
        return $this->entries;
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return $this->counts;
    }

    /**
     * Entries bucketed by category, preserving insertion order within each.
     *
     * @return array<string, array<int, string>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->entries as $entry) {
            $grouped[$entry['category']][] = $entry['message'];
        }

        return $grouped;
    }

    public function total(): int
    {
        return count($this->entries);
    }

    public function toText(string $title): string
    {
        $out = [$title, str_repeat('=', strlen($title)), ''];

        foreach ($this->counts as $key => $value) {
            $out[] = sprintf('%-42s %s', $key, $value);
        }

        foreach ($this->grouped() as $category => $messages) {
            $out[] = '';
            $out[] = $category.' ('.count($messages).')';
            $out[] = str_repeat('-', strlen($category) + 6);

            foreach ($messages as $message) {
                $out[] = '  - '.$message;
            }
        }

        return implode(PHP_EOL, $out).PHP_EOL;
    }
}
