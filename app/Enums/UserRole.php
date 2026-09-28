<?php

namespace App\Enums;

/**
 * The owner keeps the catalog and invites people. Testers keep their own
 * collection and can look at the owner's, but cannot change the catalog.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Tester = 'tester';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Tester => 'Beta tester',
        };
    }
}
