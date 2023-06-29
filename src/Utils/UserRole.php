<?php

namespace App\Utils;

class UserRole
{
    const LANDLORD = 'ROLE_LANDLORD';
    const TENANT = 'ROLE_TENANT';
    const DEFAULT = 'ROLE_DEFAULT';

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
