<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Flattens an application's saved answers into label => text rows for review. */
class ApplicationAnswers
{
    /** Labels where the automatic "Title Case Of The Key" reads badly. */
    private const LABELS = [
        'id_number' => 'ID number',
        'first_names' => 'First name(s)',
        'phone' => 'Cellular number',
        'phone_mobile' => 'Cellular number',
        'phone_business' => 'Business number',
        'food_experience' => 'Food-industry experience',
        'other_manager' => 'Someone else will manage the outlet',
        'accept_fees' => 'Undertakes to pay joining fee, royalties and advertising fees',
        'accept_declaration' => 'Declaration accepted',
        'signed_name' => 'Signed (typed name)',
        'insolvent' => 'Ever declared insolvent',
        'principals' => 'Person',
        'advisers' => 'Adviser',
        'finance_sources' => 'Finance source',
        'bankers' => 'Banker',
        'previous_businesses' => 'Previous business',
        'personal_references' => 'Personal reference',
        'trade_references' => 'Trade reference',
    ];

    /** Money fields, shown as rand. */
    private const MONEY = [
        'net_worth', 'monthly_income', 'monthly_expenditure', 'cash_available',
        'total_assets', 'total_liabilities', 'amount', 'monthly_repayment',
    ];

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
    private static function walk(array $node, string $prefix, array &$rows, ?string $listLabel = null): void
    {
        if (self::isList($node)) {
            $node = array_values($node);
        }

        foreach ($node as $key => $value) {
            $isItem = is_int($key);
            $label = $isItem
                ? $prefix.($listLabel ?? 'Item').' #'.($key + 1)
                : $prefix.(self::LABELS[$key] ?? Str::headline((string) $key));

            if (is_array($value)) {
                if ($isItem) {
                    self::walk($value, $label.' › ', $rows);
                } elseif (self::isList($value)) {
                    // The item labels ("Person #1") already name the list.
                    self::walk($value, $prefix, $rows, self::LABELS[$key] ?? Str::singular(Str::headline((string) $key)));
                } else {
                    self::walk($value, $label.' › ', $rows);
                }
            } elseif (is_bool($value)) {
                $rows[$label] = $value ? 'Yes' : 'No';
            } elseif (filled($value)) {
                $rows[$label] = in_array($key, self::MONEY, true) && is_numeric($value)
                    ? 'R '.number_format((float) $value, 0, '.', ' ')
                    : trim((string) $value);
            }
        }
    }

    /**
     * A list of rows: sequential keys, or Filament repeater items, which are
     * keyed by random UUIDs.
     *
     * @param  array<int|string, mixed>  $node
     */
    private static function isList(array $node): bool
    {
        return $node !== [] && (
            array_is_list($node)
            || collect($node)->keys()->every(fn ($k) => is_string($k) && Str::isUuid($k))
        );
    }
}
