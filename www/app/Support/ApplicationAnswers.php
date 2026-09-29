<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Flattens an application's saved answers into label => text rows for review. */
class ApplicationAnswers
{
    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, string>
     */
    public static function rows(?array $data): array
    {
        $rows = [];
        self::walk($data ?? [], '', $rows);

        return $rows;
    }

    /** @param  array<string, string>  $rows */
    private static function walk(array $node, string $prefix, array &$rows): void
    {
        foreach ($node as $key => $value) {
            $label = $prefix.(is_int($key) ? '#'.($key + 1) : Str::headline((string) $key));

            if (is_array($value)) {
                self::walk($value, $label.' › ', $rows);
            } elseif (is_bool($value)) {
                $rows[$label] = $value ? 'Yes' : 'No';
            } elseif (filled($value)) {
                $rows[$label] = (string) $value;
            }
        }
    }
}
