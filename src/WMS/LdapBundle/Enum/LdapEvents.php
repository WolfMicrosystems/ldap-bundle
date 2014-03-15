<?php
namespace WMS\LdapBundle\Enum;

use WMS\Ldap\Enum\AbstractEnum;

final class LdapEvents extends AbstractEnum
{
    /**
     * The RESOLVING_USER_ROLES event occurs when transforming the user's
     * groups into roles.
     */
    const RESOLVING_USER_ROLES = 'wms_ldap.event.resolving_user_roles';
} 