<?php

namespace Utils;

#[\Attribute]
class Access
{
    public array $roles;

    public function __construct(array $roles = [])
    {
        $this->roles = $roles;
    }

    public static function hasAccess(?string $userRole, array $allowedRoles): bool
    {
        if ($userRole === null) return false;

        $hierarchy = ['student' => 0, 'warden' => 1, 'admin' => 2];

        foreach ($allowedRoles as $role) {
            if (
                isset($hierarchy[$userRole], $hierarchy[$role]) &&
                $hierarchy[$userRole] >= $hierarchy[$role]
            ) {
                return true;
            }
        }

        return false;
    }
}
