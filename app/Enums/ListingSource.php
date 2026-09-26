<?php

namespace App\Enums;

/**
 * Where a name for a product or color comes from. The store is an outside
 * catalog whose listings are claims that only count through the owner's
 * rulings. The collection export is the owner's own spreadsheet, recorded so a
 * product can still be found by its spreadsheet name after it is renamed.
 */
enum ListingSource: string
{
    case FiestaFactoryDirect = 'fiesta_factory_direct';
    case CollectionExport = 'collection_export';

    public function label(): string
    {
        return match ($this) {
            self::FiestaFactoryDirect => 'Fiesta Factory Direct',
            self::CollectionExport => 'Collection export',
        };
    }

    /**
     * The key a spreadsheet product name is stored under. Lines share product
     * names ("10\" Plate" is both Fiesta and Harlequin), so the line is part of
     * it.
     */
    public static function exportKey(string $lineName, string $productKey): string
    {
        return mb_strtolower($lineName).'|'.$productKey;
    }
}
