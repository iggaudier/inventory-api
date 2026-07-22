<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case GroupAdmin = 'group_admin';
    case GroupMember = 'group_member';

    public function label(): string
    {
        return match ($this)
        {
            self::SuperAdmin => 'Super Admin',
            self::GroupAdmin => 'Group Admin',
            self::GroupMember => 'Group Member',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'values');
    }
}
