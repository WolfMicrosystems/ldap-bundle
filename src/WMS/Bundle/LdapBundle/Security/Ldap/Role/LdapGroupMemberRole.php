<?php
namespace WMS\Bundle\LdapBundle\Security\Ldap\Role;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Zend\Ldap\Dn;

class LdapGroupMemberRole implements RoleInterface
{
    protected $groupDn;
    protected $groupName;
    protected $groupDescription;
    protected $role;

    public function __construct($role, Dn $groupDn, $groupName, $groupDescription = null)
    {
        $this->role = (string)$role;
        $this->groupDn = $groupDn;
        $this->groupName = $groupName;
        $this->groupDescription = $groupDescription;
    }

    /**
     * Returns the role.
     *
     * This method returns a string representation whenever possible.
     *
     * When the role cannot be represented with sufficient precision by a
     * string, it should return null.
     *
     * @return string|null A string representation of the role, or null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @return string|null
     */
    public function getGroupDescription()
    {
        return $this->groupDescription;
    }

    /**
     * @return \Zend\Ldap\Dn
     */
    public function getGroupDn()
    {
        return $this->groupDn;
    }
}