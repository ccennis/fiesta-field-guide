<?php

namespace App\Enums;

/**
 * The admin keeps the catalog and invites people. Members keep their own
 * collection against it, and cannot change it.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }
}
