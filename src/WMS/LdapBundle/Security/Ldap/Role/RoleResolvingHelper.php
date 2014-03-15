<?php
namespace WMS\LdapBundle\Security\Ldap\Role;

use Symfony\Component\Security\Core\Role\RoleInterface;
use WMS\Ldap\Entity\GroupNode;
use WMS\LdapBundle\Security\Ldap\User\LdapUserInterface;

class RoleResolvingHelper
{
    /** @var LdapUserInterface */
    protected $user;

    /** @var string Spl Object hashes of resolved groups */
    protected $resolvedGroups = array();

    public function __construct(LdapUserInterface $user)
    {
        $this->user = $user;
    }

    public function addGeneratedRole(GroupNode $groupNode)
    {
        $this->addRole($this->generateRoleFromGroup($groupNode), $groupNode);
    }

    public function addRole($role, GroupNode $groupNode = null)
    {
        $this->addRoles(array($role), $groupNode);
    }

    public function addRoles(array $roles, GroupNode $groupNode = null)
    {
        foreach ($roles as $role) {
            if ($groupNode !== null && !$role instanceof RoleInterface) {
                $role = new LdapGroupMemberRole($role, $groupNode->getDn(), $groupNode->getName(), $groupNode->getDescription());
            }

            $this->addRoleToUser($role);
        }

        if ($groupNode !== null && $this->hasGroupBeenResolved($groupNode) === false) {
            $this->resolvedGroups[] = spl_object_hash($groupNode);
        }
    }

    /**
     * @param GroupNode $groupNode
     *
     * @return LdapGroupMemberRole
     */
    public function generateRoleFromGroup(GroupNode $groupNode)
    {
        $roleName = \URLify::filter($groupNode->getName(), 124);
        $roleName = trim('ROLE_' . preg_replace('/[^A-Z0-9]+/', '_', strtoupper($roleName)), '_');

        return new LdapGroupMemberRole($roleName, $groupNode->getDn(), $groupNode->getName(), $groupNode->getDescription());
    }

    public function hasGroupBeenResolved(GroupNode $groupNode)
    {
        $objectHash = spl_object_hash($groupNode);

        return in_array($objectHash, $this->resolvedGroups);
    }

    protected function addRoleToUser($role)
    {
        $userRoles = $this->user->getRoles();

        if (!in_array($role, $userRoles, false)) {
            $userRoles[] = $role;
        }

        $this->user->setRoles($userRoles);
    }
} 