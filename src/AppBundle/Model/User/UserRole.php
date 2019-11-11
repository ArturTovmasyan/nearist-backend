<?php

namespace AppBundle\Model\User;

final class UserRole
{
    const ROLE_IMT_ADMIN = "ROLE_IMT_ADMIN";
    const ROLE_ADMIN = "ROLE_ADMIN";
    const ROLE_USER = "ROLE_USER";
    const ROLE_CUSTOMER = "ROLE_CUSTOMER";

    private static $ROLES = array(
        self::ROLE_IMT_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_USER,
        self::ROLE_CUSTOMER
    );

    private static $ROLES_HIERARCHY = array(
        self::ROLE_CUSTOMER => 4,
        self::ROLE_IMT_ADMIN => 3,
        self::ROLE_ADMIN => 2,
        self::ROLE_USER => 1,
    );

    /** @return array */
    public static function getRoles()
    {
        return self::$ROLES;
    }

    /**
     * @param $highestRole
     * @return array
     */
    public static function getRolesWithHighest($highestRole)
    {
        $highestRoleValue = self::$ROLES_HIERARCHY[$highestRole];

        $roles = array();
        foreach(self::$ROLES as $role){
            if(self::$ROLES_HIERARCHY[$role] <= $highestRoleValue){
                $roles[$role] = $role;
            }
        }
        return $roles;
    }

    /** @return array */
    public static function getRolesHierarchy()
    {
        return self::$ROLES_HIERARCHY;
    }
}