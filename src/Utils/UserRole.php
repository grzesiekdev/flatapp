<?php

namespace App\Utils;

class UserRole
{
    const LANDLORD = 'landlord';
    const TENANT = 'tenant';
    const DEFAULT = 'default';

    public static function getAllRoles(): array
    {
        return [
            self::LANDLORD,
            self::TENANT,
            self::DEFAULT,
        ];
    }

    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getAllRoles(), true);
    }
}
