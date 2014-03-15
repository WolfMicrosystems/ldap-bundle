<?php
namespace WMS\LdapBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use WMS\Ldap\Connection;
use WMS\Ldap\Entity\AccountNode;
use WMS\Ldap\Entity\GroupNode;
use WMS\LdapBundle\Security\Ldap\Role\RoleResolvingHelper;
use WMS\LdapBundle\Security\Ldap\User\LdapUserInterface;

class ResolvingUserRolesEvent extends LdapEvent
{
    private $user;
    private $accountNode;
    private $groupNodes;
    /** @var RoleResolvingHelper */
    private $roleResolver;

    /**
     * @return \WMS\Ldap\Entity\AccountNode
     */
    public function getAccountNode()
    {
        return $this->accountNode;
    }

    /**
     * @return GroupNode[]
     */
    public function getGroupNodes()
    {
        return $this->groupNodes;
    }

    /**
     * @return \WMS\LdapBundle\Security\Ldap\User\LdapUserInterface
     */
    public function getUser()
    {
        return $this->user;
    }


    public function __construct(Connection $connection, LdapUserInterface $user, AccountNode $accountNode, array $groupNodes)
    {
        parent::__construct($connection);
        $this->user = $user;
        $this->accountNode = $accountNode;
        $this->groupNodes = $groupNodes;
        $this->roleResolver = new RoleResolvingHelper($user);
    }

    /**
     * @return \WMS\LdapBundle\Security\Ldap\Role\RoleResolvingHelper
     */
    public function getRoleResolver()
    {
        return $this->roleResolver;
    }
} 